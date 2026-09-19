<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private string $method;
    private string $path;
    private string $basePath;
    /** @var array<string, mixed> */
    private array $json = [];
    private bool $jsonParsed = false;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($this->method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper((string) $_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $this->method = $override;
            }
        }

        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $this->basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if (str_ends_with($this->basePath, '/public')) {
            $this->basePath = substr($this->basePath, 0, -7) ?: '';
        }

        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $uri = parse_url($uri, PHP_URL_PATH) ?: '/';
        $uri = rawurldecode($uri);

        if ($this->basePath !== '' && $this->basePath !== '/' && str_starts_with($uri, $this->basePath)) {
            $uri = substr($uri, strlen($this->basePath)) ?: '/';
        }

        $this->path = '/' . trim($uri, '/');
        if ($this->path !== '/') {
            $this->path = rtrim($this->path, '/');
        }
        if ($this->path === '/index.php') {
            $this->path = '/';
        }
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function basePath(): string
    {
        return $this->basePath === '/' ? '' : $this->basePath;
    }

    public function isApi(): bool
    {
        return str_starts_with($this->path, '/api/');
    }

    public function ip(): string
    {
        $candidates = [
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ];
        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || $candidate === '') {
                continue;
            }
            $first = trim(explode(',', $candidate)[0]);
            if (filter_var($first, FILTER_VALIDATE_IP)) {
                return $first;
            }
        }
        return '0.0.0.0';
    }

    public function userAgent(): string
    {
        return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return isset($_SERVER[$key]) ? (string) $_SERVER[$key] : null;
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization') ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null);
        if (!is_string($header) || !str_starts_with($header, 'Bearer ')) {
            return null;
        }
        return trim(substr($header, 7));
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_POST)) {
            return $_POST[$key];
        }
        $json = $this->json();
        if (array_key_exists($key, $json)) {
            return $json[$key];
        }
        if (array_key_exists($key, $_GET)) {
            return $_GET[$key];
        }
        return $default;
    }

    public function all(): array
    {
        return array_merge($_GET, $_POST, $this->json());
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function json(): array
    {
        if ($this->jsonParsed) {
            return $this->json;
        }
        $this->jsonParsed = true;
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        if (!str_contains($contentType, 'application/json')) {
            return $this->json;
        }
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || $raw === '') {
            return $this->json;
        }
        $decoded = json_decode($raw, true);
        $this->json = is_array($decoded) ? $decoded : [];
        return $this->json;
    }

    public function file(string $key): ?array
    {
        if (!isset($_FILES[$key]) || !is_array($_FILES[$key])) {
            return null;
        }
        $file = $_FILES[$key];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $file;
    }

    public function wantsJson(): bool
    {
        return $this->isApi() || str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
    }
}
