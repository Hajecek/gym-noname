<div class="page-head">
    <div>
        <p class="eyebrow">SPRÁVA</p><h1>Zájem</h1>
        <p class="muted">Celkem <?= (int) $total ?> e-mailů. Tady je zájem ještě před spuštěním.</p>
    </div>
    <a class="btn btn-secondary" href="<?= e(url('/user/sprava/zajem/export')) ?>">Stáhnout CSV</a>
</div>
<form method="get" class="card cust-search">
    <div class="field"><label>Hledat e-mail</label><input name="q" value="<?= e($q) ?>" placeholder="cast@emailu.cz"></div>
    <button class="btn btn-secondary" type="submit">Filtrovat</button>
</form>
<div class="table-wrap card">
<table>
    <thead>
        <tr>
            <th>E-mail</th>
            <th>Zdroj</th>
            <th>IP</th>
            <th>Přidáno</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$signups): ?>
        <tr><td colspan="5" class="muted">Zatím nikdo e-mail nenechal.</td></tr>
    <?php endif; ?>
    <?php foreach ($signups as $row): ?>
        <tr>
            <td><a href="mailto:<?= e($row['email']) ?>"><?= e($row['email']) ?></a></td>
            <td><?= e($row['source'] ?? '') ?></td>
            <td><?= e($row['ip_address'] ?? '') ?></td>
            <td><?= e(format_datetime($row['created_at'])) ?></td>
            <td class="interest-actions">
                <button
                    type="button"
                    class="btn btn-danger btn-sm"
                    data-cust-open="interest-delete"
                    data-name="<?= e($row['email']) ?>"
                    data-action="<?= e(url('/user/sprava/zajem/' . (int) $row['id'] . '/smazat')) ?>"
                >Smazat</button>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<form method="post" hidden data-cust-form="interest-delete">
    <?= csrf_field() ?>
</form>

<div class="cancel-modal" data-cust-modal hidden>
    <div class="cancel-modal-backdrop" data-cust-close></div>
    <section class="cancel-modal-panel" role="dialog" aria-modal="true" aria-labelledby="interest-modal-title">
        <p class="eyebrow" data-cust-eyebrow>Potvrzení</p>
        <h2 id="interest-modal-title" data-cust-title>Potvrdit akci</h2>
        <p class="muted" data-cust-body></p>
        <div class="cancel-actions">
            <button type="button" class="btn btn-secondary" data-cust-close>Zpět</button>
            <button type="button" class="btn btn-danger" data-cust-confirm>Potvrdit</button>
        </div>
    </section>
</div>
