<div class="page-head">
    <div>
        <p class="eyebrow">TVŮJ PROSTOR</p>
        <h1>Ahoj, <?= e($user['first_name']) ?>.</h1>
        <p class="muted">@<?= e($user['username']) ?> · <?= e(role_label($user['role'])) ?></p>
    </div>
    <a class="button" href="<?= e(url('/user/rezervace')) ?>">Rezervovat trénink <span>↗</span></a>
</div>

<?php if ($current && $canOpen): ?>
<div class="card door-card" style="margin-bottom:16px">
    <p class="eyebrow">Právě teď</p>
    <h2>Tvůj trénink právě probíhá</h2>
    <p class="muted"><?= e(format_datetime($current['starts_at'])) ?> – <?= e(format_datetime($current['ends_at'], 'H:i')) ?></p>
    <form method="post" action="<?= e(url('/user/vstup')) ?>" style="margin-top:16px"><?= csrf_field() ?><button class="btn btn-primary">Otevřít dveře</button></form>
</div>
<?php endif; ?>

<div class="stat-grid">
    <article class="card"><div class="stat-label">Členství</div><div class="stat-value"><?= e($membership['plan_name'] ?? 'Žádné') ?></div></article>
    <article class="card"><div class="stat-label">Nejbližší rezervace</div><div class="stat-value"><?= $upcoming ? e(format_datetime($upcoming['starts_at'], 'd.m. H:i')) : '—' ?></div></article>
    <article class="card"><div class="stat-label">Zbývající vstupy</div><div class="stat-value"><?= $remaining === null ? 'Neomezené' : (int) $remaining ?></div></article>
    <article class="card"><div class="stat-label">Fitko</div><div class="stat-value"><?= !empty($occupancy['occupied']) ? 'Obsazeno' : 'Volné' ?></div></article>
</div>

<?php if (!$membership): ?>
<div class="card" style="margin-top:16px">
    <h3>Zatím nemáš aktivní členství</h3>
    <p class="muted">Vyber si tarif a rezervuj si studio, kdy se ti to hodí.</p>
    <a class="btn btn-secondary" href="<?= e(url('/user/clenstvi')) ?>" style="margin-top:14px">Zobrazit tarify</a>
</div>
<?php endif; ?>
