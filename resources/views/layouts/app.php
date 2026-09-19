<?php
ob_start();
$user = $user ?? current_user();
$role = $user['role'] ?? 'user';
$path = app()->request()->path();
function nav_active(string $prefix): string {
    return str_starts_with(app()->request()->path(), $prefix) ? 'active' : '';
}
?>
<div class="app-shell">
    <aside class="sidebar">
        <a class="logo" href="<?= e(url('/app')) ?>"><span class="logo-mark"><span></span></span>PRIVOFIT</a>
        <div style="margin:22px 0;display:flex;gap:10px;align-items:center">
            <img class="avatar" src="<?= e(avatar_url($user)) ?>" alt="">
            <div>
                <strong><?= e($user['first_name'] ?? '') ?></strong><br>
                <span class="muted">@<?= e($user['username'] ?? '') ?></span>
            </div>
        </div>
        <nav>
            <a class="<?= nav_active('/app') && $path === '/app' ? 'active' : '' ?>" href="<?= e(url('/app')) ?>">Domů</a>
            <a class="<?= nav_active('/app/rezervace') ?>" href="<?= e(url('/app/rezervace')) ?>">Rezervace</a>
            <a class="<?= nav_active('/app/vstup') ?>" href="<?= e(url('/app/vstup')) ?>">Vstup</a>
            <a class="<?= nav_active('/app/clenstvi') ?>" href="<?= e(url('/app/clenstvi')) ?>">Členství</a>
            <a class="<?= nav_active('/app/profil') ?>" href="<?= e(url('/app/profil')) ?>">Profil</a>
            <?php if (in_array($role, ['staff','admin','owner'], true)): ?>
                <a class="<?= nav_active('/provoz') ?>" href="<?= e(url('/provoz')) ?>">Provoz</a>
            <?php endif; ?>
            <?php if (in_array($role, ['admin','owner'], true)): ?>
                <a class="<?= nav_active('/admin') ?>" href="<?= e(url('/admin')) ?>">Administrace</a>
            <?php endif; ?>
        </nav>
        <form method="post" action="<?= e(url('/odhlaseni')) ?>" style="margin-top:24px">
            <?= csrf_field() ?>
            <button class="btn btn-secondary btn-block" type="submit">Odhlásit se</button>
        </form>
    </aside>
    <div class="app-main" id="main">
        <?php if ($msg = flash('success')): ?><div class="flash flash-success"><?= e($msg) ?></div><?php endif; ?>
        <?php if ($msg = flash('error')): ?><div class="flash flash-error"><?= e($msg) ?></div><?php endif; ?>
        <?= $content ?? '' ?>
    </div>
</div>
<nav class="bottom-nav" aria-label="Mobilní navigace">
    <a class="<?= $path === '/app' ? 'active' : '' ?>" href="<?= e(url('/app')) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 11 12 4l8 7v9H4z"/></svg>Domů</a>
    <a class="<?= nav_active('/app/rezervace') ?>" href="<?= e(url('/app/rezervace')) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg>Rezervace</a>
    <a class="<?= nav_active('/app/vstup') ?>" href="<?= e(url('/app/vstup')) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>Vstup</a>
    <a class="<?= nav_active('/app/clenstvi') ?>" href="<?= e(url('/app/clenstvi')) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="8"/></svg>Členství</a>
    <a class="<?= nav_active('/app/profil') ?>" href="<?= e(url('/app/profil')) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="8" r="3"/><path d="M5 20c1.5-4 12.5-4 14 0"/></svg>Profil</a>
</nav>
<?php
$inner = ob_get_clean();
echo \App\Core\View::renderPartial('layouts/base', array_merge(get_defined_vars(), ['content' => $inner]));
