<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\HttpException;
use App\Core\Request;
use App\Services\ReservationService;

final class ReservationsController extends Controller
{
    public function availability(Request $request): never
    {
        $date = (string) $request->query('date', \App\Support\Clock::nowLocal()->format('Y-m-d'));
        $this->jsonOk(ReservationService::make($this->app->db())->availability($date));
    }

    public function slots(): never
    {
        try {
            $this->send($this->api()->slots());
        } catch (HttpException $e) {
            if ($e->status === 404) {
                $this->send([]);
            }
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function quote(Request $request): never
    {
        try {
            $this->send($this->api()->quote($this->requireUser(), $this->stringList($request, 'slotIDs', 'slot_ids')));
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function reserve(Request $request): never
    {
        try {
            $slot = $this->str($request, 'slotID', 'slot_id');
            $requestId = $this->str($request, 'requestID', 'request_id');
            $this->send($this->api()->reserve($this->requireUser(), $slot, $requestId), 201);
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function pay(Request $request): never
    {
        try {
            $applePay = (array) ($request->input('applePay') ?? $request->input('apple_pay') ?? []);
            $this->send($this->api()->payAndReserve(
                $this->requireUser(),
                $this->stringList($request, 'slotIDs', 'slot_ids'),
                $this->str($request, 'requestID', 'request_id'),
                $applePay
            ));
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        } catch (\Throwable $e) {
            \App\Core\Logger::error('API reservations/pay', ['error' => $e->getMessage()]);
            $this->jsonError('Rezervaci se nepodařilo dokončit.', 500);
        }
    }

    public function reservations(): never
    {
        $this->send($this->api()->reservations($this->requireUser()));
    }

    public function show(Request $request, array $params): never
    {
        try {
            $reservation = ReservationService::make($this->app->db())->owned($this->requireUser(), (string) $params['id']);
            $this->jsonOk(['reservation' => $reservation]);
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function cancel(Request $request, array $params): never
    {
        try {
            $this->api()->cancel(
                $this->requireUser(),
                (string) $params['id'],
                $this->str($request, 'requestID', 'request_id')
            );
            $this->send(['ok' => true]);
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }
}
