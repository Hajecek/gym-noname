<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

final class VisitsController extends Controller
{
    public function visits(): never
    {
        try {
            $this->send($this->api()->visits($this->requireUser()));
        } catch (\App\Core\HttpException $e) {
            $this->jsonError($e->getMessage(), $e->status);
        } catch (\Throwable $e) {
            \App\Core\Logger::error('API visits', ['error' => $e->getMessage()]);
            $this->send([]);
        }
    }
}
