<?php
ob_start();
$user = $user ?? current_user();
$role = $user['role'] ?? 'user';
if (!function_exists('user_active')) {
    function user_active(string $exact, bool $prefix = false): string {
        $path = app()->request()->path();
        if ($prefix) {
            return str_starts_with($path, $exact) ? 'active' : '';
        }
        return $path === $exact ? 'active' : '';
    }
}
?>
<button class="menu-toggle" type="button" aria-label="Otevřít menu" aria-expanded="false" aria-controls="side-menu">☰</button>
<div class="side-backdrop"></div>
<aside class="side-menu" id="side-menu">
    <div class="side-head">
        <?php $brandHref = url('/user'); $brandLabel = 'PRIVOFIT – přehled'; require dirname(__DIR__) . '/partials/brand-logo.php'; ?>
    </div>
    <button class="menu-close" type="button" aria-label="Zavřít menu">✕</button>
    <nav aria-label="Uživatelská sekce">
        <a class="side-link <?= user_active('/user') ?>" href="<?= e(url('/user')) ?>" title="Domů">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M4 11 12 4l8 7v9H4z"/></svg>
            <span>Domů</span>
        </a>
        <a class="side-link <?= user_active('/user/rezervace', true) ?>" href="<?= e(url('/user/rezervace')) ?>" title="Rezervace">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg>
            <span>Rezervace</span>
        </a>
        <a class="side-link <?= user_active('/user/vstup', true) ?>" href="<?= e(url('/user/vstup')) ?>" title="Vstup">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
            <span>Vstup</span>
        </a>
        <a class="side-link <?= user_active('/user/clenstvi', true) ?>" href="<?= e(url('/user/clenstvi')) ?>" title="Členství">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l2.5 1.5"/></svg>
            <span>Členství</span>
        </a>
        <a class="side-link <?= user_active('/user/profil', true) ?> <?= user_active('/user/zabezpeceni', true) ?>" href="<?= e(url('/user/profil')) ?>" title="Profil">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M5 20c1.5-4 12.5-4 14 0"/></svg>
            <span>Profil</span>
        </a>
        <?php if (in_array($role, ['staff', 'admin', 'owner'], true)): ?>
            <a class="side-link <?= user_active('/provoz', true) ?>" href="<?= e(url('/provoz')) ?>" title="Provoz">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M4 7h16M4 12h10M4 17h13"/></svg>
                <span>Provoz</span>
            </a>
        <?php endif; ?>
        <?php if (in_array($role, ['admin', 'owner'], true)): ?>
            <a class="side-link <?= user_active('/admin', true) ?>" href="<?= e(url('/admin')) ?>" title="Admin">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M12 3 20 7v5c0 5-3.5 8.5-8 9-4.5-.5-8-4-8-9V7z"/></svg>
                <span>Admin</span>
            </a>
        <?php endif; ?>
    </nav>
    <?php require dirname(__DIR__) . '/partials/side-profile.php'; ?>
</aside>
<div class="app-main" id="main">
    <?php if ($msg = flash('success')): ?><div class="flash flash-success" role="status"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash('error')): ?><div class="flash flash-error" role="alert"><?= e($msg) ?></div><?php endif; ?>
    <?= $content ?? '' ?>
</div>
<?php
$inner = ob_get_clean();
echo \App\Core\View::renderPartial('layouts/base', array_merge(get_defined_vars(), ['content' => $inner, 'bodyClass' => 'user-app']));
