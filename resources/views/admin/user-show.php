<div class="page-head"><div><h1><?= e($customer['first_name'] . ' ' . $customer['last_name']) ?></h1><p class="muted">@<?= e($customer['username']) ?></p></div></div>
<div class="grid-2">
<div class="card">
<p>E-mail: <?= e($customer['email']) ?></p>
<p>Telefon: <?= e($customer['phone'] ?? '—') ?></p>
<p>Stav: <?= e($customer['status']) ?></p>
<p>Role: <?= e(role_label($customer['role'])) ?></p>
<form method="post">
<?= csrf_field() ?>
<div class="field"><label>Stav účtu</label>
<select name="status">
<?php foreach (['pending','active','blocked'] as $st): ?>
<option value="<?= $st ?>" <?= $customer['status']===$st?'selected':'' ?>><?= $st ?></option>
<?php endforeach; ?>
</select></div>
<div class="field"><label>Důvod blokace</label><input name="blocked_reason" value="<?= e($customer['blocked_reason'] ?? '') ?>"></div>
<button class="btn btn-primary">Uložit</button>
</form>
</div>
<div class="card">
<form method="post" action="<?= e(url('/admin/zakaznici/' . $customer['public_id'] . '/clenstvi')) ?>">
<?= csrf_field() ?>
<h3>Přiřadit členství</h3>
<div class="field"><label>Tarif</label>
<select name="plan_id"><?php foreach ($plans as $plan): ?><option value="<?= (int)$plan['id'] ?>"><?= e($plan['name']) ?></option><?php endforeach; ?></select>
</div>
<button class="btn btn-secondary">Přiřadit</button>
</form>
<?php if (($user['role'] ?? '') === 'owner'): ?>
<form method="post" action="<?= e(url('/admin/zakaznici/' . $customer['public_id'] . '/role')) ?>" style="margin-top:16px">
<?= csrf_field() ?>
<div class="field"><label>Role</label>
<select name="role"><?php foreach (['user','staff','admin','owner'] as $role): ?><option value="<?= $role ?>" <?= $customer['role']===$role?'selected':'' ?>><?= e(role_label($role)) ?></option><?php endforeach; ?></select>
</div>
<button class="btn btn-danger">Změnit roli</button>
</form>
<?php endif; ?>
</div>
</div>
