<?php
ob_start();
$path = app()->request()->path();
function admin_active(string $prefix): string {
    return str_starts_with(app()->request()->path(), $prefix) ? 'active' : '';
}
?>
<div class="app-shell">
    <aside class="sidebar">
        <a class="logo" href="<?= e(url('/admin')) ?>"><span class="logo-mark"><span></span></span>PRIVOFIT</a>
        <p class="muted" style="margin:16px 0">Administrace</p>
        <nav>
            <a class="<?= $path === '/admin' ? 'active' : '' ?>" href="<?= e(url('/admin')) ?>">Přehled</a>
            <a class="<?= admin_active('/admin/zakaznici') ?>" href="<?= e(url('/admin/zakaznici')) ?>">Zákazníci</a>
            <a class="<?= admin_active('/admin/rezervace') ?>" href="<?= e(url('/admin/rezervace')) ?>">Rezervace</a>
            <a class="<?= admin_active('/admin/clenstvi') ?>" href="<?= e(url('/admin/clenstvi')) ?>">Členství</a>
            <a class="<?= admin_active('/admin/obsah') ?>" href="<?= e(url('/admin/obsah')) ?>">Obsah</a>
            <a class="<?= admin_active('/admin/vstup') ?>" href="<?= e(url('/admin/vstup')) ?>">Vstupní systém</a>
            <a class="<?= admin_active('/admin/zajem') ?>" href="<?= e(url('/admin/zajem')) ?>">Předobjednávky</a>
            <?php if (($user['role'] ?? '') === 'owner'): ?>
                <a class="<?= admin_active('/admin/nastaveni') ?>" href="<?= e(url('/admin/nastaveni')) ?>">Nastavení</a>
            <?php endif; ?>
            <a href="<?= e(url('/app')) ?>">Zpět do aplikace</a>
        </nav>
    </aside>
    <div class="app-main" id="main">
        <?php if ($msg = flash('success')): ?><div class="flash flash-success"><?= e($msg) ?></div><?php endif; ?>
        <?php if ($msg = flash('error')): ?><div class="flash flash-error"><?= e($msg) ?></div><?php endif; ?>
        <?= $content ?? '' ?>
    </div>
</div>
<?php
$inner = ob_get_clean();
echo \App\Core\View::renderPartial('layouts/base', array_merge(get_defined_vars(), ['content' => $inner]));
