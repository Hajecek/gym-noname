<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function html(string $html, int $status = 200): never
    {
        http_response_code($status);
        header('Status: ' . $status . ' ' . ($status === 200 ? 'OK' : 'Error'), true, $status);
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        exit;
    }

    public static function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(mixed $data = null, string $message = 'Operace byla úspěšná.', int $status = 200): never
    {
        self::json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $status);
    }

    public static function error(string $message, int $status = 400, mixed $errors = null): never
    {
        $payload = [
            'success' => false,
            'data' => null,
            'message' => $message,
        ];
        if ($errors !== null) {
            $payload['errors'] = $errors;
        }
        self::json($payload, $status);
    }

    public static function redirect(string $url, int $status = 302): never
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    public static function download(string $filename, string $content, string $mime = 'application/octet-stream'): never
    {
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store');
        echo $content;
        exit;
    }

    public static function send(string $content, string $mime, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: ' . $mime);
        header('Cache-Control: no-store');
        echo $content;
        exit;
    }

    public static function file(string $path, string $mime, int $maxAge = 86400): never
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new HttpException(404, 'Soubor nebyl nalezen.');
        }
        http_response_code(200);
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=' . $maxAge);
        header('Content-Length: ' . (string) filesize($path));
        readfile($path);
        exit;
    }
}
