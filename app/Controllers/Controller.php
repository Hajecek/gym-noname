<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Application;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

abstract class Controller
{
    public function __construct(protected readonly Application $app)
    {
    }

    protected function view(string $template, array $data = [], ?string $layout = 'layouts/app'): never
    {
        $user = $this->app->auth()->user();
        $data['user'] = $user;
        $data['auth'] = $this->app->auth();
        $data['cspNonce'] = (string) Session::get('_csp_nonce', '');
        Response::html(View::render($template, $data, $layout));
    }

    protected function publicView(string $template, array $data = []): never
    {
        $data['page'] = $data['page'] ?? 'inner';
        $this->view($template, $data, 'layouts/brand');
    }

    protected function redirect(string $path): never
    {
        Response::redirect($this->app->url($path));
    }

    protected function back(string $fallback = '/'): never
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? $this->app->url($fallback);
        Response::redirect($ref);
    }

    protected function flashError(string $message): void
    {
        Session::flash('error', $message);
    }

    protected function flashSuccess(string $message): void
    {
        Session::flash('success', $message);
    }

    protected function requireUser(): array
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            throw new HttpException(401, 'Nejste přihlášeni.');
        }
        return $user;
    }

    protected function jsonOk(mixed $data = null, string $message = 'Operace byla úspěšná.', int $status = 200): never
    {
        Response::success($data, $message, $status);
    }

    protected function jsonError(string $message, int $status = 400, mixed $errors = null): never
    {
        Response::error($message, $status, $errors);
    }

    protected function rememberOld(Request $request): void
    {
        $old = $request->all();
        unset($old['password'], $old['password_confirmation'], $old['_csrf'], $old['current_password']);
        Session::set('_old', $old);
    }
}
