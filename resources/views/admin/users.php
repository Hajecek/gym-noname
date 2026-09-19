<div class="page-head"><h1>Zákazníci</h1></div>
<form method="get" class="card" style="margin-bottom:16px">
    <div class="field"><label>Hledat</label><input name="q" value="<?= e($q) ?>" placeholder="jméno, e-mail, username"></div>
    <button class="btn btn-secondary">Filtrovat</button>
</form>
<div class="table-wrap card">
<table><thead><tr><th></th><th>Jméno</th><th>Username</th><th>E-mail</th><th>Role</th><th>Stav</th></tr></thead>
<tbody>
<?php foreach ($users as $row): ?>
<tr>
<td><img class="avatar" style="width:36px;height:36px" src="<?= e(avatar_url($row)) ?>" alt=""></td>
<td><a href="<?= e(url('/admin/zakaznici/' . $row['public_id'])) ?>"><?= e($row['first_name'] . ' ' . $row['last_name']) ?></a></td>
<td>@<?= e($row['username']) ?></td>
<td><?= e($row['email']) ?></td>
<td><?= e(role_label($row['role'])) ?></td>
<td><?= e($row['status']) ?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
