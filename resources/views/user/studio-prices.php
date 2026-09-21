<?php
$days = [1 => 'Pondělí', 2 => 'Úterý', 3 => 'Středa', 4 => 'Čtvrtek', 5 => 'Pátek', 6 => 'Sobota', 7 => 'Neděle'];
$map = [];
foreach ($hours as $row) {
    $map[(int) $row['weekday']] = $row;
}
$room = $room ?? null;
?>
<div class="page-head">
    <div>
        <p class="eyebrow">SPRÁVA</p>
        <h1>Ceny</h1>
        <p class="muted">Jedna cena za hodinu platí všude. U dne ji vyplň jen tehdy, když má být jiná.</p>
    </div>
</div>
<?php require dirname(__DIR__) . '/partials/studio-nav.php'; ?>

<?php if (!$room): ?>
    <div class="card">Nejdřív přidej studio.</div>
<?php else: ?>
    <form class="card studio-card" method="post" action="<?= e(url('/user/studio/ceny')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="room" value="<?= e($room['public_id']) ?>">
        <div class="field studio-default">
            <label>Cena za hodinu (Kč)</label>
            <input type="number" name="default_hourly_price" min="0" step="1" required value="<?= e((string) (int) (float) ($hourly_price ?? 150)) ?>">
        </div>
        <h2>Jiná cena v konkrétní den</h2>
        <p class="muted">Necháš-li pole prázdné, použije se cena za hodinu.</p>
        <div class="price-list">
            <?php foreach ($days as $n => $label):
                $dayPrice = $map[$n]['hourly_price'] ?? '';
            ?>
                <label class="price-row">
                    <span><?= e($label) ?></span>
                    <input type="number" name="price_<?= $n ?>" min="0" step="1" placeholder="stejná" value="<?= $dayPrice === null || $dayPrice === '' ? '' : e((string) (int) (float) $dayPrice) ?>">
                </label>
            <?php endforeach; ?>
        </div>
        <button class="btn btn-primary">Uložit ceny</button>
    </form>
<?php endif; ?>
