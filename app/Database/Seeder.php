<?php

declare(strict_types=1);

namespace App\Database;

use App\Core\Crypto;
use App\Core\Database;
use App\Support\Clock;

final class Seeder
{
    public function __construct(private readonly Database $db)
    {
    }

    public function run(): void
    {
        $this->roles();
        $this->room();
        $this->plans();
        $this->content();
        $this->settings();
    }

    private function roles(): void
    {
        $roles = [
            ['slug' => 'user', 'name' => 'Zákazník', 'description' => 'Běžný zákazník'],
            ['slug' => 'staff', 'name' => 'Personál', 'description' => 'Provozní pracovník'],
            ['slug' => 'admin', 'name' => 'Administrátor', 'description' => 'Správce fitka'],
            ['slug' => 'owner', 'name' => 'Vlastník', 'description' => 'Hlavní správce systému'],
        ];
        foreach ($roles as $role) {
            if (!$this->db->fetch('SELECT id FROM roles WHERE slug = :s', ['s' => $role['slug']])) {
                $this->db->insert('roles', $role);
            }
        }
        $permissions = [
            'reservations.view' => 'Zobrazit rezervace',
            'reservations.manage' => 'Spravovat rezervace',
            'users.view' => 'Zobrazit zákazníky',
            'users.manage' => 'Spravovat zákazníky',
            'users.roles' => 'Měnit role',
            'content.manage' => 'Spravovat obsah',
            'settings.manage' => 'Měnit nastavení',
            'access.manage' => 'Spravovat vstupní systém',
            'payments.manage' => 'Spravovat platby',
        ];
        foreach ($permissions as $slug => $name) {
            if (!$this->db->fetch('SELECT id FROM permissions WHERE slug = :s', ['s' => $slug])) {
                $this->db->insert('permissions', ['slug' => $slug, 'name' => $name]);
            }
        }
        $map = [
            'staff' => ['reservations.view', 'users.view'],
            'admin' => ['reservations.view', 'reservations.manage', 'users.view', 'users.manage', 'content.manage', 'payments.manage', 'access.manage'],
            'owner' => ['reservations.view', 'reservations.manage', 'users.view', 'users.manage', 'users.roles', 'content.manage', 'settings.manage', 'access.manage', 'payments.manage'],
        ];
        foreach ($map as $roleSlug => $slugs) {
            $role = $this->db->fetch('SELECT id FROM roles WHERE slug = :s', ['s' => $roleSlug]);
            foreach ($slugs as $slug) {
                $perm = $this->db->fetch('SELECT id FROM permissions WHERE slug = :s', ['s' => $slug]);
                if ($role && $perm) {
                    $exists = $this->db->fetch('SELECT role_id FROM role_permissions WHERE role_id = :r AND permission_id = :p', [
                        'r' => (int) $role['id'], 'p' => (int) $perm['id'],
                    ]);
                    if (!$exists) {
                        $this->db->insert('role_permissions', [
                            'role_id' => (int) $role['id'],
                            'permission_id' => (int) $perm['id'],
                        ]);
                    }
                }
            }
        }
    }

    private function room(): void
    {
        $room = $this->db->fetch("SELECT * FROM rooms WHERE slug = 'studio'");
        if (!$room) {
            $id = (int) $this->db->insert('rooms', [
                'public_id' => Crypto::uuid(),
                'slug' => 'studio',
                'name' => 'PRIVOFIT Studio',
                'description' => 'Soukromé fitness studio',
                'max_persons' => 3,
                'is_active' => 1,
            ]);
        } else {
            $id = (int) $room['id'];
        }
        for ($d = 1; $d <= 7; $d++) {
            $exists = $this->db->fetch('SELECT id FROM opening_hours WHERE room_id = :r AND weekday = :d', ['r' => $id, 'd' => $d]);
            if (!$exists) {
                $this->db->insert('opening_hours', [
                    'room_id' => $id,
                    'weekday' => $d,
                    'opens_at' => '06:00:00',
                    'closes_at' => '22:00:00',
                    'is_closed' => 0,
                ]);
            }
        }
        if (!$this->db->fetch('SELECT id FROM doors LIMIT 1')) {
            $this->db->insert('doors', [
                'public_id' => Crypto::uuid(),
                'room_id' => $id,
                'name' => 'Vstupní dveře',
                'provider' => 'mock',
                'is_active' => 1,
                'last_known_state' => 'locked',
                'last_known_door_state' => 'closed',
                'last_battery_percent' => 100,
            ]);
        }
    }

    private function plans(): void
    {
        $plans = [
            ['slug' => 'single', 'name' => 'Jednorázový vstup', 'description' => 'TESTOVACÍ CENA: jeden vstup do soukromého studia.', 'type' => 'single', 'price' => '249.00', 'entries' => 1, 'duration_days' => null, 'max_guests' => 0, 'sort_order' => 1],
            ['slug' => 'pack10', 'name' => 'Balíček 10 vstupů', 'description' => 'TESTOVACÍ CENA: deset vstupů.', 'type' => 'pack', 'price' => '1990.00', 'entries' => 10, 'duration_days' => 180, 'max_guests' => 1, 'sort_order' => 2],
            ['slug' => 'monthly', 'name' => 'Měsíční členství', 'description' => 'TESTOVACÍ CENA: měsíční tarif.', 'type' => 'monthly', 'price' => '2490.00', 'entries' => null, 'duration_days' => 30, 'max_guests' => 1, 'sort_order' => 3],
            ['slug' => 'credit', 'name' => 'Kredit 2000 Kč', 'description' => 'TESTOVACÍ CENA: předplacený kredit.', 'type' => 'credit', 'price' => '2000.00', 'entries' => null, 'duration_days' => 365, 'max_guests' => 0, 'sort_order' => 4],
        ];
        foreach ($plans as $plan) {
            if (!$this->db->fetch('SELECT id FROM membership_plans WHERE slug = :s', ['s' => $plan['slug']])) {
                $this->db->insert('membership_plans', $plan + [
                    'public_id' => Crypto::uuid(),
                    'currency' => 'CZK',
                    'is_active' => 1,
                ]);
            }
        }
    }

    private function content(): void
    {
        $faqs = [
            ['Jak probíhá vstup?', 'V čase rezervace otevřete dveře v aplikaci. Server ověří účet, rezervaci i oprávnění a teprve potom pošle příkaz zámku.', 1],
            ['Mohu přijít s kamarádem?', 'Ano, pokud to tarif a kapacita studia dovolují. Počet osob volíte při rezervaci.', 2],
            ['Jak fungují rezervace?', 'Studio je soukromé, proto se termíny nepřekrývají. Volný čas uvidíte v kalendáři.', 3],
            ['Co když nemohu dorazit?', 'Rezervaci lze zrušit podle storno lhůty nastavené provozovatelem.', 4],
            ['Co když se mi nepodaří otevřít dveře?', 'Zkuste to znovu v aplikaci. Pokud problém trvá, kontaktujte nás. Nouzový mechanický přístup je řešen i fyzicky na místě.', 5],
        ];
        if (!(int) $this->db->fetchColumn('SELECT COUNT(*) FROM faq_items')) {
            foreach ($faqs as [$q, $a, $s]) {
                $this->db->insert('faq_items', [
                    'question' => $q,
                    'answer' => $a,
                    'sort_order' => $s,
                    'is_published' => 1,
                ]);
            }
        }
        $pages = [
            'home.hero' => ['Tvoje fitko. Tvůj prostor.', 'Trénuj bez čekání na stroje, bez přeplněných prostor a bez kompromisů. Rezervuj si vlastní fitness studio a užij si trénink přesně podle sebe.'],
            'obchodni-podminky' => ['Obchodní podmínky', 'Doplňte kompletní obchodní podmínky v administraci před spuštěním provozu.'],
            'ochrana-udaju' => ['Ochrana osobních údajů', 'PRIVOFIT zpracovává osobní údaje v rozsahu potřebném pro vedení účtu, rezervace, platby a vstup. Logy přístupů se uchovávají po omezenou dobu. Údaje lze exportovat nebo požádat o výmaz v profilu.'],
        ];
        foreach ($pages as $slug => [$title, $body]) {
            if (!$this->db->fetch('SELECT id FROM page_contents WHERE slug = :s', ['s' => $slug])) {
                $this->db->insert('page_contents', ['slug' => $slug, 'title' => $title, 'body_html' => $body]);
            }
        }
    }

    private function settings(): void
    {
        $defaults = [
            'reservation.slot_minutes' => 15,
            'reservation.min_minutes' => 60,
            'reservation.max_minutes' => 180,
            'reservation.buffer_minutes' => 15,
            'reservation.hold_minutes' => 40,
            'reservation.cancellation_hours' => 12,
            'access.early_minutes' => 10,
            'access.late_minutes' => 10,
            'pricing.hourly' => 150,
            'contact.address' => 'Adresa studia bude doplněna v administraci.',
            'contact.email' => 'ahoj@privofit.cz',
            'contact.phone' => '',
            'contact.hours' => 'Podle rezervací, výchozí provoz 6:00–22:00',
            'contact.map_embed' => '',
        ];
        foreach ($defaults as $key => $value) {
            if (!$this->db->fetch('SELECT setting_key FROM app_settings WHERE setting_key = :k', ['k' => $key])) {
                $this->db->insert('app_settings', [
                    'setting_key' => $key,
                    'setting_value' => is_string($value) ? $value : json_encode($value),
                    'is_secret' => 0,
                ]);
            }
        }
    }
}
