<div class="page-head"><div><p class="eyebrow">ADMINISTRACE</p><h1>Přehled</h1></div></div>
<div class="stat-grid">
<article class="card"><div class="stat-label">Aktivní členové</div><div class="stat-value"><?= (int) $stats['active_members'] ?></div></article>
<article class="card"><div class="stat-label">Dnešní rezervace</div><div class="stat-value"><?= (int) $stats['today_reservations'] ?></div></article>
<article class="card"><div class="stat-label">Probíhající</div><div class="stat-value"><?= !empty($stats['current']['occupied']) ? 'Ano' : 'Ne' ?></div></article>
<article class="card"><div class="stat-label">Tržby 30 dní</div><div class="stat-value"><?= e(money_format_czk($stats['revenue'])) ?></div></article>
<article class="card"><div class="stat-label">Vstupy dnes</div><div class="stat-value"><?= (int) $stats['entries'] ?></div></article>
<article class="card"><div class="stat-label">Neúspěšné vstupy</div><div class="stat-value"><?= (int) $stats['failed_access'] ?></div></article>
<article class="card"><div class="stat-label">Zájem / předobjednávky</div><div class="stat-value"><a href="<?= e(url('/admin/zajem')) ?>"><?= (int) $stats['interest'] ?></a></div></article>
</div>
<div class="card" style="margin-top:16px">
<h3>Vstupní systém</h3>
<p>Poskytovatel: <?= e($stats['door']['provider'] ?? 'n/a') ?></p>
<p><?= !empty($stats['door']['test_mode']) ? 'Testovací režim (MockDoorProvider). Fyzické dveře se neotevírají.' : 'Nuki režim' ?></p>
</div>
