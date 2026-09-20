<div class="page-head"><div><p class="eyebrow">TARIFY</p><h1>Členství</h1></div></div>
<?php if ($current): ?>
<div class="card" style="margin-bottom:16px">
    <h3><?= e($current['plan_name']) ?></h3>
    <p class="muted">Stav: <?= e($current['status']) ?> · Zbývá vstupů: <?= $current['entries_remaining'] === null ? 'neomezeno' : (int) $current['entries_remaining'] ?></p>
    <?php if ($current['ends_at']): ?><p class="muted">Platí do <?= e(format_datetime($current['ends_at'], 'd. m. Y')) ?></p><?php endif; ?>
</div>
<?php endif; ?>
<div class="grid-3">
    <?php foreach ($plans as $plan): ?>
    <article class="card">
        <h3><?= e($plan['name']) ?></h3>
        <p class="stat-value"><?= e(money_format_czk($plan['price'])) ?></p>
        <p class="muted"><?= e($plan['description'] ?? '') ?></p>
        <p class="muted">Online platba bude napojená po výběru brány. Do té doby tarif přidělí administrátor.</p>
    </article>
    <?php endforeach; ?>
</div>
