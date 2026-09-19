<div class="page-head">
    <div>
        <p class="eyebrow">PRIVOFIT</p>
        <h1>Vítej zpět, <?= e($user['first_name']) ?>!</h1>
        <p class="muted">@<?= e($user['username']) ?> · <?= e(role_label($user['role'])) ?></p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/app/rezervace')) ?>">Rezervovat trénink</a>
</div>

<?php if ($current && $canOpen): ?>
<div class="card" style="margin-bottom:16px;border-color:var(--accent)">
    <p class="eyebrow">Právě teď</p>
    <h2>Tvůj trénink právě probíhá</h2>
    <p class="muted"><?= e(format_datetime($current['starts_at'])) ?> – <?= e(format_datetime($current['ends_at'], 'H:i')) ?></p>
    <form method="post" action="<?= e(url('/app/vstup')) ?>"><?= csrf_field() ?><button class="btn btn-primary">Otevřít dveře</button></form>
</div>
<?php endif; ?>

<div class="stat-grid">
    <article class="card"><div class="stat-label">Aktivní členství</div><div class="stat-value"><?= e($membership['plan_name'] ?? 'Žádné') ?></div></article>
    <article class="card"><div class="stat-label">Nejbližší rezervace</div><div class="stat-value"><?= $upcoming ? e(format_datetime($upcoming['starts_at'], 'd.m. H:i')) : '—' ?></div></article>
    <article class="card"><div class="stat-label">Zbývající vstupy</div><div class="stat-value"><?= $remaining === null ? 'Neomezené' : (int) $remaining ?></div></article>
    <article class="card"><div class="stat-label">Dostupnost fitka</div><div class="stat-value"><?= !empty($occupancy['occupied']) ? 'Obsazeno' : 'Volné' ?></div></article>
</div>

<?php if (!$membership): ?>
<div class="card" style="margin-top:16px">
    <h3>Nemáte aktivní členství</h3>
    <p class="muted">Vyberte tarif a rezervujte si studio, kdy se vám to hodí.</p>
    <a class="btn btn-secondary" href="<?= e(url('/app/clenstvi')) ?>">Zobrazit tarify</a>
</div>
<?php endif; ?>
