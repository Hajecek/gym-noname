<div class="page-head"><h1>Rezervace a provozní doba</h1></div>
<form method="get" class="card"><div class="field"><label>Datum</label><input type="date" name="date" value="<?= e($date) ?>" onchange="this.form.submit()"></div></form>
<div class="table-wrap card" style="margin-top:16px">
<table><thead><tr><th>Čas</th><th>Zákazník</th><th>Stav</th></tr></thead>
<tbody><?php foreach ($today as $row): ?><tr><td><?= e(format_datetime($row['starts_at'])) ?></td><td><?= e($row['first_name'].' '.$row['last_name']) ?></td><td><?= e($row['status']) ?></td></tr><?php endforeach; ?></tbody>
</table></div>
<form class="card" method="post" style="margin-top:16px">
<?= csrf_field() ?>
<h3>Nová rezervace</h3>
<div class="field"><label>E-mail zákazníka</label><input type="email" name="email" required></div>
<div class="field"><label>Začátek (místní čas)</label><input type="datetime-local" name="start" required></div>
<div class="field"><label>Délka (min)</label><input type="number" name="duration" value="60"></div>
<button class="btn btn-primary">Vytvořit</button>
</form>
<form class="card" method="post" action="<?= e(url('/admin/rezervace/blokace')) ?>" style="margin-top:16px">
<?= csrf_field() ?>
<h3>Blokace termínu</h3>
<div class="row-2">
<div class="field"><label>Od</label><input type="datetime-local" name="start" required></div>
<div class="field"><label>Do</label><input type="datetime-local" name="end" required></div>
</div>
<div class="field"><label>Důvod</label><input name="reason"></div>
<button class="btn btn-secondary">Zablokovat</button>
</form>
<form class="card" method="post" action="<?= e(url('/admin/rezervace/provozni-doba')) ?>" style="margin-top:16px">
<?= csrf_field() ?>
<h3>Provozní doba</h3>
<?php $days = [1=>'Po',2=>'Út',3=>'St',4=>'Čt',5=>'Pá',6=>'So',7=>'Ne'];
$map = [];
foreach ($hours as $h) { $map[(int)$h['weekday']] = $h; }
foreach ($days as $n=>$label): $h = $map[$n] ?? ['opens_at'=>'06:00:00','closes_at'=>'22:00:00','is_closed'=>0]; ?>
<div class="row-2">
<div class="field"><label><?= $label ?> od</label><input type="time" name="opens_<?= $n ?>" value="<?= e(substr($h['opens_at'],0,5)) ?>"></div>
<div class="field"><label>do</label><input type="time" name="closes_<?= $n ?>" value="<?= e(substr($h['closes_at'],0,5)) ?>"></div>
</div>
<label class="check"><input type="checkbox" name="closed_<?= $n ?>" <?= (int)$h['is_closed']?'checked':'' ?>> Zavřeno</label>
<?php endforeach; ?>
<button class="btn btn-primary">Uložit dobu</button>
</form>
<form class="card" method="post" action="<?= e(url('/admin/rezervace/vyjimka')) ?>" style="margin-top:16px">
<?= csrf_field() ?>
<h3>Výjimka / zavírací den</h3>
<div class="field"><label>Datum</label><input type="date" name="date" required></div>
<label class="check"><input type="checkbox" name="closed" checked> Zavřeno</label>
<div class="field"><label>Poznámka</label><input name="note"></div>
<button class="btn btn-secondary">Uložit výjimku</button>
</form>
