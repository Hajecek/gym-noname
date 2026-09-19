<?php
ob_start();
?>
<div class="auth-split">
    <aside class="auth-visual">
        <a class="logo" href="<?= e(url('/')) ?>"><span class="logo-mark"><span></span></span>PRIVOFIT</a>
        <div>
            <p class="eyebrow">Soukromé fitness studio</p>
            <h1>TVŮJ PROSTOR.<br>TVŮJ TRÉNINK.</h1>
            <p class="lead">Trénuj bez čekání, bez davu a bez kompromisů. Celé fitko jen pro tebe.</p>
        </div>
        <p class="muted">Klid. Soukromí. Moderní prostor.</p>
    </aside>
    <div class="auth-form-wrap">
        <?php if ($msg = flash('success')): ?><div class="flash flash-success"><?= e($msg) ?></div><?php endif; ?>
        <?php if ($msg = flash('error')): ?><div class="flash flash-error"><?= e($msg) ?></div><?php endif; ?>
        <?= $content ?? '' ?>
    </div>
</div>
<?php
$inner = ob_get_clean();
echo \App\Core\View::renderPartial('layouts/base', array_merge(get_defined_vars(), ['content' => $inner]));
