<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Core\Database;

final class ContentService
{
    public function __construct(private readonly Database $db)
    {
    }

    public function page(string $slug, ?string $defaultTitle = null, ?string $defaultBody = null): array
    {
        $page = $this->db->fetch('SELECT * FROM page_contents WHERE slug = :s', ['s' => $slug]);
        if ($page) {
            return $page;
        }
        return [
            'slug' => $slug,
            'title' => $defaultTitle ?? $slug,
            'body_html' => $defaultBody ?? '',
        ];
    }

    public function savePage(string $slug, string $title, string $body, ?int $userId): void
    {
        $existing = $this->db->fetch('SELECT id FROM page_contents WHERE slug = :s', ['s' => $slug]);
        if ($existing) {
            $this->db->update('page_contents', [
                'title' => $title,
                'body_html' => $body,
                'updated_by' => $userId,
            ], 'slug = :s', ['s' => $slug]);
            return;
        }
        $this->db->insert('page_contents', [
            'slug' => $slug,
            'title' => $title,
            'body_html' => $body,
            'updated_by' => $userId,
        ]);
    }

    public function faqs(): array
    {
        return $this->db->fetchAll('SELECT * FROM faq_items WHERE is_published = 1 ORDER BY sort_order ASC, id ASC');
    }

    public function allFaqs(): array
    {
        return $this->db->fetchAll('SELECT * FROM faq_items ORDER BY sort_order ASC, id ASC');
    }

    public function equipment(): array
    {
        return $this->db->fetchAll('SELECT * FROM equipment_items WHERE is_published = 1 ORDER BY sort_order ASC, id ASC');
    }

    public function media(): array
    {
        return $this->db->fetchAll('SELECT * FROM gym_media WHERE is_published = 1 ORDER BY sort_order ASC, id ASC');
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
