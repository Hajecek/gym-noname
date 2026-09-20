<div class="page-head"><div><p class="eyebrow">TVŮJ ČAS</p><h1>Rezervace</h1><p class="muted">Soukromé studio. Ve stejném čase může běžet jen jedna rezervace.</p></div></div>
<form class="card" method="get" action="<?= e(url('/user/rezervace')) ?>" style="margin-bottom:16px">
    <div class="field"><label>Datum</label><input type="date" name="date" value="<?= e($date) ?>" onchange="this.form.submit()"></div>
</form>
<?php if (!empty($availability['closed'])): ?>
    <div class="card">Tento den je studio zavřené.</div>
<?php else: ?>
    <form method="post" class="card" action="<?= e(url('/user/rezervace')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="start" required>
        <div class="slots" style="margin-bottom:16px">
            <?php foreach ($availability['slots'] as $slot): ?>
                <button type="button" class="slot <?= $slot['available'] ? '' : 'busy' ?>" data-slot data-start="<?= e($date . ' ' . $slot['start']) ?>" <?= $slot['available'] ? '' : 'disabled' ?>><?= e($slot['start']) ?></button>
            <?php endforeach; ?>
        </div>
        <div class="row-2">
            <div class="field"><label>Délka</label>
                <select name="duration">
                    <?php for ($m = $availability['min_minutes']; $m <= $availability['max_minutes']; $m += $availability['slot_minutes']): ?>
                        <option value="<?= $m ?>"><?= $m ?> min</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="field"><label>Počet osob</label>
                <select name="guests">
                    <?php for ($i = 1; $i <= $availability['max_persons']; $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        <button class="btn btn-primary">Potvrdit rezervaci</button>
    </form>
<?php endif; ?>

<h2 style="margin-top:28px">Moje rezervace</h2>
<div class="table-wrap card">
<table>
<thead><tr><th>Začátek</th><th>Konec</th><th>Stav</th><th></th></tr></thead>
<tbody>
<?php foreach ($mine as $item): ?>
<tr>
<td><?= e(format_datetime($item['starts_at'])) ?></td>
<td><?= e(format_datetime($item['ends_at'], 'H:i')) ?></td>
<td><?= e($item['status']) ?></td>
<td>
<?php if (in_array($item['status'], ['confirmed','pending_payment'], true)): ?>
<form method="post" action="<?= e(url('/user/rezervace/' . $item['public_id'] . '/zrusit')) ?>"><?= csrf_field() ?><button class="btn btn-danger">Zrušit</button></form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
