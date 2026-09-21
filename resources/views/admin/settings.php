<div class="page-head"><h1>Nastavení systému</h1></div>
<form method="post" class="card">
<?= csrf_field() ?>
<div class="field"><label>Krok začátku (min)</label><input name="reservation.slot_minutes" value="<?= e((string)setting('reservation.slot_minutes', 15)) ?>"><p class="muted">Interní krok obsazenosti. Zákazník vidí bloky 1 h + rezerva na úklid.</p></div>
<div class="field"><label>Min. délka tréninku (min)</label><input name="reservation.min_minutes" value="<?= e((string)setting('reservation.min_minutes', 60)) ?>"></div>
<div class="field"><label>Max. délka tréninku (min)</label><input name="reservation.max_minutes" value="<?= e((string)setting('reservation.max_minutes', 180)) ?>"></div>
<div class="field"><label>Rezerva na převlečení a umytí (min)</label><input name="reservation.buffer_minutes" value="<?= e((string)setting('reservation.buffer_minutes', 15)) ?>"><p class="muted">Patří do každého bloku (1 h 15 min). U 2–3 hodin v kuse je až na konci celého termínu.</p></div>
<div class="field"><label>Storno (hodiny)</label><input name="reservation.cancellation_hours" value="<?= e((string)setting('reservation.cancellation_hours', 12)) ?>"></div>
<div class="field"><label>Vstup před začátkem (min)</label><input name="access.early_minutes" value="<?= e((string)setting('access.early_minutes', 10)) ?>"></div>
<div class="field"><label>Cena za hodinu (Kč)</label><input name="pricing.hourly" value="<?= e((string)setting('pricing.hourly', 150)) ?>"><p class="muted">Použije se u dnů, které nemají vlastní cenu v provozní době.</p></div>
<button class="btn btn-primary">Uložit</button>
</form>
<div class="table-wrap card" style="margin-top:16px">
<h3>Auditní log</h3>
<table><thead><tr><th>Čas</th><th>Kdo</th><th>Akce</th><th>Entita</th></tr></thead>
<tbody>
<?php foreach ($audit as $row): ?>
<tr><td><?= e(format_datetime($row['created_at'])) ?></td><td><?= e($row['username'] ?? '') ?></td><td><?= e($row['action']) ?></td><td><?= e($row['entity_type']) ?></td></tr>
<?php endforeach; ?>
</tbody></table>
</div>
