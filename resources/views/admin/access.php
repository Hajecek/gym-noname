<div class="page-head"><div><p class="eyebrow">SPRÁVA</p><h1>Dveře</h1><p class="muted">Stav zámku, test režim a logy vstupů.</p></div></div>
<div class="card">
<p>Poskytovatel: <?= e($status['provider'] ?? 'n/a') ?></p>
<p>Režim: <?= !empty($status['test_mode']) ? 'TEST (MockDoorProvider)' : 'Nuki' ?></p>
<p>Online: <?= !empty($status['online']) ? 'ano' : 'ne' ?></p>
<p>Zámek: <?= e($status['lock_state'] ?? 'n/a') ?></p>
<p>Baterie: <?= e((string)($status['battery_percent'] ?? 'n/a')) ?>%</p>
<p class="muted">Nuki token se nikdy neposílá do prohlížeče. Ostré otevírání se aktivuje až po konfiguraci zařízení.</p>
</div>
<form class="card" method="post" action="<?= e(url('/user/sprava/vstup/test')) ?>" style="margin-top:16px">
<?= csrf_field() ?>
<h3>Kritická akce – opětovné ověření</h3>
<div class="field"><label>Vaše heslo</label><input type="password" name="password" required></div>
<button class="btn btn-danger">Potvrdit testovací režim</button>
</form>
<div class="table-wrap card" style="margin-top:16px">
<table><thead><tr><th>Čas</th><th>Uživatel</th><th>Autorizace</th><th>Příkaz</th><th>Důvod</th></tr></thead>
<tbody>
<?php foreach ($logs as $log): ?>
<tr>
<td><?= e(format_datetime($log['created_at'])) ?></td>
<td><?= e($log['username'] ?? '') ?></td>
<td><?= e($log['authorization_result']) ?></td>
<td><?= e($log['command_result']) ?></td>
<td><?= e($log['denial_reason'] ?? '') ?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
