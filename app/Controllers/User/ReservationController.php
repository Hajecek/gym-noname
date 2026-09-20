<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Services\ReservationService;
use App\Support\Clock;

final class ReservationController extends Controller
{
    public function index(Request $request): never
    {
        $user = $this->requireUser();
        $service = ReservationService::make($this->app->db());
        $date = (string) $request->query('date', Clock::nowLocal()->format('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = Clock::nowLocal()->format('Y-m-d');
        }
        $this->view('user/reservations', [
            'title' => 'Rezervace',
            'availability' => $service->availability($date),
            'mine' => $service->forUser((int) $user['id']),
            'date' => $date,
        ]);
    }

    public function store(Request $request): never
    {
        $user = $this->requireUser();
        try {
            ReservationService::make($this->app->db())->create(
                $user,
                (string) $request->input('start'),
                (int) $request->input('duration', 60),
                (int) $request->input('guests', 1)
            );
            $this->flashSuccess('Rezervace byla vytvořena.');
        } catch (HttpException $e) {
            $this->flashError($e->getMessage());
        }
        $this->redirect('/user/rezervace');
    }

    public function cancel(Request $request, array $params): never
    {
        $user = $this->requireUser();
        try {
            ReservationService::make($this->app->db())->cancel($user, (string) $params['id']);
            $this->flashSuccess('Rezervace byla zrušena.');
        } catch (HttpException $e) {
            $this->flashError($e->getMessage());
        }
        $this->redirect('/user/rezervace');
    }
}
