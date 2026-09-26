<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Core\Database;
use App\Core\HttpException;

final class ContentService
{
    private const DOCUMENTS = [
        'obchodni-podminky',
        'ochrana-udaju',
    ];

    public function __construct(private readonly Database $db)
    {
    }

    /** @return array{slug:string,title:string,body_html:string} */
    public function page(string $slug, ?string $defaultTitle = null, ?string $defaultBody = null): array
    {
        $slug = strtolower(trim($slug));
        if (!in_array($slug, self::DOCUMENTS, true)) {
            if ($defaultTitle !== null || $defaultBody !== null) {
                return [
                    'slug' => $slug,
                    'title' => $defaultTitle ?? $slug,
                    'body_html' => $defaultBody ?? '',
                ];
            }
            throw new HttpException(404, 'Dokument nebyl nalezen.');
        }

        $file = dirname(__DIR__, 3) . '/resources/content/' . $slug . '.php';
        if (!is_file($file)) {
            throw new HttpException(404, 'Dokument nebyl nalezen.');
        }

        /** @var mixed $data */
        $data = require $file;
        if (!is_array($data)) {
            throw new HttpException(500, 'Dokument má neplatný formát.');
        }

        return [
            'slug' => $slug,
            'title' => (string) ($data['title'] ?? $defaultTitle ?? $slug),
            'body_html' => (string) ($data['body'] ?? $data['body_html'] ?? $defaultBody ?? ''),
        ];
    }

    public function faqs(): array
    {
        return $this->db->fetchAll('SELECT * FROM faq_items WHERE is_published = 1 ORDER BY sort_order ASC, id ASC');
    }

    public function allFaqs(): array
    {
        return $this->db->fetchAll('SELECT * FROM faq_items ORDER BY sort_order ASC, id ASC');
    }

    public function contact(): array
    {
        return [
            'address' => (string) ($this->setting('contact.address') ?? 'Adresa bude doplněna v administraci.'),
            'email' => (string) ($this->setting('contact.email') ?? 'ahoj@privofit.cz'),
            'phone' => (string) ($this->setting('contact.phone') ?? ''),
            'hours' => (string) ($this->setting('contact.hours') ?? 'Podle rezervací'),
            'map_embed' => (string) ($this->setting('contact.map_embed') ?? ''),
        ];
    }

    private function setting(string $key): mixed
    {
        $row = $this->db->fetch('SELECT setting_value FROM app_settings WHERE setting_key = :k', ['k' => $key]);
        if (!$row) {
            return null;
        }
        $decoded = json_decode((string) $row['setting_value'], true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $row['setting_value'];
    }
}
