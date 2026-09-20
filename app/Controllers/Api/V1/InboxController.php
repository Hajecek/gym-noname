<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

final class InboxController extends Controller
{
    public function inbox(): never
    {
        try {
            $this->send($this->api()->inbox($this->requireUser()));
        } catch (\App\Core\HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        } catch (\Throwable $e) {
            \App\Core\Logger::error('API inbox', ['error' => $e->getMessage()]);
            $this->send([]);
        }
    }
}
