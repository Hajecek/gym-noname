<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Services\Billing\CheckoutService;
use App\Services\MembershipService;
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
        $membership = (new MembershipService($this->app->db()))->activeForUser((int) $user['id']);
        $covers = $membership && ($membership['entries_remaining'] === null || (int) $membership['entries_remaining'] > 0);
        $this->view('user/reservations', [
            'title' => 'Rezervace',
            'availability' => $service->availability($date),
            'mine' => $service->forUser((int) $user['id']),
            'date' => $date,
            'today' => Clock::nowLocal()->format('Y-m-d'),
            'membership_covers' => $covers,
            'pageScripts' => ['js/reservations.js'],
        ]);
    }

    public function availability(Request $request): never
    {
        $this->requireUser();
        $date = (string) $request->query('date', Clock::nowLocal()->format('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->jsonError('Neplatné datum.', 422);
        }
        $this->jsonOk(ReservationService::make($this->app->db())->availability($date));
    }

    public function calendar(Request $request): never
    {
        $this->requireUser();
        $year = (int) $request->query('year', Clock::nowLocal()->format('Y'));
        $month = (int) $request->query('month', Clock::nowLocal()->format('n'));
        $this->jsonOk([
            'year' => $year,
            'month' => $month,
            'days' => ReservationService::make($this->app->db())->monthOverview($year, $month),
        ]);
    }

    public function store(Request $request): never
    {
        $user = $this->requireUser();
        $start = (string) $request->input('start');
        $date = preg_match('/^(\d{4}-\d{2}-\d{2})/', $start, $match) ? $match[1] : '';
        $back = '/user/rezervace' . ($date !== '' ? '?date=' . rawurlencode($date) : '');
        try {
            $reservation = ReservationService::make($this->app->db())->create(
                $user,
                $start,
                (int) $request->input('duration', 60),
                (int) $request->input('guests', 1)
            );
            if (($reservation['status'] ?? '') === 'confirmed' || (float) ($reservation['price'] ?? 0) <= 0) {
                $this->flashSuccess('Rezervace byla potvrzena.');
                if ($request->wantsJson()) {
                    $this->jsonOk(['redirect' => $this->app->url($back)]);
                }
                $this->redirect($back);
            }
            $url = CheckoutService::make($this->app->db())->start($user, $reservation, $this->app);
            if ($request->wantsJson()) {
                $this->jsonOk(['checkout_url' => $url]);
            }
            Response::redirect($url);
        } catch (HttpException $e) {
            if ($request->wantsJson()) {
                $this->jsonError($e->getMessage(), $e->status);
            }
            $this->flashError($e->getMessage());
            $this->redirect($back);
        }
    }

    public function paid(Request $request): never
    {
        $user = $this->requireUser();
        $sessionId = trim((string) $request->query('session_id', ''));
        try {
            $payment = CheckoutService::make($this->app->db())->fulfillSession($sessionId);
            if ((int) ($payment['user_id'] ?? 0) !== (int) $user['id']) {
                throw new HttpException(403, 'Tato platba nepatří k tvému účtu.');
            }
            $this->flashSuccess('Platba prošla. Rezervace je potvrzená.');
        } catch (HttpException $e) {
            $this->flashError($e->getMessage());
        }
        $this->redirect('/user/rezervace');
    }

    public function checkoutCancel(Request $request): never
    {
        $user = $this->requireUser();
        $paymentId = trim((string) $request->query('platba', ''));
        if ($paymentId !== '') {
            CheckoutService::make($this->app->db())->cancelHold($paymentId, $user);
        }
        $this->flashError('Platba se nedokončila. Termín se uvolnil, můžeš ho vybrat znovu.');
        $date = (string) $request->query('date', '');
        $this->redirect('/user/rezervace' . (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? '?date=' . rawurlencode($date) : ''));
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
