<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\Controller;
use App\Core\Crypto;
use App\Core\HttpException;
use App\Core\Request;
use App\Support\Clock;

final class StudioController extends Controller
{
    public function index(Request $request): never
    {
        $this->requireUser();
        $rooms = $this->rooms();
        $selected = $this->selected($rooms, (string) $request->query('room', ''));
        $this->view('user/studio', [
            'title' => 'Studia',
            'section' => 'studia',
            'rooms' => $rooms,
            'room' => $selected,
        ]);
    }

    public function prices(Request $request): never
    {
        [$rooms, $room, $hours] = $this->bundle($request);
        $this->view('user/studio-prices', [
            'title' => 'Ceny',
            'section' => 'ceny',
            'rooms' => $rooms,
            'room' => $room,
            'hours' => $hours,
            'hourly_price' => $this->app->settings()->get('pricing.hourly', 150),
        ]);
    }

    public function savePrices(Request $request): never
    {
        $this->requireUser();
        $room = $this->roomFromInput($request);
        $back = $this->roomUrl($room, '/user/studio/ceny');
        $defaultPrice = trim((string) $request->input('default_hourly_price', ''));
        $normalized = str_replace(',', '.', $defaultPrice);
        if ($defaultPrice === '' || !is_numeric($normalized) || (float) $normalized < 0) {
            $this->flashError('Vyplň cenu za hodinu.');
            $this->redirect($back);
        }
        $this->app->settings()->set('pricing.hourly', number_format((float) $normalized, 2, '.', ''));
        foreach (range(1, 7) as $day) {
            $rawPrice = trim((string) $request->input('price_' . $day, ''));
            $hourlyPrice = $rawPrice === '' ? null : number_format((float) str_replace(',', '.', $rawPrice), 2, '.', '');
            $this->app->db()->query(
                'INSERT INTO opening_hours (room_id, weekday, opens_at, closes_at, is_closed, hourly_price)
                 VALUES (:rid, :d, :o, :c, 0, :p)
                 ON DUPLICATE KEY UPDATE hourly_price = VALUES(hourly_price)',
                [
                    'rid' => (int) $room['id'],
                    'd' => $day,
                    'o' => '06:00:00',
                    'c' => '22:00:00',
                    'p' => $hourlyPrice,
                ]
            );
        }
        $this->flashSuccess('Ceny jsou uložené.');
        $this->redirect($back);
    }

    public function hoursPage(Request $request): never
    {
        [$rooms, $room, $hours] = $this->bundle($request);
        $exceptions = [];
        if ($room) {
            $exceptions = $this->app->db()->fetchAll(
                'SELECT * FROM opening_hour_exceptions
                 WHERE room_id = :id AND exception_date >= :today
                 ORDER BY exception_date ASC',
                ['id' => (int) $room['id'], 'today' => Clock::nowLocal()->format('Y-m-d')]
            );
        }
        $this->view('user/studio-hours', [
            'title' => 'Otevírací doba',
            'section' => 'doba',
            'rooms' => $rooms,
            'room' => $room,
            'hours' => $hours,
            'exceptions' => $exceptions,
        ]);
    }

    public function store(Request $request): never
    {
        $this->requireUser();
        $name = trim((string) $request->input('name', ''));
        $location = trim((string) $request->input('location', ''));
        if ($name === '' || $location === '') {
            $this->flashError('Vyplň název prostoru i kde se nachází.');
            $this->redirect('/user/studio');
        }
        $slug = $this->uniqueSlug($this->slug($name));
        $id = (int) $this->app->db()->insert('rooms', [
            'public_id' => Crypto::uuid(),
            'slug' => $slug,
            'name' => mb_substr($name, 0, 120),
            'location' => mb_substr($location, 0, 190),
            'description' => trim((string) $request->input('description', '')) ?: null,
            'max_persons' => 3,
            'is_active' => 1,
        ]);
        foreach (range(1, 7) as $day) {
            $this->app->db()->insert('opening_hours', [
                'room_id' => $id,
                'weekday' => $day,
                'opens_at' => '06:00:00',
                'closes_at' => '22:00:00',
                'is_closed' => 0,
                'hourly_price' => null,
            ]);
        }
        $room = $this->app->db()->fetch('SELECT public_id FROM rooms WHERE id = :id', ['id' => $id]);
        $this->flashSuccess('Prostor je přidaný. Nastav u něj dny, časy a cenu.');
        $this->redirect('/user/studio?room=' . rawurlencode((string) ($room['public_id'] ?? '')));
    }

    public function update(Request $request): never
    {
        $this->requireUser();
        $room = $this->roomFromInput($request);
        $name = trim((string) $request->input('name', ''));
        $location = trim((string) $request->input('location', ''));
        if ($name === '' || $location === '') {
            $this->flashError('Název i místo prostoru musí zůstat vyplněné.');
            $this->redirect('/user/studio?room=' . rawurlencode((string) $room['public_id']));
        }
        $this->app->db()->update('rooms', [
            'name' => mb_substr($name, 0, 120),
            'location' => mb_substr($location, 0, 190),
            'is_active' => $request->input('is_active') ? 1 : 0,
        ], 'id = :id', ['id' => (int) $room['id']]);
        $this->flashSuccess('Prostor je uložený.');
        $this->redirect('/user/studio?room=' . rawurlencode((string) $room['public_id']));
    }

    public function hours(Request $request): never
    {
        $this->requireUser();
        $room = $this->roomFromInput($request);
        $back = $this->roomUrl($room, '/user/studio/doba');
        $sameWeek = (bool) $request->input('same_week');
        $mondayOpen = (string) $request->input('opens_1', '06:00');
        $mondayClose = (string) $request->input('closes_1', '22:00');
        $mondayClosed = $request->input('open_1') ? 0 : 1;
        foreach (range(1, 7) as $day) {
            $open = $sameWeek ? $mondayOpen : (string) $request->input('opens_' . $day, '06:00');
            $close = $sameWeek ? $mondayClose : (string) $request->input('closes_' . $day, '22:00');
            $closed = $sameWeek ? $mondayClosed : ($request->input('open_' . $day) ? 0 : 1);
            if ($closed === 0 && $open >= $close) {
                $this->flashError('Konec musí být později než začátek.');
                $this->redirect($back);
            }
            $this->app->db()->query(
                'INSERT INTO opening_hours (room_id, weekday, opens_at, closes_at, is_closed)
                 VALUES (:rid, :d, :o, :c, :x)
                 ON DUPLICATE KEY UPDATE opens_at = VALUES(opens_at), closes_at = VALUES(closes_at), is_closed = VALUES(is_closed)',
                [
                    'rid' => (int) $room['id'],
                    'd' => $day,
                    'o' => strlen($open) === 5 ? $open . ':00' : $open,
                    'c' => strlen($close) === 5 ? $close . ':00' : $close,
                    'x' => $closed,
                ]
            );
        }
        $this->flashSuccess('Otevírací doba je uložená.');
        $this->redirect($back);
    }

    public function exception(Request $request): never
    {
        $this->requireUser();
        $room = $this->roomFromInput($request);
        $date = (string) $request->input('date', '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->flashError('Vyber datum výjimky.');
            $this->redirect($this->roomUrl($room, '/user/studio/doba'));
        }
        $this->app->db()->query(
            'INSERT INTO opening_hour_exceptions (room_id, exception_date, opens_at, closes_at, is_closed, note, created_at)
             VALUES (:rid, :d, NULL, NULL, 1, :n, :now)
             ON DUPLICATE KEY UPDATE is_closed = 1, note = VALUES(note)',
            [
                'rid' => (int) $room['id'],
                'd' => $date,
                'n' => mb_substr(trim((string) $request->input('note', '')), 0, 255),
                'now' => Clock::utc(),
            ]
        );
        $this->flashSuccess('Den je zavřený.');
        $this->redirect($this->roomUrl($room, '/user/studio/doba'));
    }

    public function deleteException(Request $request): never
    {
        $this->requireUser();
        $room = $this->roomFromInput($request);
        $this->app->db()->query(
            'DELETE FROM opening_hour_exceptions WHERE id = :id AND room_id = :rid',
            ['id' => (int) $request->input('id', 0), 'rid' => (int) $room['id']]
        );
        $this->flashSuccess('Výjimka je smazaná.');
        $this->redirect($this->roomUrl($room, '/user/studio/doba'));
    }

    /** @return array{0: list<array<string, mixed>>, 1: ?array<string, mixed>, 2: list<array<string, mixed>>} */
    private function bundle(Request $request): array
    {
        $this->requireUser();
        $rooms = $this->rooms();
        $room = $this->selected($rooms, (string) $request->query('room', ''));
        $hours = [];
        if ($room) {
            $hours = $this->app->db()->fetchAll(
                'SELECT * FROM opening_hours WHERE room_id = :id ORDER BY weekday',
                ['id' => (int) $room['id']]
            );
        }
        return [$rooms, $room, $hours];
    }

    /** @return list<array<string, mixed>> */
    private function rooms(): array
    {
        return $this->app->db()->fetchAll('SELECT * FROM rooms ORDER BY is_active DESC, name ASC, id ASC');
    }

    /** @param array<string, mixed> $room */
    private function roomUrl(array $room, string $path): string
    {
        return $path . '?room=' . rawurlencode((string) $room['public_id']);
    }

    /** @param list<array<string, mixed>> $rooms */
    private function selected(array $rooms, string $publicId): ?array
    {
        foreach ($rooms as $room) {
            if ((string) $room['public_id'] === $publicId) {
                return $room;
            }
        }
        return $rooms[0] ?? null;
    }

    private function roomFromInput(Request $request): array
    {
        $room = $this->app->db()->fetch(
            'SELECT * FROM rooms WHERE public_id = :pid',
            ['pid' => (string) $request->input('room', '')]
        );
        if (!$room) {
            throw new HttpException(404, 'Prostor nebyl nalezen.');
        }
        return $room;
    }

    private function slug(string $name): string
    {
        $map = ['á' => 'a', 'č' => 'c', 'ď' => 'd', 'é' => 'e', 'ě' => 'e', 'í' => 'i', 'ň' => 'n', 'ó' => 'o', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ú' => 'u', 'ů' => 'u', 'ý' => 'y', 'ž' => 'z'];
        $name = strtr(mb_strtolower($name), $map);
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', $name), '-');
        return $slug !== '' ? substr($slug, 0, 70) : 'prostor';
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $n = 2;
        while ($this->app->db()->fetch('SELECT id FROM rooms WHERE slug = :s', ['s' => $slug])) {
            $slug = $base . '-' . $n;
            $n++;
        }
        return $slug;
    }
}
