<div class="page-head">
    <div>
        <p class="eyebrow">SPRÁVA</p>
        <h1>Přehled</h1>
        <p class="muted">Rychlý stav studia, rezervací a vstupů.</p>
    </div>
</div>
<div class="stat-grid">
    <article class="card"><div class="stat-label">Aktivní členové</div><div class="stat-value"><?= (int) $stats['active_members'] ?></div></article>
    <article class="card"><div class="stat-label">Dnešní rezervace</div><div class="stat-value"><?= (int) $stats['today_reservations'] ?></div></article>
    <article class="card"><div class="stat-label">Probíhající</div><div class="stat-value"><?= !empty($stats['current']['occupied']) ? 'Ano' : 'Ne' ?></div></article>
    <article class="card"><div class="stat-label">Tržby 30 dní</div><div class="stat-value"><?= e(money_format_czk($stats['revenue'])) ?></div></article>
    <article class="card"><div class="stat-label">Vstupy dnes</div><div class="stat-value"><?= (int) $stats['entries'] ?></div></article>
    <article class="card"><div class="stat-label">Neúspěšné vstupy</div><div class="stat-value"><?= (int) $stats['failed_access'] ?></div></article>
    <a class="card" href="<?= e(url('/user/sprava/zajem')) ?>"><div class="stat-label">Zájem</div><div class="stat-value"><?= (int) $stats['interest'] ?></div></a>
</div>
<section class="card" style="margin-top:16px">
    <p class="eyebrow">Vstupní systém</p>
    <h2 style="margin:6px 0 8px">Dveře</h2>
    <p class="muted" style="margin:0">Poskytovatel: <?= e($stats['door']['provider'] ?? 'n/a') ?> · <?= !empty($stats['door']['test_mode']) ? 'Testovací režim (dveře se fyzicky neotevírají).' : 'Nuki režim' ?></p>
</section>
