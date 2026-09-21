<?php
$dayNames = [1 => 'pondělí', 2 => 'úterý', 3 => 'středa', 4 => 'čtvrtek', 5 => 'pátek', 6 => 'sobota', 7 => 'neděle'];
$monthsShort = [1 => 'led', 2 => 'úno', 3 => 'bře', 4 => 'dub', 5 => 'kvě', 6 => 'čvn', 7 => 'čvc', 8 => 'srp', 9 => 'zář', 10 => 'říj', 11 => 'lis', 12 => 'pro'];
$filters = [
    'prehled' => 'Přehled',
    'naplanovane' => 'Naplánované',
    'probehle' => 'Proběhlé',
    'zrusene' => 'Zrušené',
    'platba' => 'Čeká na platbu',
    'vse' => 'Vše',
];
$tones = [
    'pending_payment' => ['Čeká na platbu', 'badge-warn', 'is-pending'],
    'confirmed' => ['Potvrzeno', 'badge-ok', 'is-confirmed'],
    'cancelled' => ['Zrušeno', 'badge-bad', 'is-cancelled'],
    'completed' => ['Proběhlo', 'badge-done', 'is-done'],
    'expired' => ['Vypršelo', 'badge-muted', 'is-expired'],
    'no_show' => ['Nedorazil', 'badge-bad', 'is-missed'],
];
?>
<div class="page-head">
    <div>
        <p class="eyebrow">TVÉ TERMÍNY</p>
        <h1>Moje rezervace</h1>
        <p class="muted">Naplánované a proběhlé jsou odděleně. Zrušené a nezaplacené otevřeš filtrem.</p>
    </div>
    <a class="button" href="<?= e(url('/user/rezervace')) ?>">Nová rezervace <span>↗</span></a>
</div>

<nav class="res-filters" aria-label="Filtr rezervací">
    <?php foreach ($filters as $key => $label): ?>
        <a class="res-filter<?= $filter === $key ? ' is-on' : '' ?>" href="<?= e(url('/user/moje-rezervace' . ($key === 'prehled' ? '' : '?stav=' . $key))) ?>">
            <?= e($label) ?>
            <span><?= (int) ($counts[$key] ?? 0) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<?php foreach ($sections as $section): ?>
    <section class="res-group">
        <h2><?= e($section['title']) ?></h2>
        <?php if ($section['items'] === []): ?>
            <div class="card res-empty"><?= e($section['empty']) ?></div>
        <?php else: ?>
            <div class="res-list">
                <?php foreach ($section['items'] as $item):
                    $status = (string) ($item['status'] ?? '');
                    [$statusLabel, $statusClass, $tone] = $tones[$status] ?? [$status, 'badge-warn', 'is-pending'];
                    if ($section['key'] === 'probehle' && $status === 'confirmed') {
                        $statusLabel = 'Proběhlo';
                        $statusClass = 'badge-done';
                        $tone = 'is-done';
                    }
                    $canCancel = in_array($status, ['confirmed', 'pending_payment'], true) && $section['key'] !== 'probehle';
                    $startLocal = \App\Support\Clock::toLocal((string) $item['starts_at']);
                    $endShown = \App\Support\Clock::toLocal((string) $item['ends_at'])->modify('+' . (int) ($item['buffer_minutes'] ?? 15) . ' minutes');
                    $whenLabel = $dayNames[(int) $startLocal->format('N')] . ' ' . $startLocal->format('j. n. Y');
                    $guests = (int) ($item['guest_count'] ?? 1);
                    $guestLabel = $guests > 1 ? ' · ' . $guests . ($guests < 5 ? ' osoby' : ' osob') : '';
                ?>
                    <article class="res-card card <?= e($tone) ?>">
                        <div class="res-day">
                            <strong><?= e($startLocal->format('j')) ?></strong>
                            <span><?= e($monthsShort[(int) $startLocal->format('n')]) ?></span>
                        </div>
                        <div class="res-copy">
                            <p class="res-when"><?= e($whenLabel) ?></p>
                            <h3><?= e(format_datetime($item['starts_at'], 'H:i')) ?>–<?= e($endShown->format('H:i')) ?></h3>
                            <p class="muted"><?= e($item['room_name'] ?? 'Studio') ?> · <?= e(money_format_czk($item['price'] ?? 0)) ?><?= e($guestLabel) ?></p>
                        </div>
                        <div class="res-actions">
                            <span class="badge <?= e($statusClass) ?>"><?= e($statusLabel) ?></span>
                            <?php if ($canCancel): ?>
                                <form method="post" action="<?= e(url('/user/rezervace/' . $item['public_id'] . '/zrusit')) ?>">
                                    <?= csrf_field() ?>
                                    <button
                                        class="btn btn-danger button-small"
                                        data-cancel-open
                                        data-status="<?= e($status) ?>"
                                        data-price="<?= e((string) ($item['price'] ?? 0)) ?>"
                                        data-membership="<?= !empty($item['membership_id']) && (float) ($item['price'] ?? 0) <= 0 ? '1' : '0' ?>"
                                        data-paid-at="<?= (int) ($paidAt[(int) $item['id']] ?? 0) ?>"
                                    >Zrušit</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endforeach; ?>

<div class="cancel-modal" data-cancel-modal hidden data-now="<?= (int) ($nowUnix ?? time()) ?>" data-refund-seconds="<?= (int) ($refundSeconds ?? 120) ?>">
    <div class="cancel-modal-backdrop" data-cancel-close></div>
    <section class="cancel-modal-panel" role="dialog" aria-modal="true" aria-labelledby="cancel-title">
        <p class="done-kicker">STORNO</p>
        <h2 id="cancel-title">Zrušit termín</h2>
        <p class="cancel-rule">Peníze lze vrátit nejvýše 2 minuty od potvrzení rezervace. Potom už na vrácení peněz není nárok.</p>
        <p class="cancel-outcome" data-cancel-outcome></p>
        <div class="cancel-actions">
            <button type="button" class="btn btn-secondary" data-cancel-close>Nechat termín</button>
            <button type="button" class="btn btn-danger" data-cancel-confirm>Zrušit termín</button>
        </div>
    </section>
</div>
