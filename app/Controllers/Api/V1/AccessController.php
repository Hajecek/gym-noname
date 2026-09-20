<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\HttpException;
use App\Core\Request;
use App\Services\Access\AccessControlService;

final class AccessController extends Controller
{
    public function eligibility(): never
    {
        $this->send($this->api()->eligibility($this->requireUser()));
    }

    public function commands(Request $request): never
    {
        try {
            $operation = $request->input('operationID', $request->query('operationID', $request->query('operation_id')));
            $this->send($this->api()->doorStatus(
                $this->requireUser(),
                $this->str($request, 'requestID', 'request_id') !== ''
                    ? $this->str($request, 'requestID', 'request_id')
                    : (string) $request->query('requestID', $request->query('request_id', '')),
                is_string($operation) && $operation !== '' ? $operation : null
            ));
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function open(Request $request): never
    {
        try {
            $this->send($this->api()->openDoor(
                $this->requireUser(),
                $this->str($request, 'doorID', 'door_id'),
                $this->str($request, 'requestID', 'request_id'),
                $request->ip()
            ));
        } catch (HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        }
    }

    public function status(): never
    {
        $access = AccessControlService::make($this->app->db());
        $this->jsonOk([
            'authorization' => $access->canAttempt($this->requireUser()),
            'door' => $access->doorStatus(),
        ]);
    }
}
