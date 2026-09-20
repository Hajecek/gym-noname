<?php
$brandHref = $brandHref ?? url('/');
$brandLabel = $brandLabel ?? 'PRIVOFIT';
$brandVersion = '2';
?>
<a class="brand" href="<?= e($brandHref) ?>" aria-label="<?= e($brandLabel) ?>">
    <img class="brand-mark" src="<?= e(asset('brand/logo-transparent.png')) ?>?v=<?= e($brandVersion) ?>" alt="" width="220" height="38" decoding="async">
    <img class="brand-mark-icon" src="<?= e(asset('brand/logo-icon.png')) ?>?v=<?= e($brandVersion) ?>" alt="" width="40" height="40" decoding="async">
</a>
