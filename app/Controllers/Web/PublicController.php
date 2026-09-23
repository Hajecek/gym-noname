<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\Controller;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Services\AvatarService;
use App\Services\Content\ContentService;
use App\Services\MembershipService;
use App\Support\Clock;

final class PublicController extends Controller
{
    public function home(Request $request): never
    {
        $this->publicView('public/home', [
            'title' => 'PRIVOFIT — Tvůj prostor. Tvoje pravidla.',
            'page' => 'home',
            'description' => 'Objev soukromé fitness PRIVOFIT. Prostor pro tvůj trénink, tvoje tempo a tvoje lepší já.',
            'plans' => $this->publicPlans(),
            'hourlyPrice' => (float) $this->app->settings()->get('pricing.hourly', 150),
        ]);
    }

    public function pricing(Request $request): never
    {
        $plans = $this->publicPlans();
        $this->publicView('public/pricing', [
            'title' => 'Ceník',
            'plans' => $plans,
        ]);
    }

    public function faq(Request $request): never
    {
        $this->publicView('public/faq', [
            'title' => 'Časté otázky',
            'faqs' => (new ContentService($this->app->db()))->faqs(),
        ]);
    }

    public function contact(Request $request): never
    {
        $this->publicView('public/contact', [
            'title' => 'Kontakt',
            'contact' => (new ContentService($this->app->db()))->contact(),
        ]);
    }

    public function sendContact(Request $request): never
    {
        $limiter = new RateLimiter($this->app->db());
        if (!$limiter->attempt('contact', $request->ip(), 5, 60)) {
            $this->flashError('Příliš mnoho zpráv. Zkuste to později.');
            $this->redirect('/kontakt');
        }
        $name = trim((string) $request->input('name', ''));
        $email = trim((string) $request->input('email', ''));
        $message = trim((string) $request->input('message', ''));
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($message) < 10) {
            $this->rememberOld($request);
            $this->flashError('Vyplňte jméno, platný e-mail a zprávu.');
            $this->redirect('/kontakt');
        }
        $this->app->db()->insert('contact_messages', [
            'name' => $name,
            'email' => $email,
            'phone' => substr((string) $request->input('phone', ''), 0, 30) ?: null,
            'message' => $message,
            'ip_address' => $request->ip(),
            'created_at' => Clock::utc(),
        ]);
        $this->flashSuccess('Zpráva byla odeslána. Ozveme se co nejdříve.');
        $this->redirect('/kontakt');
    }

    public function interest(Request $request): never
    {
        $this->publicView('public/interest', [
            'title' => 'Zjišťujeme zájem — PRIVOFIT',
            'page' => 'interest',
            'description' => 'PRIVOFIT ještě neotevírá. Nech e-mail a ozveme se, až půjde rezervovat první trénink v soukromém studiu.',
        ]);
    }

    public function signupInterest(Request $request): never
    {
        $back = $this->interestReturnPath($request);
        $limiter = new RateLimiter($this->app->db());
        if (!$limiter->attempt('interest', $request->ip(), 8, 60)) {
            $this->flashError('Příliš mnoho pokusů. Zkus to za chvíli znovu.');
            $this->redirect($back);
        }

        if (trim((string) $request->input('website', '')) !== '') {
            $this->flashSuccess('Díky. Až otevřeme, ozveme se.');
            $this->redirect($back);
        }

        $email = mb_strtolower(trim((string) $request->input('email', '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            $this->rememberOld($request);
            $this->flashError('Zadej platný e-mail, ať víme, kam se ozvat.');
            $this->redirect($back);
        }

        $source = $this->interestSource($request);
        $existing = $this->app->db()->fetch('SELECT id FROM interest_signups WHERE email = :e', ['e' => $email]);
        if ($existing) {
            $this->flashSuccess('Tento e-mail už evidujeme. Ozveme se, až bude PRIVOFIT připravené.');
            $this->redirect($back);
        }

        try {
            $this->app->db()->insert('interest_signups', [
                'email' => $email,
                'source' => substr($source, 0, 40),
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent(), 0, 255) ?: null,
                'created_at' => Clock::utc(),
            ]);
        } catch (\PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
        }

        $this->flashSuccess('Díky. Až otevřeme, ozveme se na tento e-mail.');
        $this->redirect($back);
    }

    private function interestSource(Request $request): string
    {
        return preg_replace('/[^a-z0-9_-]/', '', mb_strtolower((string) $request->input('source', 'home'))) ?: 'home';
    }

    private function interestReturnPath(Request $request): string
    {
        return $this->interestSource($request) === 'home' ? '/#zajem' : '/zajem';
    }

    public function legal(Request $request, array $params): never
    {
        $slug = $params['slug'] ?? 'obchodni-podminky';
        $page = (new ContentService($this->app->db()))->page($slug, 'Dokument');
        $this->publicView('public/legal', [
            'title' => $page['title'],
            'page' => 'inner',
            'document' => $page,
        ]);
    }

    public function manifest(): never
    {
        header('Content-Type: application/manifest+json; charset=UTF-8');
        echo json_encode([
            'name' => 'PRIVOFIT',
            'short_name' => 'PRIVOFIT',
            'description' => 'Soukromé fitness studio. Tvůj prostor. Tvůj trénink.',
            'start_url' => $this->app->url('/user'),
            'scope' => $this->app->url('/'),
            'display' => 'standalone',
            'background_color' => '#0b1210',
            'theme_color' => '#0b1210',
            'lang' => 'cs',
            'icons' => [
                ['src' => $this->app->url('/assets/icons/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $this->app->url('/assets/icons/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function avatar(Request $request, array $params): never
    {
        try {
            $id = (string) ($params['id'] ?? '');
            if ($id !== '' && $id !== 'guest' && preg_match('/^[0-9a-f-]{36}$/i', $id) === 1) {
                $user = $this->app->db()->fetch(
                    'SELECT avatar_path, first_name, last_name FROM users WHERE public_id = :id LIMIT 1',
                    ['id' => $id]
                );
                if ($user && !empty($user['avatar_path'])) {
                    $path = AvatarService::resolveFile((string) $user['avatar_path']);
                    if ($path) {
                        Response::file($path, AvatarService::mimeFor($path));
                    }
                }
                if ($user) {
                    $initials = mb_strtoupper(mb_substr((string) ($user['first_name'] ?? ''), 0, 1) . mb_substr((string) ($user['last_name'] ?? ''), 0, 1)) ?: 'PF';
                    $this->sendInitialsSvg($initials);
                }
            }
        } catch (\Throwable) {
            // Náhled fotky nesmí shodit stránku.
        }

        $initials = strtoupper(substr((string) $request->query('i', 'PF'), 0, 2));
        $this->sendInitialsSvg($initials);
    }

    public function uploadedAvatar(Request $request, array $params): never
    {
        try {
            $file = basename((string) ($params['file'] ?? ''));
            $path = AvatarService::resolveFile($file);
            if ($path) {
                Response::file($path, AvatarService::mimeFor($path));
            }

            $initials = 'PF';
            if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})-/i', $file, $match) === 1) {
                $user = $this->app->db()->fetch(
                    'SELECT first_name, last_name FROM users WHERE public_id = :id LIMIT 1',
                    ['id' => $match[1]]
                );
                if ($user) {
                    $initials = mb_strtoupper(mb_substr((string) ($user['first_name'] ?? ''), 0, 1) . mb_substr((string) ($user['last_name'] ?? ''), 0, 1)) ?: 'PF';
                }
            }
            $this->sendInitialsSvg($initials);
        } catch (\Throwable) {
            $this->sendInitialsSvg('PF');
        }
    }

    /** @return list<array<string, mixed>> */
    private function publicPlans(): array
    {
        $plans = (new MembershipService($this->app->db()))->plans();

        return array_values(array_filter(
            $plans,
            static fn (array $plan): bool => !in_array((string) ($plan['type'] ?? ''), ['credit', 'lifetime'], true)
        ));
    }

    private function sendInitialsSvg(string $initials): never
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128">'
            . '<rect width="128" height="128" rx="64" fill="#1c2914"/>'
            . '<text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" fill="#c6f21a" font-family="Syne, Figtree, sans-serif" font-size="44" font-weight="700">'
            . htmlspecialchars($initials, ENT_QUOTES)
            . '</text></svg>';
        header('Content-Type: image/svg+xml; charset=UTF-8');
        header('Cache-Control: public, max-age=86400');
        echo $svg;
        exit;
    }
}
