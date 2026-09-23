<?php
$studio = is_array($studio ?? null) ? $studio : [
    'state' => 'open',
    'dot' => 'green',
    'label' => 'Volné',
    'detail' => 'Stav studia se načítá.',
    'room' => 'Studio',
];
$stats = is_array($stats ?? null) ? $stats : [
    'streak' => 0,
    'best_streak' => 0,
    'month_count' => 0,
    'year_count' => 0,
    'total_count' => 0,
    'history' => [],
];
$upcomingList = is_array($upcomingList ?? null) ? $upcomingList : [];
$history = is_array($stats['history'] ?? null) ? $stats['history'] : [];
$historyMax = 1;
foreach ($history as $point) {
    $historyMax = max($historyMax, (int) ($point['count'] ?? 0));
}
$dot = (string) ($studio['dot'] ?? 'green');
$headline = match ($dot) {
    'orange' => (string) ($studio['label'] ?? 'Blíží se termín'),
    'red' => 'Fitko uzavřené',
    default => 'Fitko volné',
};
$dayNames = [1 => 'pondělí', 2 => 'úterý', 3 => 'středa', 4 => 'čtvrtek', 5 => 'pátek', 6 => 'sobota', 7 => 'neděle'];
$monthsShort = [1 => 'led', 2 => 'úno', 3 => 'bře', 4 => 'dub', 5 => 'kvě', 6 => 'čvn', 7 => 'čvc', 8 => 'srp', 9 => 'zář', 10 => 'říj', 11 => 'lis', 12 => 'pro'];
$streak = (int) ($stats['streak'] ?? 0);
$streakUnit = $streak === 1 ? 'týden' : (($streak >= 2 && $streak <= 4) ? 'týdny' : 'týdnů');
?>
<div class="page-head">
    <div>
        <p class="eyebrow">TVŮJ PROSTOR</p>
        <h1>Ahoj, <?= e($user['first_name']) ?>.</h1>
        <p class="muted">@<?= e($user['username']) ?><?php if (is_admin_user($user)): ?> · uživatelský režim<?php else: ?> · <?= e(role_label($user['role'])) ?><?php endif; ?></p>
    </div>
    <div class="page-head-actions">
        <button
            type="button"
            class="streak-chip<?= $streak > 0 ? ' is-on' : '' ?>"
            data-streak-open
            aria-haspopup="dialog"
            aria-controls="streak-modal"
        >
            <svg class="streak-chip-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 2c.4 3.2-1.2 5.2-3.2 7.1C6.6 11.2 5 13.2 5 16a7 7 0 0 0 14 0c0-3.4-2.2-5.8-4.2-8.1C13.2 5.9 12.4 4.2 12 2Z" fill="currentColor"/>
                <path d="M12 22a4.2 4.2 0 0 1-4.2-4.2c0-1.7 1-3 2.2-4.2.4 1.5 1.3 2.3 2 2.8.7-.5 1.6-1.3 2-2.8 1.2 1.2 2.2 2.5 2.2 4.2A4.2 4.2 0 0 1 12 22Z" fill="currentColor" opacity=".35"/>
            </svg>
            <strong><?= $streak ?></strong>
            <span><?= e($streakUnit) ?></span>
        </button>
        <button
            type="button"
            class="studio-pulse"
            data-studio-pulse
            data-studio-url="<?= e(url('/user/stav-studia')) ?>"
            data-state="<?= e((string) ($studio['state'] ?? 'open')) ?>"
            aria-haspopup="dialog"
            aria-controls="studio-pulse-modal"
        >
            <i class="studio-pulse-dot is-<?= e($dot) ?>" data-studio-dot aria-hidden="true"></i>
            <span data-studio-label><?= e((string) ($studio['label'] ?? 'Volné')) ?></span>
        </button>
        <a class="button" href="<?= e(url('/user/rezervace')) ?>">Rezervovat trénink <span>↗</span></a>
    </div>
</div>

<div class="cancel-modal" id="streak-modal" data-streak-modal hidden>
    <div class="cancel-modal-backdrop" data-streak-close tabindex="-1"></div>
    <div class="cancel-modal-panel streak-panel" role="dialog" aria-modal="true" aria-labelledby="streak-title">
        <div class="streak-panel-top">
            <svg class="streak-panel-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 2c.4 3.2-1.2 5.2-3.2 7.1C6.6 11.2 5 13.2 5 16a7 7 0 0 0 14 0c0-3.4-2.2-5.8-4.2-8.1C13.2 5.9 12.4 4.2 12 2Z" fill="currentColor"/>
                <path d="M12 22a4.2 4.2 0 0 1-4.2-4.2c0-1.7 1-3 2.2-4.2.4 1.5 1.3 2.3 2 2.8.7-.5 1.6-1.3 2-2.8 1.2 1.2 2.2 2.5 2.2 4.2A4.2 4.2 0 0 1 12 22Z" fill="currentColor" opacity=".35"/>
            </svg>
            <div>
                <p class="eyebrow">Tvoje série</p>
                <h2 id="streak-title"><?= $streak ?> <?= e($streakUnit) ?></h2>
            </div>
        </div>
        <p class="muted streak-panel-lead">Streak držíš, když máš aspoň jeden dokončený trénink každý týden. Aktuální týden bez návštěvy sérii ještě nepřeruší.</p>
        <dl class="streak-facts">
            <div>
                <dt>Rekord</dt>
                <dd><?= (int) ($stats['best_streak'] ?? 0) ?></dd>
            </div>
            <div>
                <dt>Tenhle měsíc</dt>
                <dd><?= (int) ($stats['month_count'] ?? 0) ?></dd>
            </div>
            <div>
                <dt>Letos</dt>
                <dd><?= (int) ($stats['year_count'] ?? 0) ?></dd>
            </div>
            <div>
                <dt>Celkem dní</dt>
                <dd><?= (int) ($stats['total_count'] ?? 0) ?></dd>
            </div>
        </dl>
        <div class="streak-chart-wrap">
            <div class="streak-chart-head">
                <strong>Posledních 12 týdnů</strong>
                <span class="muted">tréninky / týden</span>
            </div>
            <div class="streak-chart" role="img" aria-label="Graf tréninků za posledních 12 týdnů">
                <?php foreach ($history as $point):
                    $count = (int) ($point['count'] ?? 0);
                    $pct = $count > 0 ? max(12, (int) round(($count / $historyMax) * 100)) : 0;
                ?>
                    <div class="streak-bar<?= !empty($point['active']) ? ' is-active' : '' ?>" title="<?= e((string) ($point['label'] ?? '')) ?>: <?= $count ?>">
                        <span class="streak-bar-value"><?= $count > 0 ? $count : '' ?></span>
                        <span class="streak-bar-fill" style="--h: <?= $pct ?>%"></span>
                        <span class="streak-bar-label"><?= e((string) ($point['label'] ?? '')) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="cancel-actions">
            <button type="button" class="btn btn-secondary" data-streak-close>Zavřít</button>
        </div>
    </div>
</div>

<div class="cancel-modal" id="studio-pulse-modal" data-studio-modal hidden>
    <div class="cancel-modal-backdrop" data-studio-close tabindex="-1"></div>
    <div class="cancel-modal-panel studio-pulse-panel is-<?= e($dot) ?>" data-studio-panel role="dialog" aria-modal="true" aria-labelledby="studio-pulse-title">
        <div class="studio-pulse-status">
            <i class="studio-pulse-dot is-<?= e($dot) ?>" data-studio-banner-dot aria-hidden="true"></i>
            <span>Stav teď</span>
        </div>
        <h2 id="studio-pulse-title" data-studio-title><?= e($headline) ?></h2>
        <p data-studio-detail><?= e((string) ($studio['detail'] ?? '')) ?></p>
        <dl class="studio-pulse-facts">
            <div>
                <dt>Studio</dt>
                <dd data-studio-room><?= e((string) ($studio['room'] ?? 'Studio')) ?></dd>
            </div>
            <div>
                <dt>Otevírací doba</dt>
                <dd data-studio-hours><?php
                    $open = (string) ($studio['opens_at'] ?? '');
                    $close = (string) ($studio['closes_at'] ?? '');
                    echo e(($open !== '' && $close !== '') ? ($open . '–' . $close) : '—');
                ?></dd>
            </div>
            <div>
                <dt data-studio-next-label>Další termín</dt>
                <dd data-studio-next><?= e((string) ($studio['next_at'] ?? '—')) ?></dd>
            </div>
        </dl>
        <div class="cancel-actions">
            <button type="button" class="btn studio-pulse-close" data-studio-close>Zavřít</button>
        </div>
    </div>
</div>

<?php if ($current && $canOpen): ?>
<div class="card door-card" style="margin-bottom:16px">
    <p class="eyebrow">Právě teď</p>
    <h2>Tvůj trénink právě probíhá</h2>
    <p class="muted"><?= e(format_datetime($current['starts_at'])) ?> – <?= e(format_datetime($current['ends_at'], 'H:i')) ?></p>
    <form method="post" action="<?= e(url('/user/vstup')) ?>" style="margin-top:16px"><?= csrf_field() ?><button class="btn btn-primary">Otevřít dveře</button></form>
</div>
<?php endif; ?>

<?php if ($membership): ?>
<section class="member-hero card">
    <div>
        <p class="eyebrow">Tvoje členství</p>
        <h2><?= e($membership['plan_name']) ?></h2>
        <p class="member-count"><?php
            if ($remaining === null) {
                echo 'Neomezené vstupy';
            } else {
                $left = (int) $remaining;
                $unit = $left === 1 ? 'vstup' : ($left >= 2 && $left <= 4 ? 'vstupy' : 'vstupů');
                echo e($left . ' ' . $unit);
            }
        ?></p>
        <p class="muted"><?= $membership['ends_at'] ? 'Platí do ' . e(format_datetime($membership['ends_at'], 'd. m. Y')) . '. ' : '' ?>Další den si jen rezervuješ, platba se nestrhává.</p>
    </div>
    <a class="button" href="<?= e(url('/user/rezervace')) ?>">Vybrat den <span>↗</span></a>
</section>
<?php endif; ?>

<div class="stat-grid">
    <a class="card" href="<?= e(url('/user/clenstvi')) ?>"><div class="stat-label">Členství</div><div class="stat-value"><?= e($membership['plan_name'] ?? 'Žádné') ?></div></a>
    <a class="card" href="<?= e(url('/user/moje-rezervace')) ?>"><div class="stat-label">Nejbližší rezervace</div><div class="stat-value"><?= $upcoming ? e(format_datetime($upcoming['starts_at'], 'd.m. H:i')) : '—' ?></div></a>
    <a class="card" href="<?= e(url('/user/clenstvi')) ?>"><div class="stat-label">Zbývající vstupy</div><div class="stat-value"><?= $remaining === null ? 'Neomezené' : (int) $remaining ?></div></a>
</div>

<section class="dash-bookings">
    <div class="dash-bookings-head">
        <h2>Moje rezervace</h2>
        <a class="text-link" href="<?= e(url('/user/moje-rezervace')) ?>">Všechny</a>
    </div>
    <?php if ($upcomingList === []): ?>
        <div class="card dash-bookings-empty">
            <p class="muted">Zatím nemáš žádný naplánovaný termín.</p>
            <a class="btn btn-secondary button-small" href="<?= e(url('/user/rezervace')) ?>">Rezervovat</a>
        </div>
    <?php else: ?>
        <div class="dash-bookings-list">
            <?php foreach ($upcomingList as $item):
                $status = (string) ($item['status'] ?? '');
                $startLocal = \App\Support\Clock::toLocal((string) $item['starts_at']);
                $endShown = \App\Support\Clock::toLocal((string) $item['ends_at'])->modify('+' . (int) ($item['buffer_minutes'] ?? 15) . ' minutes');
                $whenLabel = $dayNames[(int) $startLocal->format('N')] . ' ' . $startLocal->format('j. n.');
                $badge = $status === 'pending_payment' ? ['Čeká platba', 'badge-warn'] : ['Potvrzeno', 'badge-ok'];
            ?>
                <article class="card dash-booking">
                    <div class="res-day">
                        <strong><?= e($startLocal->format('j')) ?></strong>
                        <span><?= e($monthsShort[(int) $startLocal->format('n')]) ?></span>
                    </div>
                    <div>
                        <p class="res-when"><?= e($whenLabel) ?></p>
                        <h3><?= e($startLocal->format('H:i')) ?>–<?= e($endShown->format('H:i')) ?></h3>
                        <p class="muted"><?= e($item['room_name'] ?? 'Studio') ?></p>
                    </div>
                    <span class="badge <?= e($badge[1]) ?>"><?= e($badge[0]) ?></span>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php if (!$membership): ?>
<div class="card" style="margin-top:16px">
    <h3>Zatím nemáš aktivní členství</h3>
    <p class="muted">Vyber si tarif, zaplať ho a pak si rezervuj jen den. Vstup se odečte sám.</p>
    <a class="btn btn-secondary" href="<?= e(url('/user/clenstvi')) ?>" style="margin-top:14px">Vybrat tarif</a>
</div>
<?php endif; ?>
