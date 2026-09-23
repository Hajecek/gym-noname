<?php
$days = [1 => 'Pondělí', 2 => 'Úterý', 3 => 'Středa', 4 => 'Čtvrtek', 5 => 'Pátek', 6 => 'Sobota', 7 => 'Neděle'];
$short = [1 => 'Po', 2 => 'Út', 3 => 'St', 4 => 'Čt', 5 => 'Pá', 6 => 'So', 7 => 'Ne'];
$map = [];
foreach ($hours as $row) {
    $map[(int) $row['weekday']] = $row;
}
$room = $room ?? null;
$priceOne = (int) (float) ($hourly_price ?? 150);
$priceTwo = (int) (float) ($hourly_price_two ?? 200);
$extra = max(0, $priceTwo - $priceOne);
?>
<div class="page-head">
    <div>
        <p class="eyebrow">SPRÁVA</p>
        <h1>Ceny</h1>
        <p class="muted">Základ platí všude. Denní pole vyplň jen když má být jinak.</p>
    </div>
    <button class="btn btn-primary" type="submit" form="price-form">Uložit ceny</button>
</div>
<?php require dirname(__DIR__) . '/partials/studio-nav.php'; ?>

<form id="price-form" class="price-layout" method="post" action="<?= e(url('/user/studio/ceny')) ?>">
    <?= csrf_field() ?>
    <?php if ($room): ?>
        <input type="hidden" name="room" value="<?= e($room['public_id']) ?>">
    <?php endif; ?>

    <section class="card studio-card price-panel">
        <header class="price-panel-head">
            <h2>Základní sazby</h2>
            <p class="muted">Pro všechna studia</p>
        </header>
        <div class="price-rates">
            <label class="price-rate">
                <span>1 osoba</span>
                <strong>
                    <input type="number" name="default_hourly_price" min="0" step="1" required value="<?= e((string) $priceOne) ?>">
                    <i>Kč</i>
                </strong>
            </label>
            <label class="price-rate is-two">
                <span>2 osoby</span>
                <strong>
                    <input type="number" name="default_hourly_price_two" min="0" step="1" required value="<?= e((string) $priceTwo) ?>">
                    <i>Kč</i>
                </strong>
            </label>
        </div>
        <p class="price-hint">Hodina · <?= $priceOne ?> / <?= $priceTwo ?> Kč · příplatek za 2. osobu <?= $extra ?> Kč</p>
    </section>

    <?php if (!$room): ?>
        <section class="card studio-card price-panel">
            <p class="muted" style="margin:0">Nejdřív přidej studio.</p>
        </section>
    <?php else: ?>
        <section class="card studio-card price-panel">
            <header class="price-panel-head">
                <div>
                    <h2>Denní výjimky</h2>
                    <p class="muted"><?= e($room['name']) ?></p>
                </div>
            </header>
            <div class="price-table" role="table" aria-label="Denní ceny">
                <div class="price-table-head" role="row">
                    <span role="columnheader">Den</span>
                    <span role="columnheader">1 osoba</span>
                    <span role="columnheader">2 osoby</span>
                </div>
                <?php foreach ($days as $n => $label):
                    $dayPrice = $map[$n]['hourly_price'] ?? '';
                    $hasOverride = !($dayPrice === null || $dayPrice === '');
                    $shownOne = $hasOverride ? (int) (float) $dayPrice : $priceOne;
                    $shownTwo = $shownOne + $extra;
                ?>
                    <label class="price-table-row<?= $n >= 6 ? ' is-weekend' : '' ?>" role="row">
                        <span class="price-table-day" role="cell">
                            <b><?= e($short[$n]) ?></b>
                            <em><?= e($label) ?></em>
                        </span>
                        <span class="price-table-input" role="cell">
                            <input
                                type="number"
                                name="price_<?= $n ?>"
                                min="0"
                                step="1"
                                placeholder="<?= e((string) $priceOne) ?>"
                                value="<?= $hasOverride ? e((string) (int) (float) $dayPrice) : '' ?>"
                                data-day-price
                                data-extra="<?= $extra ?>"
                            >
                            <i>Kč</i>
                        </span>
                        <span class="price-table-two" role="cell" data-day-two>
                            <?= $shownTwo ?>&nbsp;Kč
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <p class="price-hint">Prázdné pole = základní sazba. 2 osoby = 1 osoba + <?= $extra ?> Kč.</p>
        </section>
    <?php endif; ?>
</form>
