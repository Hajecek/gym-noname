<div class="page-head"><h1>Provozní přehled</h1></div>
<div class="stat-grid">
    <article class="card"><div class="stat-label">Obsazenost</div><div class="stat-value"><?= !empty($occupancy['occupied']) ? 'Probíhá trénink' : 'Volno' ?></div></article>
    <article class="card"><div class="stat-label">Zámek</div><div class="stat-value"><?= !empty($door['online']) ? 'Online' : 'Neznámý' ?></div></article>
</div>
<h2>Dnešní rezervace</h2>
<div class="table-wrap card">
<table><thead><tr><th>Čas</th><th>Zákazník</th><th>Telefon</th><th>Stav</th></tr></thead>
<tbody>
<?php foreach ($today as $row): ?>
<tr>
<td><?= e(format_datetime($row['starts_at'], 'H:i')) ?></td>
<td><?= e($row['first_name'] . ' ' . $row['last_name']) ?></td>
<td><?= e($row['phone'] ?? '') ?></td>
<td><?= e($row['status']) ?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
<form class="card" method="post" action="<?= e(url('/provoz/problem')) ?>" style="margin-top:16px">
<?= csrf_field() ?>
<h3>Nahlásit problém</h3>
<div class="field"><label>Název</label><input name="title" required></div>
<div class="field"><label>Popis</label><textarea name="description" required></textarea></div>
<button class="btn btn-primary">Odeslat</button>
</form>
