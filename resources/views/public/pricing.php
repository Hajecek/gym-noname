<section class="section"><div class="container">
<h1>Ceník</h1>
<p class="lead">Zaplatit jde až z přihlášeného účtu. Nepřihlášený návštěvník se nejdřív přihlásí.</p>
<div class="grid-3">
<?php foreach ($plans as $plan): ?>
<article class="card">
<h3><?= e($plan['name']) ?></h3>
<p class="stat-value"><?= e(money_format_czk($plan['price'])) ?></p>
<p class="muted">Platba se spustí až po přihlášení.</p>
<?php if ($user): ?>
<form method="post" action="<?= e(url('/user/clenstvi')) ?>">
<?= csrf_field() ?>
<input type="hidden" name="plan" value="<?= e($plan['public_id']) ?>">
<button class="btn btn-primary" type="submit">Zaplatit</button>
</form>
<?php else: ?>
<a class="btn btn-primary" href="<?= e(url('/user/clenstvi')) ?>">Přihlas se a zaplať</a>
<?php endif; ?>
</article>
<?php endforeach; ?>
</div>
</div></section>
