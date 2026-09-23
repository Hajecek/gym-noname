<div class="page-head"><h1>Obsah webu</h1></div>
<form method="post" class="card">
<?= csrf_field() ?>
<div class="field"><label>Hero nadpis</label><input name="hero_title" value="<?= e($hero['title'] ?? '') ?>"></div>
<div class="field"><label>Hero text</label><textarea name="hero_body"><?= e($hero['body_html'] ?? '') ?></textarea></div>
<div class="field"><label>Adresa</label><input name="address" value="<?= e($contact['address']) ?>"></div>
<div class="field"><label>E-mail</label><input name="email" value="<?= e($contact['email']) ?>"></div>
<div class="field"><label>Telefon</label><input name="phone" value="<?= e($contact['phone']) ?>"></div>
<div class="field"><label>Provozní info</label><input name="hours" value="<?= e($contact['hours']) ?>"></div>
<div class="field"><label>Mapa (iframe HTML)</label><textarea name="map_embed"><?= e($contact['map_embed']) ?></textarea></div>
<div class="field"><label>Obchodní podmínky</label><textarea name="terms"><?= e($terms['body_html'] ?? '') ?></textarea></div>
<div class="field"><label>Ochrana údajů</label><textarea name="privacy"><?= e($privacy['body_html'] ?? '') ?></textarea></div>
<button class="btn btn-primary">Uložit obsah</button>
</form>
<form method="post" action="<?= e(url('/user/sprava/obsah/faq')) ?>" class="card" style="margin-top:16px">
<?= csrf_field() ?>
<h3>Nová FAQ</h3>
<div class="field"><label>Otázka</label><input name="question" required></div>
<div class="field"><label>Odpověď</label><textarea name="answer" required></textarea></div>
<button class="btn btn-secondary">Přidat</button>
</form>
