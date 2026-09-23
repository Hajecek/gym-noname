<?php
$studio = is_array($studio ?? null) ? $studio : [
    'state' => 'open',
    'dot' => 'green',
    'label' => 'Volné',
    'detail' => 'Stav studia se načítá.',
    'room' => 'Studio',
];
$dot = (string) ($studio['dot'] ?? 'green');
$headline = match ($dot) {
    'orange' => (string) ($studio['label'] ?? 'Blíží se termín'),
    'red' => 'Fitko uzavřené',
    default => 'Fitko volné',
};
?>
<div class="page-head">
    <div>
        <p class="eyebrow">TVŮJ PROSTOR</p>
        <h1>Ahoj, <?= e($user['first_name']) ?>.</h1>
        <p class="muted">@<?= e($user['username']) ?> · <?= e(role_label($user['role'])) ?></p>
    </div>
    <div class="page-head-actions">
        <button
            type="button"
            class="studio-pulse"
            data-studio-pulse
            data-studio-url="<?= e(url('/user/stav-studia')) ?>"
            data-state="<?= e((string) ($studio['state'] ?? 'open')) ?>"
            aria-haspopup="dialog"
            aria-controls="studio-pulse-modal"
        >
            <i class="studio-pulse-dot is-<?= e($dot) ?>" data-studio-dot aria-hidden="true"></i>
            <span data-studio-label><?= e((string) ($studio['label'] ?? 'Volné')) ?></span>
        </button>
        <a class="button" href="<?= e(url('/user/rezervace')) ?>">Rezervovat trénink <span>↗</span></a>
    </div>
</div>

<div class="cancel-modal" id="studio-pulse-modal" data-studio-modal hidden>
    <div class="cancel-modal-backdrop" data-studio-close tabindex="-1"></div>
    <div class="cancel-modal-panel studio-pulse-panel is-<?= e($dot) ?>" data-studio-panel role="dialog" aria-modal="true" aria-labelledby="studio-pulse-title">
        <div class="studio-pulse-status">
            <i class="studio-pulse-dot is-<?= e($dot) ?>" data-studio-banner-dot aria-hidden="true"></i>
            <span>Stav teď</span>
        </div>
        <h2 id="studio-pulse-title" data-studio-title><?= e($headline) ?></h2>
        <p data-studio-detail><?= e((string) ($studio['detail'] ?? '')) ?></p>
        <dl class="studio-pulse-facts">
            <div>
                <dt>Studio</dt>
                <dd data-studio-room><?= e((string) ($studio['room'] ?? 'Studio')) ?></dd>
            </div>
            <div>
                <dt>Otevírací doba</dt>
                <dd data-studio-hours><?php
                    $open = (string) ($studio['opens_at'] ?? '');
                    $close = (string) ($studio['closes_at'] ?? '');
                    echo e(($open !== '' && $close !== '') ? ($open . '–' . $close) : '—');
                ?></dd>
            </div>
            <div>
                <dt data-studio-next-label>Další termín</dt>
                <dd data-studio-next><?= e((string) ($studio['next_at'] ?? '—')) ?></dd>
            </div>
        </dl>
        <div class="cancel-actions">
            <button type="button" class="btn studio-pulse-close" data-studio-close>Zavřít</button>
        </div>
    </div>
</div>

<?php if ($current && $canOpen): ?>
<div class="card door-card" style="margin-bottom:16px">
    <p class="eyebrow">Právě teď</p>
    <h2>Tvůj trénink právě probíhá</h2>
    <p class="muted"><?= e(format_datetime($current['starts_at'])) ?> – <?= e(format_datetime($current['ends_at'], 'H:i')) ?></p>
    <form method="post" action="<?= e(url('/user/vstup')) ?>" style="margin-top:16px"><?= csrf_field() ?><button class="btn btn-primary">Otevřít dveře</button></form>
</div>
<?php endif; ?>

<?php if ($membership): ?>
<section class="member-hero card">
    <div>
        <p class="eyebrow">Tvoje členství</p>
        <h2><?= e($membership['plan_name']) ?></h2>
        <p class="member-count"><?php
            if ($remaining === null) {
                echo 'Neomezené vstupy';
            } else {
                $left = (int) $remaining;
                $unit = $left === 1 ? 'vstup' : ($left >= 2 && $left <= 4 ? 'vstupy' : 'vstupů');
                echo e($left . ' ' . $unit);
            }
        ?></p>
        <p class="muted"><?= $membership['ends_at'] ? 'Platí do ' . e(format_datetime($membership['ends_at'], 'd. m. Y')) . '. ' : '' ?>Další den si jen rezervuješ, platba se nestrhává.</p>
    </div>
    <a class="button" href="<?= e(url('/user/rezervace')) ?>">Vybrat den <span>↗</span></a>
</section>
<?php endif; ?>

<div class="stat-grid">
    <a class="card" href="<?= e(url('/user/clenstvi')) ?>"><div class="stat-label">Členství</div><div class="stat-value"><?= e($membership['plan_name'] ?? 'Žádné') ?></div></a>
    <a class="card" href="<?= e(url('/user/moje-rezervace')) ?>"><div class="stat-label">Nejbližší rezervace</div><div class="stat-value"><?= $upcoming ? e(format_datetime($upcoming['starts_at'], 'd.m. H:i')) : '—' ?></div></a>
    <a class="card" href="<?= e(url('/user/clenstvi')) ?>"><div class="stat-label">Zbývající vstupy</div><div class="stat-value"><?= $remaining === null ? 'Neomezené' : (int) $remaining ?></div></a>
</div>

<?php if (!$membership): ?>
<div class="card" style="margin-top:16px">
    <h3>Zatím nemáš aktivní členství</h3>
    <p class="muted">Vyber si tarif, zaplať ho a pak si rezervuj jen den. Vstup se odečte sám.</p>
    <a class="btn btn-secondary" href="<?= e(url('/user/clenstvi')) ?>" style="margin-top:14px">Vybrat tarif</a>
</div>
<?php endif; ?>
