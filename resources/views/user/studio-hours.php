<?php
$days = [1 => 'Pondělí', 2 => 'Úterý', 3 => 'Středa', 4 => 'Čtvrtek', 5 => 'Pátek', 6 => 'Sobota', 7 => 'Neděle'];
$short = [1 => 'Po', 2 => 'Út', 3 => 'St', 4 => 'Čt', 5 => 'Pá', 6 => 'So', 7 => 'Ne'];
$map = [];
foreach ($hours as $row) {
    $map[(int) $row['weekday']] = $row;
}
$room = $room ?? null;
?>
<div class="page-head">
    <div>
        <p class="eyebrow">SPRÁVA</p>
        <h1>Otevírací doba</h1>
        <p class="muted">U každého dne zapni otevřeno a nastav od–do. Vypnutý den se v rezervacích nenabízí.</p>
    </div>
</div>
<?php require dirname(__DIR__) . '/partials/studio-nav.php'; ?>

<?php if (!$room): ?>
    <div class="card">Nejdřív přidej studio.</div>
<?php else: ?>
    <div class="hours-layout">
        <form class="card studio-card hours-board" method="post" action="<?= e(url('/user/studio/doba')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="room" value="<?= e($room['public_id']) ?>">
            <div class="hours-board-head">
                <h2><?= e($room['name']) ?></h2>
                <label class="check hours-same"><input type="checkbox" name="same_week" value="1"> Celý týden jako pondělí</label>
            </div>
            <div class="hours-list">
                <?php foreach ($days as $n => $label):
                    $h = $map[$n] ?? ['opens_at' => '00:00:00', 'closes_at' => '23:59:00', 'is_closed' => 0];
                    $open = !(int) $h['is_closed'];
                ?>
                    <div class="hours-row<?= $open ? '' : ' is-closed' ?><?= $n >= 6 ? ' is-weekend' : '' ?>" data-hours-row>
                        <div class="hours-name">
                            <b><?= e($short[$n]) ?></b>
                            <span><?= e($label) ?></span>
                        </div>
                        <label class="day-switch">
                            <input type="checkbox" name="open_<?= $n ?>" value="1" data-open-day <?= $open ? 'checked' : '' ?>>
                            <span class="day-switch-ui" aria-hidden="true"></span>
                            <span class="day-switch-label"><?= $open ? 'Otevřeno' : 'Zavřeno' ?></span>
                        </label>
                        <div class="hours-range">
                            <input type="time" name="opens_<?= $n ?>" aria-label="<?= e($label) ?> od" value="<?= e(substr((string) $h['opens_at'], 0, 5)) ?>">
                            <span aria-hidden="true">–</span>
                            <input type="time" name="closes_<?= $n ?>" aria-label="<?= e($label) ?> do" value="<?= e(substr((string) $h['closes_at'], 0, 5)) ?>">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="btn btn-primary">Uložit otevírací dobu</button>
        </form>

        <aside class="card studio-card hours-aside">
            <h2>Zavřené dny</h2>
            <p class="muted">Jednorázově, třeba na svátek. Běžný týden tím neměníš.</p>
            <?php if (!empty($exceptions)): ?>
                <ul class="exception-list">
                    <?php foreach ($exceptions as $item): ?>
                        <li>
                            <span>
                                <strong><?= e((new DateTimeImmutable((string) $item['exception_date']))->format('j. n. Y')) ?></strong>
                                <?php if (!empty($item['note'])): ?><em><?= e($item['note']) ?></em><?php endif; ?>
                            </span>
                            <form method="post" action="<?= e(url('/user/studio/vyjimka/smazat')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="room" value="<?= e($room['public_id']) ?>">
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <button class="btn btn-ghost" type="submit">Zrušit</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="hours-none">Žádný zavřený den.</p>
            <?php endif; ?>
            <form method="post" action="<?= e(url('/user/studio/vyjimka')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="room" value="<?= e($room['public_id']) ?>">
                <div class="field"><label>Datum</label><input type="date" name="date" required></div>
                <div class="field"><label>Poznámka</label><input name="note" maxlength="255" placeholder="Svátek"></div>
                <button class="btn btn-secondary">Zavřít tento den</button>
            </form>
        </aside>
    </div>
<?php endif; ?>
<script nonce="<?= e($cspNonce ?? '') ?>">
document.querySelectorAll("[data-hours-row]").forEach((row) => {
  const box = row.querySelector("[data-open-day]");
  const label = row.querySelector(".day-switch-label");
  if (!box) return;
  const sync = () => {
    row.classList.toggle("is-closed", !box.checked);
    if (label) label.textContent = box.checked ? "Otevřeno" : "Zavřeno";
  };
  box.addEventListener("change", sync);
});
</script>
