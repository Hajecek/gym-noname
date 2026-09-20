<?php
$user = $user ?? current_user();
$cspNonce = $cspNonce ?? '';
$title = $title ?? 'PRIVOFIT';
$page = $page ?? 'inner';
$bodyClass = $bodyClass ?? ($page === 'register' ? 'standalone-registration' : '');
$basePath = rtrim((string) app()->request()->basePath(), '/');
$dataBase = $basePath === '' ? '/' : $basePath . '/';
$description = $description ?? 'Objev soukromé fitness PRIVOFIT. Prostor pro tvůj trénink, tvoje tempo a tvoje lepší já.';
$hideChrome = $hideChrome ?? str_contains((string) $bodyClass, 'standalone-');
?>
<!doctype html>
<html lang="cs" data-base="<?= e($dataBase) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#0b1210">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <link rel="icon" href="<?= e(url('/assets/marketing/favicon.svg')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('/assets/marketing/style.css')) ?>?v=13">
    <link rel="manifest" href="<?= e(url('/manifest.json')) ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="PRIVOFIT">
</head>
<body class="<?= e($bodyClass) ?>" data-page="<?= e($page) ?>"<?= !empty($mfa) ? ' data-mfa="1"' : '' ?>>
<a class="skip-link" href="#main">Přeskočit na obsah</a>
<div class="scroll-progress" aria-hidden="true"></div>
<header class="header"<?= $hideChrome ? ' hidden' : '' ?>>
    <a class="brand" href="<?= e(url('/')) ?>" aria-label="PRIVOFIT – úvod"><span class="brand-icon">p<span></span></span>privo<span class="brand-light">fit</span><span class="brand-dot">®</span></a>
    <nav aria-label="Hlavní navigace">
        <a href="<?= e(url('/#prostor')) ?>">Proč PRIVOFIT</a>
        <a href="<?= e(url('/#jak-to-funguje')) ?>">Jak to funguje</a>
        <a href="<?= e(url('/#vstup')) ?>">Vstup do fitka</a>
        <a href="<?= e(url('/#otazky')) ?>">Otázky</a>
        <a href="<?= e(url('/zajem')) ?>">Zájem</a>
    </nav>
    <div class="nav-actions">
        <?php if ($user): ?>
            <a href="<?= e(url('/app')) ?>" class="login-link">Aplikace</a>
            <a href="<?= e(url('/app')) ?>" class="button button-small">Pokračovat <span>↗</span></a>
        <?php else: ?>
            <a href="<?= e(url('/prihlaseni')) ?>" class="login-link">Přihlášení</a>
            <a href="<?= e(url('/registrace')) ?>" class="button button-small">Začít po svém <span>↗</span></a>
        <?php endif; ?>
    </div>
    <button class="menu-toggle" aria-label="Otevřít menu" aria-expanded="false">☰</button>
</header>
<?php if ($msg = flash('success')): ?><div class="flash flash-success wrapper" role="status"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash('error')): ?><div class="flash flash-error wrapper" role="alert"><?= e($msg) ?></div><?php endif; ?>
<div id="main">
<?= $content ?? '' ?>
</div>
<footer class="wrapper"<?= $hideChrome ? ' hidden' : '' ?>>
    <div class="footer-top">
        <a class="brand" href="<?= e(url('/')) ?>"><span class="brand-icon">p<span></span></span>privo<span class="brand-light">fit</span><span class="brand-dot">®</span></a>
        <p>Tvůj prostor. Tvoje tempo. Tvoje pravidla.</p>
        <a href="#" class="back-top">Zpátky nahoru ↑</a>
    </div>
    <div class="footer-bottom">
        <span>© <span id="year"><?= date('Y') ?></span> PRIVOFIT</span>
        <span>MADE FOR YOUR NEXT MOVE.</span>
    </div>
</footer>
<script nonce="<?= e($cspNonce) ?>" src="<?= e(url('/assets/marketing/app.js')) ?>?v=8"></script>
<script type="module" nonce="<?= e($cspNonce) ?>">
const sceneUrl = <?= json_encode(url('/assets/marketing/scene.js') . '?v=8', JSON_UNESCAPED_SLASHES) ?>;
const decorUrl = <?= json_encode(url('/assets/marketing/decor.js') . '?v=8', JSON_UNESCAPED_SLASHES) ?>;
const phoneUrl = <?= json_encode(url('/assets/marketing/phone.js') . '?v=8', JSON_UNESCAPED_SLASHES) ?>;
const welcomeUrl = <?= json_encode(url('/assets/marketing/welcome.js') . '?v=8', JSON_UNESCAPED_SLASHES) ?>;
const isLoginPage = document.body.dataset.page === "login";
if (document.getElementById("three-stage")) {
  import(sceneUrl).catch(() => {
    const loading = document.getElementById("scene-loading");
    const hint = document.getElementById("scene-hint");
    if (loading) loading.textContent = "3D náhled není dostupný.";
    if (hint) hint.textContent = "Tvůj prostor. Tvoje tempo.";
  });
}
if (document.getElementById("phone-stage")) {
  import(phoneUrl).catch(() => {
    const loading = document.getElementById("phone-loading");
    const status = document.getElementById("phone-status");
    if (loading) loading.textContent = "3D telefon není v tomto prohlížeči dostupný.";
    if (status) status.textContent = "Ukázku můžeš otevřít v prohlížeči s podporou WebGL.";
    document.querySelectorAll(".phone-actions button").forEach((b) => (b.disabled = true));
  });
}
if (document.getElementById("peek-stage") || (document.getElementById("auth-3d") && !isLoginPage)) {
  import(decorUrl).catch(() => {
    const loading = document.getElementById("auth-3d-loading");
    if (loading) loading.textContent = "Tvůj nový začátek.";
  });
}
if (document.getElementById("auth-3d") && isLoginPage) {
  import(welcomeUrl).catch(() => {
    const loading = document.getElementById("auth-3d-loading");
    if (loading) loading.textContent = "Tvůj nový začátek.";
  });
}
</script>
</body>
</html>
