<section class="section"><div class="container grid-2">
<div>
<h1>Kontakt</h1>
<p><?= e($contact['address']) ?></p>
<p class="muted"><?= e($contact['email']) ?></p>
<p class="muted"><?= e($contact['phone']) ?></p>
<p class="muted"><?= e($contact['hours']) ?></p>
<?php if (!empty($contact['map_embed'])): ?>
<div class="card"><?= $contact['map_embed'] ?></div>
<?php endif; ?>
</div>
<form class="card" method="post">
<?= csrf_field() ?>
<div class="field"><label>Jméno</label><input name="name" required></div>
<div class="field"><label>E-mail</label><input type="email" name="email" required></div>
<div class="field"><label>Telefon</label><input name="phone"></div>
<div class="field"><label>Zpráva</label><textarea name="message" required></textarea></div>
<button class="btn btn-primary btn-block">Odeslat</button>
</form>
</div></section>
