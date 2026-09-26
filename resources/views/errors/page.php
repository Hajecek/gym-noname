<?php
$status = (int) ($status ?? 500);
$debug = !empty($debug);
$rawMessage = trim((string) ($message ?? ''));
$generic = [
    '',
    'Došlo k chybě.',
    'Došlo k neočekávané chybě.',
    'Došlo k neočekávané chybě. Zkuste to prosím později.',
    'Došlo k neočekávané chybě. Zkus to prosím později.',
];
$technical = $debug && $status >= 500 && !in_array($rawMessage, $generic, true);

$presets = [
    401 => [
        'eyebrow' => 'Přihlášení',
        'title' => 'Nejsi přihlášený.',
        'lead' => 'Pro tuhle stránku se nejdřív přihlas.',
    ],
    403 => [
        'eyebrow' => 'Přístup',
        'title' => 'Sem to nejde.',
        'lead' => 'K téhle části nemáš oprávnění.',
    ],
    404 => [
        'eyebrow' => 'Adresa',
        'title' => 'Špatná adresa.',
        'lead' => 'Odkaz nesedí. Vrať se zpátky, nebo otevři úvod.',
    ],
    422 => [
        'eyebrow' => 'Požadavek',
        'title' => 'Tohle nešlo dokončit.',
        'lead' => 'Zkontroluj údaje a zkus to ještě jednou.',
    ],
    429 => [
        'eyebrow' => 'Tempo',
        'title' => 'Moc rychle.',
        'lead' => 'Počkej chvíli a zkus to znovu.',
    ],
    503 => [
        'eyebrow' => 'Nedostupné',
        'title' => 'Teď to nejde.',
        'lead' => 'Služba je chvíli mimo. Zkus to znovu za moment.',
    ],
];
$copy = $presets[$status] ?? [
    'eyebrow' => 'Chyba',
    'title' => 'Něco se pokazilo.',
    'lead' => 'Akci se nepodařilo dokončit. Zkus to za chvíli znovu.',
];
if (!$technical && $rawMessage !== '' && $status !== 404) {
    $copy['lead'] = $rawMessage;
}

$tone = match (true) {
    in_array($status, [401, 403], true) => 'denied',
    $status >= 500 => 'broken',
    default => 'calm',
};

try {
    $account = current_user();
} catch (\Throwable) {
    $account = null;
}

if ($status === 401) {
    $homeHref = url('/prihlaseni');
    $homeLabel = 'Přihlásit se';
} elseif ($account) {
    $homeHref = url('/user');
    $homeLabel = 'Zpět do aplikace';
} else {
    $homeHref = url('/');
    $homeLabel = 'Zpět na web';
}

$backHref = '';
$referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
$parts = is_string($referer) && $referer !== '' ? parse_url($referer) : false;
$ownHost = strtolower((string) preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
$refHost = strtolower((string) (is_array($parts) ? ($parts['host'] ?? '') : ''));
if (is_array($parts) && $refHost === $ownHost && !empty($parts['path'])) {
    $candidate = (string) $parts['path'];
    if (!empty($parts['query'])) {
        $candidate .= '?' . $parts['query'];
    }
    $current = (string) ($_SERVER['REQUEST_URI'] ?? '');
    if ($candidate !== $current && $candidate !== $homeHref) {
        $backHref = $candidate;
    }
}
?>
<main class="site-error is-<?= e($tone) ?>">
    <section class="site-error-card" aria-labelledby="site-error-title">
        <div class="site-error-brand">
            <?php $brandHref = url('/'); $brandLabel = 'PRIVOFIT'; require dirname(__DIR__) . '/partials/brand-logo.php'; ?>
        </div>

        <p class="site-error-code" aria-hidden="true"><?= e((string) $status) ?></p>
        <p class="site-error-eyebrow"><?= e($copy['eyebrow']) ?></p>
        <h1 id="site-error-title"><?= e($copy['title']) ?></h1>
        <p class="site-error-lead"><?= e($copy['lead']) ?></p>

        <?php if ($technical): ?>
        <div class="site-error-detail">
            <span>Technický detail</span>
            <p><?= e($rawMessage) ?></p>
        </div>
        <?php endif; ?>

        <div class="site-error-actions">
            <a class="button" href="<?= e($homeHref) ?>"><?= e($homeLabel) ?> <span>↗</span></a>
            <?php if ($backHref !== ''): ?>
            <a class="site-error-link" href="<?= e($backHref) ?>">Zpět</a>
            <?php elseif ($status !== 401): ?>
            <a class="site-error-link" href="<?= e($account ? url('/') : url('/prihlaseni')) ?>"><?= $account ? 'Na úvod webu' : 'Přihlásit se' ?></a>
            <?php endif; ?>
        </div>
    </section>
</main>
