<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\SettingsService;
use App\Support\Clock;
use Throwable;

final class Application
{
    private static ?self $instance = null;
    private Database $db;
    private Auth $auth;
    private Router $router;
    private Request $request;
    private SettingsService $settings;
    /** @var array<string, mixed> */
    private array $config = [];

    public static function create(): self
    {
        $root = dirname(__DIR__, 2);
        Env::load($root . '/.env');
        date_default_timezone_set((string) Env::get('APP_TIMEZONE', 'UTC'));

        $app = new self();
        self::$instance = $app;
        $app->boot($root);
        return $app;
    }

    public static function getInstance(): self
    {
        if (!self::$instance) {
            throw new \RuntimeException('Aplikace není inicializována.');
        }
        return self::$instance;
    }

    private function boot(string $root): void
    {
        $this->config = [
            'app' => require $root . '/config/app.php',
            'database' => require $root . '/config/database.php',
            'security' => require $root . '/config/security.php',
            'services' => require $root . '/config/services.php',
        ];

        $this->configureErrors();
        $this->db = new Database();
        $this->settings = new SettingsService($this->db);
        $this->auth = new Auth($this->db);
        $this->router = new Router();
        $this->request = new Request();
    }

    public function run(): void
    {
        $this->sendSecurityHeaders();
        $request = $this->request;

        if (!$request->isApi()) {
            Session::start();
        } else {
            // API může používat session cookie nebo bearer token
            if (!$request->bearerToken()) {
                Session::start();
            }
        }

        try {
            $this->auth->hydrate($request);

            if (!$request->isApi()) {
                if (!Csrf::verifyRequest($request)) {
                    if ($request->wantsJson()) {
                        Response::error('Neplatný bezpečnostní token. Obnovte stránku a zkuste to znovu.', 419);
                    }
                    Session::flash('error', 'Neplatný bezpečnostní token. Obnovte stránku a zkuste to znovu.');
                    Response::redirect($this->url('/'));
                }
            }

            require dirname(__DIR__, 2) . '/routes/web.php';
            require dirname(__DIR__, 2) . '/routes/api.php';

            $match = $this->router->dispatch($request);
            if ($match === null) {
                $path = $request->path();
                if ($path === '/app' || str_starts_with($path, '/app/')) {
                    $target = '/user' . substr($path, 4);
                    Response::redirect($this->url($target === '/user' ? '/user' : $target), $request->method() === 'GET' ? 301 : 307);
                }
                $this->abort(404);
            }

            foreach ($match['middleware'] as $middleware) {
                $instance = new $middleware();
                $instance->handle($request, $this);
            }

            $handler = $match['handler'];
            if (is_array($handler)) {
                [$class, $method] = $handler;
                $controller = new $class($this);
                $controller->{$method}($request, $match['params']);
                return;
            }

            $handler($request, $match['params']);
        } catch (HttpException $e) {
            $this->abort($e->status, $e->getMessage());
        } catch (Throwable $e) {
            Logger::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            if ($this->config('app.debug')) {
                $this->abort(500, $e->getMessage());
            }
            $this->abort(500, 'Došlo k neočekávané chybě. Zkuste to prosím později.');
        }
    }

    public function db(): Database
    {
        return $this->db;
    }

    public function auth(): Auth
    {
        return $this->auth;
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function request(): Request
    {
        return $this->request;
    }

    public function settings(): SettingsService
    {
        return $this->settings;
    }

    public function config(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->config;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    public function url(string $path = '/'): string
    {
        $path = '/' . ltrim($path, '/');
        $configured = rtrim((string) $this->config('app.url', ''), '/');
        if ($configured !== '' && preg_match('#^https?://#i', $configured) === 1) {
            $configured = rtrim((string) (parse_url($configured, PHP_URL_PATH) ?: ''), '/');
        }
        if ($configured !== '') {
            return $configured . ($path === '/' ? '/' : $path);
        }
        $base = $this->request->basePath();
        return ($base === '' ? '' : $base) . ($path === '/' ? '/' : $path);
    }

    public function abort(int $status, string $message = ''): never
    {
        if ($this->request->wantsJson()) {
            $fallback = match ($status) {
                401 => 'Nejste přihlášeni.',
                403 => 'K této akci nemáte oprávnění.',
                404 => 'Požadovaný zdroj nebyl nalezen.',
                429 => 'Příliš mnoho požadavků. Zkuste to později.',
                default => $message !== '' ? $message : 'Došlo k chybě.',
            };
            Response::error($message !== '' ? $message : $fallback, $status);
        }

        $view = match ($status) {
            403 => 'errors/403',
            404 => 'errors/404',
            default => 'errors/500',
        };
        Response::html(View::render($view, [
            'title' => 'Chyba',
            'message' => $message,
            'status' => $status,
            'page' => 'inner',
        ], 'layouts/brand'), $status);
    }

    private function sendSecurityHeaders(): void
    {
        foreach ((array) $this->config('security.headers', []) as $name => $value) {
            header($name . ': ' . $value);
        }
        header('X-Powered-By: PRIVOFIT');
        $nonce = Crypto::token(16);
        Session::start();
        Session::set('_csp_nonce', $nonce);
        $csp = implode('; ', [
            "default-src 'self'",
            "img-src 'self' data:",
            "font-src 'self' https://fonts.gstatic.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "script-src 'self' 'nonce-{$nonce}'",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        header('Content-Security-Policy: ' . $csp);
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    private function configureErrors(): void
    {
        $debug = (bool) $this->config('app.debug', false);
        ini_set('display_errors', $debug ? '1' : '0');
        error_reporting(E_ALL);
        set_exception_handler(function (Throwable $e): void {
            Logger::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            if ($this->config('app.debug')) {
                http_response_code(500);
                echo 'Chyba: ' . e($e->getMessage());
                exit;
            }
            $this->abort(500, 'Došlo k neočekávané chybě.');
        });
    }
}
