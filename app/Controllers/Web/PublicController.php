<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\Controller;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Services\Content\ContentService;
use App\Services\MembershipService;
use App\Support\Clock;

final class PublicController extends Controller
{
    public function home(Request $request): never
    {
        $content = new ContentService($this->app->db());
        $plans = (new MembershipService($this->app->db()))->plans();
        $this->publicView('public/home', [
            'title' => 'PRIVOFIT – Tvoje fitko. Tvůj prostor.',
            'hero' => $content->page('home.hero', 'Tvoje fitko. Tvůj prostor.', 'Trénuj bez čekání na stroje, bez přeplněných prostor a bez kompromisů.'),
            'faqs' => $content->faqs(),
            'plans' => $plans,
            'equipment' => $content->equipment(),
            'media' => $content->media(),
            'contact' => $content->contact(),
        ]);
    }

    public function pricing(Request $request): never
    {
        $plans = (new MembershipService($this->app->db()))->plans();
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

    public function legal(Request $request, array $params): never
    {
        $slug = $params['slug'] ?? 'obchodni-podminky';
        $page = (new ContentService($this->app->db()))->page($slug, 'Dokument');
        $this->publicView('public/legal', [
            'title' => $page['title'],
            'page' => $page,
        ]);
    }

    public function manifest(): never
    {
        header('Content-Type: application/manifest+json; charset=UTF-8');
        echo json_encode([
            'name' => 'PRIVOFIT',
            'short_name' => 'PRIVOFIT',
            'description' => 'Soukromé fitness studio. Tvůj prostor. Tvůj trénink.',
            'start_url' => $this->app->url('/app'),
            'scope' => $this->app->url('/'),
            'display' => 'standalone',
            'background_color' => '#0B1220',
            'theme_color' => '#0B1220',
            'lang' => 'cs',
            'icons' => [
                ['src' => $this->app->url('/assets/icons/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => $this->app->url('/assets/icons/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png'],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function avatar(Request $request, array $params): never
    {
        $initials = strtoupper(substr((string) $request->query('i', 'PF'), 0, 2));
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128">'
            . '<rect width="128" height="128" rx="64" fill="#1A2940"/>'
            . '<text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" fill="#42E8B4" font-family="Manrope, Inter, sans-serif" font-size="44" font-weight="700">'
            . htmlspecialchars($initials, ENT_QUOTES)
            . '</text></svg>';
        header('Content-Type: image/svg+xml; charset=UTF-8');
        header('Cache-Control: public, max-age=86400');
        echo $svg;
        exit;
    }
}
