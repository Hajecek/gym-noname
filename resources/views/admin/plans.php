<div class="page-head"><h1>Členství a ceník</h1></div>
<?php foreach ($plans as $plan): ?>
<form class="card" method="post" style="margin-bottom:12px">
<?= csrf_field() ?>
<input type="hidden" name="id" value="<?= (int)$plan['id'] ?>">
<div class="row-2">
<div class="field"><label>Název</label><input name="name" value="<?= e($plan['name']) ?>"></div>
<div class="field"><label>Cena</label><input name="price" value="<?= e($plan['price']) ?>"></div>
</div>
<div class="field"><label>Popis</label><textarea name="description"><?= e($plan['description'] ?? '') ?></textarea></div>
<div class="row-2">
<div class="field"><label>Typ</label>
<select name="type"><?php foreach (['single','pack','monthly','credit','voucher'] as $t): ?><option value="<?= $t ?>" <?= $plan['type']===$t?'selected':'' ?>><?= $t ?></option><?php endforeach; ?></select>
</div>
<div class="field"><label>Vstupy</label><input name="entries" value="<?= e((string)($plan['entries'] ?? '')) ?>"></div>
</div>
<label class="check"><input type="checkbox" name="is_active" <?= $plan['is_active']?'checked':'' ?>> Aktivní</label>
<button class="btn btn-primary">Uložit tarif</button>
</form>
<?php endforeach; ?>
<form class="card" method="post">
<?= csrf_field() ?>
<h3>Nový tarif</h3>
<div class="field"><label>Název</label><input name="name" required></div>
<div class="field"><label>Cena</label><input name="price" required></div>
<div class="field"><label>Typ</label><select name="type"><option value="single">Jednorázový</option><option value="pack">Balíček</option><option value="monthly">Měsíční</option></select></div>
<button class="btn btn-secondary">Vytvořit</button>
</form>
