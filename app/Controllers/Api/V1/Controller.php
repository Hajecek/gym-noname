<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\Request;
use App\Core\Response;
use App\Services\Auth\AuthService;
use App\Services\Mobile\MobileApiService;

abstract class Controller extends \App\Controllers\Controller
{
    protected function api(): MobileApiService
    {
        return MobileApiService::make($this->app->db());
    }

    protected function auth(): AuthService
    {
        return AuthService::make($this->app->db());
    }

    protected function send(mixed $payload, int $status = 200): never
    {
        Response::json(is_array($payload) ? $payload : ['ok' => true], $status);
    }

    protected function str(Request $request, string $camel, string $snake, string $default = ''): string
    {
        $value = $request->input($camel, $request->input($snake, $default));
        return is_string($value) ? trim($value) : $default;
    }

    /** @return list<string> */
    protected function stringList(Request $request, string $camel, string $snake): array
    {
        $value = $request->input($camel, $request->input($snake, []));
        if (!is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $item) {
            if (is_string($item) && $item !== '') {
                $out[] = $item;
            }
        }
        return $out;
    }
}
