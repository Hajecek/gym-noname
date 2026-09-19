<section class="section"><div class="container">
<h1>Ceník</h1>
<p class="lead">Aktuální tarify načtené z databáze. Ceny lze kdykoli upravit v administraci.</p>
<div class="grid-3">
<?php foreach ($plans as $plan): ?>
<article class="card">
<h3><?= e($plan['name']) ?></h3>
<p class="stat-value"><?= e(money_format_czk($plan['price'])) ?></p>
<p class="muted"><?= e($plan['description'] ?? '') ?></p>
<a class="btn btn-primary" href="<?= e(url('/registrace')) ?>">Rezervovat</a>
</article>
<?php endforeach; ?>
</div>
</div></section>
