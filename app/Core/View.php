<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $template, array $data = [], ?string $layout = 'layouts/user'): string
    {
        $content = self::renderPartial($template, $data);
        if ($layout === null) {
            return $content;
        }
        return self::renderPartial($layout, array_merge($data, ['content' => $content]));
    }

    public static function renderPartial(string $template, array $data = []): string
    {
        $path = dirname(__DIR__, 2) . '/resources/views/' . str_replace('.', '/', $template) . '.php';
        if (!is_file($path)) {
            throw new \RuntimeException('Šablona nebyla nalezena.');
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $path;
        return (string) ob_get_clean();
    }
}
