<div class="page-head">
    <div>
        <h1>Předobjednávky</h1>
        <p class="muted">Celkem <?= (int) $total ?> e-mailů. Tady je zájem ještě před spuštěním.</p>
    </div>
    <a class="btn btn-secondary" href="<?= e(url('/admin/zajem/export')) ?>">Stáhnout CSV</a>
</div>
<form method="get" class="card" style="margin-bottom:16px">
    <div class="field"><label>Hledat e-mail</label><input name="q" value="<?= e($q) ?>" placeholder="cast@emailu.cz"></div>
    <button class="btn btn-secondary">Filtrovat</button>
</form>
<div class="table-wrap card">
<table>
    <thead>
        <tr>
            <th>E-mail</th>
            <th>Zdroj</th>
            <th>IP</th>
            <th>Přidáno</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$signups): ?>
        <tr><td colspan="4" class="muted">Zatím nikdo e-mail nenechal.</td></tr>
    <?php endif; ?>
    <?php foreach ($signups as $row): ?>
        <tr>
            <td><a href="mailto:<?= e($row['email']) ?>"><?= e($row['email']) ?></a></td>
            <td><?= e($row['source'] ?? '') ?></td>
            <td><?= e($row['ip_address'] ?? '') ?></td>
            <td><?= e(format_datetime($row['created_at'])) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
