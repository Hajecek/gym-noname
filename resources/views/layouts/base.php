<?php
$user = $user ?? current_user();
$cspNonce = $cspNonce ?? '';
$title = $title ?? 'PRIVOFIT';
?><!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0B1220">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title) ?> · PRIVOFIT</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <link rel="manifest" href="<?= e(url('/manifest.json')) ?>">
    <link rel="icon" href="<?= e(asset('icons/icon.svg')) ?>" type="image/svg+xml">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="PRIVOFIT">
</head>
<body>
<a class="skip-link" href="#main">Přeskočit na obsah</a>
<?= $content ?? '' ?>
<script nonce="<?= e($cspNonce) ?>" src="<?= e(asset('js/app.js')) ?>"></script>
<script nonce="<?= e($cspNonce) ?>">
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('<?= e(url('/service-worker.js')) ?>').catch(() => {});
}
</script>
</body>
</html>
