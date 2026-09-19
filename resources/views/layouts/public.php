<?php
ob_start();
$user = $user ?? current_user();
?>
<header class="site-header">
    <div class="container inner">
        <a class="logo" href="<?= e(url('/')) ?>">
            <span class="logo-mark" aria-hidden="true"><span></span></span>
            PRIVOFIT
        </a>
        <nav class="nav-links" aria-label="Hlavní navigace">
            <a href="<?= e(url('/#jak-to-funguje')) ?>">Jak to funguje</a>
            <a href="<?= e(url('/cenik')) ?>">Ceník</a>
            <a href="<?= e(url('/faq')) ?>">FAQ</a>
            <a href="<?= e(url('/kontakt')) ?>">Kontakt</a>
        </nav>
        <div class="public-nav-cta actions">
            <?php if ($user): ?>
                <a class="btn btn-primary" href="<?= e(url('/app')) ?>">Aplikace</a>
            <?php else: ?>
                <a class="btn btn-secondary" href="<?= e(url('/prihlaseni')) ?>">Přihlásit se</a>
                <a class="btn btn-primary" href="<?= e(url('/registrace')) ?>">Rezervovat trénink</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<main id="main">
    <?php if ($msg = flash('success')): ?><div class="container"><div class="flash flash-success"><?= e($msg) ?></div></div><?php endif; ?>
    <?php if ($msg = flash('error')): ?><div class="container"><div class="flash flash-error"><?= e($msg) ?></div></div><?php endif; ?>
    <?= $content ?? '' ?>
</main>
<footer class="site-footer">
    <div class="container inner">
        <span>© <?= date('Y') ?> PRIVOFIT</span>
        <nav class="nav-links" style="display:flex">
            <a href="<?= e(url('/dokument/obchodni-podminky')) ?>">Obchodní podmínky</a>
            <a href="<?= e(url('/dokument/ochrana-udaju')) ?>">Ochrana údajů</a>
        </nav>
    </div>
</footer>
<?php
$inner = ob_get_clean();
echo \App\Core\View::renderPartial('layouts/base', array_merge(get_defined_vars(), ['content' => $inner]));
