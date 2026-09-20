<?php
ob_start();
$user = $user ?? current_user();
$path = app()->request()->path();
if (!function_exists('admin_active')) {
    function admin_active(string $prefix): string {
        return str_starts_with(app()->request()->path(), $prefix) ? 'active' : '';
    }
}
?>
<button class="menu-toggle" type="button" aria-label="Otevřít menu" aria-expanded="false" aria-controls="side-menu">☰</button>
<div class="side-backdrop"></div>
<aside class="side-menu" id="side-menu">
    <div class="side-head">
        <div class="brand-lockup">
            <?php $brandHref = url('/admin'); $brandLabel = 'PRIVOFIT – administrace'; require dirname(__DIR__) . '/partials/brand-logo.php'; ?>
            <span class="admin-tag">ADMIN</span>
        </div>
    </div>
    <button class="menu-close" type="button" aria-label="Zavřít menu">✕</button>
    <nav aria-label="Administrace">
        <a class="side-link <?= $path === '/admin' ? 'active' : '' ?>" href="<?= e(url('/admin')) ?>" title="Přehled">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M4 11 12 4l8 7v9H4z"/></svg>
            <span>Přehled</span>
        </a>
        <a class="side-link <?= admin_active('/admin/zakaznici') ?>" href="<?= e(url('/admin/zakaznici')) ?>" title="Zákazníci">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="9" cy="8" r="3"/><circle cx="16" cy="9" r="2.5"/><path d="M3 19c1.2-3.2 10.8-3.2 12 0M14 19c.4-2 5.6-2.4 7 0"/></svg>
            <span>Zákazníci</span>
        </a>
        <a class="side-link <?= admin_active('/admin/rezervace') ?>" href="<?= e(url('/admin/rezervace')) ?>" title="Rezervace">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg>
            <span>Rezervace</span>
        </a>
        <a class="side-link <?= admin_active('/admin/clenstvi') ?>" href="<?= e(url('/admin/clenstvi')) ?>" title="Členství">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l2.5 1.5"/></svg>
            <span>Členství</span>
        </a>
        <a class="side-link <?= admin_active('/admin/obsah') ?>" href="<?= e(url('/admin/obsah')) ?>" title="Obsah">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><rect x="5" y="4" width="14" height="16" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
            <span>Obsah</span>
        </a>
        <a class="side-link <?= admin_active('/admin/vstup') ?>" href="<?= e(url('/admin/vstup')) ?>" title="Vstup">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
            <span>Vstup</span>
        </a>
        <a class="side-link <?= admin_active('/admin/zajem') ?>" href="<?= e(url('/admin/zajem')) ?>" title="Zájem">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg>
            <span>Zájem</span>
        </a>
        <?php if (($user['role'] ?? '') === 'owner'): ?>
            <a class="side-link" href="<?= e(url('/adminer.php')) ?>" title="Adminer" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><ellipse cx="12" cy="6" rx="7" ry="3"/><path d="M5 6v6c0 1.7 3.1 3 7 3s7-1.3 7-3V6M5 12v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6"/></svg>
                <span>Adminer</span>
            </a>
            <a class="side-link <?= admin_active('/admin/nastaveni') ?>" href="<?= e(url('/admin/nastaveni')) ?>" title="Nastavení">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 4v2M12 18v2M4 12h2M18 12h2M6.3 6.3l1.4 1.4M16.3 16.3l1.4 1.4M6.3 17.7l1.4-1.4M16.3 7.7l1.4-1.4"/></svg>
                <span>Nastavení</span>
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
echo \App\Core\View::renderPartial('layouts/base', array_merge(get_defined_vars(), ['content' => $inner, 'bodyClass' => 'admin-app']));
