<?php
$hourly = (float) ($availability['hourly_price'] ?? 150);
$step = (int) ($availability['duration_step_minutes'] ?? 60);
$min = (int) ($availability['min_minutes'] ?? 60);
$max = (int) ($availability['max_minutes'] ?? 180);
$buffer = (int) ($availability['buffer_minutes'] ?? 15);
$maxPersons = (int) ($availability['max_persons'] ?? 3);
$dayNames = [1 => 'pondělí', 2 => 'úterý', 3 => 'středa', 4 => 'čtvrtek', 5 => 'pátek', 6 => 'sobota', 7 => 'neděle'];
$monthsGen = [1 => 'ledna', 2 => 'února', 3 => 'března', 4 => 'dubna', 5 => 'května', 6 => 'června', 7 => 'července', 8 => 'srpna', 9 => 'září', 10 => 'října', 11 => 'listopadu', 12 => 'prosince'];
$localDay = \App\Support\Clock::parseLocal($date . ' 12:00:00');
$dateLabel = $dayNames[(int) $localDay->format('N')] . ' ' . (int) $localDay->format('j') . '. ' . $monthsGen[(int) $localDay->format('n')];
$payload = [
    'date' => $date,
    'today' => $today,
    'availability' => $availability,
    'membership_covers' => !empty($membership_covers),
];
$statusMap = [
    'pending_payment' => ['Čeká na platbu', 'badge-warn'],
    'confirmed' => ['Potvrzeno', 'badge-ok'],
    'cancelled' => ['Zrušeno', 'badge-bad'],
    'completed' => ['Proběhlo', 'badge-ok'],
    'expired' => ['Vypršelo', 'badge-bad'],
    'no_show' => ['Nedorazil', 'badge-bad'],
];
?>
<div class="page-head">
    <div>
        <p class="eyebrow">TVŮJ ČAS</p>
        <h1>Rezervace</h1>
        <p class="muted">Otevři kalendář, vyber den a klikni na blok. Každý blok je 1 h 15 min. Délku 2 nebo 3 hodiny přidáš tlačítky dole.</p>
    </div>
</div>

<div
    class="booker"
    data-booker
    data-store="<?= e(url('/user/rezervace')) ?>"
    data-availability-url="<?= e(url('/user/rezervace/dostupnost')) ?>"
    data-calendar-url="<?= e(url('/user/rezervace/kalendar')) ?>"
    data-page-url="<?= e(url('/user/rezervace')) ?>"
    data-payload="<?= e(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP)) ?>"
>
    <noscript>
        <form class="card" method="get" action="<?= e(url('/user/rezervace')) ?>" style="margin-bottom:16px">
            <div class="field"><label>Datum</label><input type="date" name="date" value="<?= e($date) ?>"></div>
            <button class="btn btn-secondary">Zobrazit den</button>
        </form>
    </noscript>

    <button type="button" class="booker-date-btn card" data-open-cal aria-haspopup="dialog" aria-expanded="false">
        <span class="booker-date-kicker">Vybraný den</span>
        <strong data-date-label><?= e($dateLabel) ?></strong>
        <span class="booker-date-action">Otevřít kalendář</span>
    </button>

    <div class="cal-modal" data-cal-modal hidden>
        <div class="cal-modal-backdrop" data-cal-close></div>
        <section class="cal-modal-panel" data-calendar role="dialog" aria-modal="true" aria-labelledby="calendar-title">
            <header class="cal-head">
                <button type="button" class="cal-nav" data-cal-prev aria-label="Předchozí měsíc">‹</button>
                <h2 id="calendar-title" data-cal-title>Kalendář</h2>
                <button type="button" class="cal-nav" data-cal-next aria-label="Další měsíc">›</button>
            </header>
            <div class="cal-weekdays" aria-hidden="true">
                <span>Po</span><span>Út</span><span>St</span><span>Čt</span><span>Pá</span><span>So</span><span>Ne</span>
            </div>
            <div class="cal-grid" data-cal-grid></div>
            <p class="cal-legend muted">Zelená tečka = volný termín</p>
            <button type="button" class="cal-modal-close" data-cal-close>Zavřít</button>
        </section>
    </div>

    <section class="booker-hours card">
        <div class="booker-hours-head">
            <div>
                <h2>Hodiny</h2>
                <p class="muted" data-hours-hint>Každý blok je hodina tréninku plus <?= (int) $buffer ?> min úklid. 2 nebo 3 hodiny jdou v kuse, úklid je až na konci.</p>
            </div>
            <p class="booker-price"><?= e(money_format_czk($hourly)) ?><span> / hod</span></p>
        </div>
        <div class="hour-list" data-hour-list></div>
        <p class="booker-empty" data-hours-empty hidden>Pro tento den teď není volná hodina.</p>
        <p class="booker-empty" data-hours-closed hidden>Tento den je studio zavřené.</p>
        <p class="booker-empty" data-hours-loading hidden>Načítám volné hodiny…</p>
    </section>

    <form id="book-form" method="post" action="<?= e(url('/user/rezervace')) ?>" data-book-form>
        <?= csrf_field() ?>
        <input type="hidden" name="start" value="">
        <input type="hidden" name="duration" value="<?= (int) $min ?>">
        <input type="hidden" name="guests" value="1">
    </form>
</div>

<div class="booker-bar" data-bar hidden>
    <div class="booker-bar-inner">
        <button type="button" class="booker-bar-clear" data-clear aria-label="Zrušit výběr">✕</button>
        <div class="booker-bar-copy">
            <strong data-bar-time>Vyber hodiny</strong>
            <span data-bar-meta>Klikni na volnou hodinu v seznamu</span>
        </div>
        <div class="booker-durations" data-durations>
            <span>Délka</span>
            <button type="button" data-hours="1">1 h 15</button>
            <button type="button" data-hours="2">2 h 15</button>
            <button type="button" data-hours="3">3 h 15</button>
        </div>
        <div class="booker-guests" data-guests-wrap>
            <span>Osoby</span>
            <button type="button" data-guest-minus aria-label="Méně osob">−</button>
            <strong data-guest-count>1</strong>
            <button type="button" data-guest-plus aria-label="Více osob">+</button>
        </div>
        <button type="submit" class="btn btn-primary" data-confirm form="book-form">Zaplatit</button>
    </div>
</div>

<section class="mine">
    <h2>Moje rezervace</h2>
    <?php if ($mine === []): ?>
        <div class="card mine-empty">Zatím nemáš žádnou rezervaci. Vyber den a hodinu výše.</div>
    <?php else: ?>
        <div class="mine-list">
            <?php foreach ($mine as $item):
                $status = (string) ($item['status'] ?? '');
                [$statusLabel, $statusClass] = $statusMap[$status] ?? [$status, 'badge-warn'];
                $canCancel = in_array($status, ['confirmed', 'pending_payment'], true);
                $startLocal = \App\Support\Clock::toLocal((string) $item['starts_at']);
                $endShown = \App\Support\Clock::toLocal((string) $item['ends_at'])->modify('+' . (int) ($item['buffer_minutes'] ?? 15) . ' minutes');
                $whenLabel = $dayNames[(int) $startLocal->format('N')] . ' ' . $startLocal->format('j. n. Y');
            ?>
                <article class="mine-card card">
                    <div>
                        <p class="mine-when"><?= e($whenLabel) ?></p>
                        <h3><?= e(format_datetime($item['starts_at'], 'H:i')) ?>–<?= e($endShown->format('H:i')) ?></h3>
                        <p class="muted"><?= e($item['room_name'] ?? 'Studio') ?> · <?= e(money_format_czk($item['price'] ?? 0)) ?></p>
                    </div>
                    <div class="mine-actions">
                        <span class="badge <?= e($statusClass) ?>"><?= e($statusLabel) ?></span>
                        <?php if ($canCancel): ?>
                            <form method="post" action="<?= e(url('/user/rezervace/' . $item['public_id'] . '/zrusit')) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-danger button-small">Zrušit</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
