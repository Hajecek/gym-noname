<div class="page-head"><h1>Rezervace</h1></div>
<p class="muted">Rezervace je v blocích 1 h 15 min (hodina tréninku + 15 min úklid). Když někdo jde 2 nebo 3 hodiny, jdou v kuse a 15 min je až na konci.</p>
<form method="get" class="card"><div class="field"><label>Datum</label><input type="date" name="date" value="<?= e($date) ?>" onchange="this.form.submit()"></div></form>
<div class="table-wrap card" style="margin-top:16px">
<table><thead><tr><th>Začátek</th><th>Konec</th><th>Zákazník</th><th>Cena</th><th>Stav</th></tr></thead>
<tbody><?php foreach ($today as $row): ?><tr>
<td><?= e(format_datetime($row['starts_at'])) ?></td>
<td><?= e(format_datetime($row['ends_at'], 'H:i')) ?></td>
<td><?= e($row['first_name'].' '.$row['last_name']) ?></td>
<td><?= e(money_format_czk($row['price'] ?? 0)) ?></td>
<td><?= e($row['status']) ?></td>
</tr><?php endforeach; ?></tbody>
</table></div>
<form class="card" method="post" style="margin-top:16px">
<?= csrf_field() ?>
<h3>Nová rezervace</h3>
<div class="field"><label>E-mail zákazníka</label><input type="email" name="email" required></div>
<div class="field"><label>Začátek (místní čas)</label><input type="datetime-local" name="start" required step="900"></div>
<div class="field"><label>Délka</label>
<select name="duration">
<option value="60">1 hodina (blok 1 h 15 min)</option>
<option value="120">2 hodiny (blok 2 h 15 min)</option>
<option value="180">3 hodiny (blok 3 h 15 min)</option>
</select>
</div>
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
