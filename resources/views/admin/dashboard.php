<?php
$stats = is_array($stats ?? null) ? $stats : [];
$chart = is_array($chart ?? null) ? $chart : ['days' => [], 'breakdown' => [], 'total' => 0, 'count' => 0];
$current = is_array($stats['current'] ?? null) ? $stats['current'] : [];
$door = is_array($stats['door'] ?? null) ? $stats['door'] : [];
$todayList = is_array($stats['today_list'] ?? null) ? $stats['today_list'] : [];
$deniedList = is_array($stats['denied_list'] ?? null) ? $stats['denied_list'] : [];
$next = is_array($stats['next'] ?? null) ? $stats['next'] : null;
$occupied = !empty($current['occupied']);
$reservation = is_array($current['reservation'] ?? null) ? $current['reservation'] : null;
$online = !empty($door['online']);
$configured = !empty($door['configured']);
$testMode = !empty($door['test_mode']);
$nowLocal = \App\Support\Clock::nowLocal();
$chartJson = json_encode($chart, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?: '{}';
$breakdown = is_array($chart['breakdown'] ?? null) ? $chart['breakdown'] : [];

$guestName = '';
$slotLabel = '';
if ($reservation) {
    $guestName = trim((string) (($reservation['first_name'] ?? '') . ' ' . ($reservation['last_name'] ?? '')));
    if (!empty($reservation['starts_at']) && !empty($reservation['ends_at'])) {
        $start = \App\Support\Clock::toLocal((string) $reservation['starts_at']);
        $end = \App\Support\Clock::toLocal((string) $reservation['ends_at']);
        $slotLabel = $start->format('H:i') . '–' . $end->format('H:i');
    }
}

$nextLabel = '';
if ($next && !empty($next['starts_at'])) {
    $ns = \App\Support\Clock::toLocal((string) $next['starts_at']);
    $nextName = trim((string) (($next['first_name'] ?? '') . ' ' . ($next['last_name'] ?? '')));
    $nextLabel = $ns->format('H:i') . ($nextName !== '' ? ' · ' . $nextName : '');
}

$statusText = $occupied ? 'Obsazeno' : 'Volno';
$statusClass = $occupied ? 'is-busy' : 'is-free';
?>
<div class="adash">
    <header class="adash-hero">
        <div class="adash-hero-copy">
            <p class="eyebrow">SPRÁVA</p>
            <h1>Přehled</h1>
            <p class="muted"><?= e($nowLocal->format('l j. n. Y')) ?> · <?= e($nowLocal->format('H:i')) ?></p>
        </div>
        <div class="adash-live <?= e($statusClass) ?>">
            <span class="adash-live-pulse" aria-hidden="true"></span>
            <div>
                <strong><?= e($statusText) ?></strong>
                <span><?= $occupied ? e($guestName !== '' ? $guestName . ($slotLabel !== '' ? ' · ' . $slotLabel : '') : 'Aktivní rezervace') : ($nextLabel !== '' ? 'Další: ' . e($nextLabel) : 'Žádný další termín') ?></span>
            </div>
        </div>
    </header>

    <section
        class="adash-chart card"
        data-dash-chart
        data-chart="<?= e($chartJson) ?>"
        data-day-url="<?= e(url('/user/sprava/trzby?obdobi=den&datum=')) ?>"
        aria-label="Graf příjmu"
    >
        <div class="adash-chart-head">
            <div>
                <p class="eyebrow">PŘÍJEM</p>
                <h2><?= e(money_format_czk($chart['total'] ?? $stats['revenue'] ?? 0)) ?></h2>
                <p class="muted">Posledních 30 dní · klikni na den v grafu</p>
            </div>
            <div class="adash-chart-actions">
                <a class="btn btn-secondary" href="<?= e(url('/user/sprava/trzby?obdobi=dnes')) ?>">Dnes</a>
                <a class="btn btn-primary" href="<?= e(url('/user/sprava/trzby')) ?>">Detail tržeb</a>
            </div>
        </div>

        <div class="adash-chart-stage">
            <canvas data-dash-line width="800" height="260" aria-label="Vývoj tržeb"></canvas>
            <div class="adash-chart-tip" data-chart-tip hidden></div>
        </div>

        <div class="adash-chart-footer">
            <div class="adash-donut-wrap">
                <canvas data-dash-donut width="120" height="120" aria-hidden="true"></canvas>
                <ul class="adash-legend">
                    <li><i style="background:#c6f21a"></i>Rezervace <strong><?= e(money_format_czk($breakdown['reservations'] ?? 0)) ?></strong></li>
                    <li><i style="background:#6ec8ff"></i>Členství <strong><?= e(money_format_czk($breakdown['memberships'] ?? 0)) ?></strong></li>
                    <li><i style="background:#9aa49c"></i>Ostatní <strong><?= e(money_format_czk($breakdown['other'] ?? 0)) ?></strong></li>
                </ul>
            </div>
            <div class="adash-mini-kpis">
                <a href="<?= e(url('/user/sprava/trzby?obdobi=dnes')) ?>">
                    <span>Dnes</span>
                    <strong><?= e(money_format_czk($stats['revenue_today'] ?? 0)) ?></strong>
                </a>
                <a href="<?= e(url('/user/sprava/trzby?obdobi=vcera')) ?>">
                    <span>Včera</span>
                    <strong><?= e(money_format_czk($stats['revenue_yesterday'] ?? 0)) ?></strong>
                </a>
                <a href="<?= e(url('/user/sprava/trzby?obdobi=30d')) ?>">
                    <span>Plateb / 30 dní</span>
                    <strong><?= (int) ($stats['revenue_count_30'] ?? ($chart['count'] ?? 0)) ?></strong>
                </a>
            </div>
        </div>
    </section>

    <section class="adash-kpis" aria-label="Klíčové ukazatele">
        <article class="adash-kpi">
            <span>Rezervace dnes</span>
            <strong><?= (int) ($stats['today_reservations'] ?? 0) ?></strong>
        </article>
        <article class="adash-kpi">
            <span>Vstupy dnes</span>
            <strong><?= (int) ($stats['entries'] ?? 0) ?></strong>
        </article>
        <article class="adash-kpi<?= (int) ($stats['failed_access'] ?? 0) > 0 ? ' is-alert' : '' ?>">
            <span>Neúspěšné</span>
            <strong><?= (int) ($stats['failed_access'] ?? 0) ?></strong>
        </article>
        <article class="adash-kpi">
            <span>Aktivní členové</span>
            <strong><?= (int) ($stats['active_members'] ?? 0) ?></strong>
        </article>
        <a class="adash-kpi adash-kpi-link" href="<?= e(url('/user/sprava/zakaznici')) ?>">
            <span>Zákazníci</span>
            <strong><?= (int) ($stats['customers'] ?? 0) ?></strong>
        </a>
        <a class="adash-kpi adash-kpi-link" href="<?= e(url('/user/sprava/zajem')) ?>">
            <span>Zájem</span>
            <strong><?= (int) ($stats['interest'] ?? 0) ?></strong>
        </a>
    </section>

    <div class="adash-grid">
        <section class="adash-panel">
            <div class="adash-panel-head">
                <div>
                    <p class="eyebrow">DNES</p>
                    <h2>Harmonogram</h2>
                </div>
                <span class="badge badge-muted"><?= count($todayList) ?></span>
            </div>
            <?php if ($occupied && $reservation): ?>
                <div class="adash-now">
                    <span class="eyebrow">PRÁVĚ TEĎ</span>
                    <strong><?= e($guestName !== '' ? $guestName : 'Zákazník') ?></strong>
                    <span><?= e($slotLabel !== '' ? $slotLabel : 'Probíhající termín') ?></span>
                </div>
            <?php endif; ?>
            <?php if ($todayList === []): ?>
                <p class="adash-empty">Dnes žádné rezervace.<?= $nextLabel !== '' ? ' Další termín ' . e($nextLabel) . '.' : '' ?></p>
            <?php else: ?>
                <ul class="adash-timeline">
                    <?php foreach ($todayList as $row): ?>
                        <?php
                        $rs = \App\Support\Clock::toLocal((string) $row['starts_at']);
                        $re = \App\Support\Clock::toLocal((string) $row['ends_at']);
                        $name = trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')));
                        if ($name === '') {
                            $name = (string) ($row['username'] ?? 'Zákazník');
                        }
                        $isLive = $occupied && $reservation && (int) ($reservation['id'] ?? 0) === (int) ($row['id'] ?? 0);
                        $isPast = $re < $nowLocal;
                        $pending = ($row['status'] ?? '') === 'pending_payment';
                        ?>
                        <li class="adash-tl<?= $isLive ? ' is-live' : ($isPast ? ' is-past' : '') ?>">
                            <time><?= e($rs->format('H:i')) ?><span>–<?= e($re->format('H:i')) ?></span></time>
                            <div>
                                <?php if (!empty($row['user_public_id'])): ?>
                                    <a href="<?= e(url('/user/sprava/zakaznici/' . $row['user_public_id'])) ?>"><?= e($name) ?></a>
                                <?php else: ?>
                                    <strong><?= e($name) ?></strong>
                                <?php endif; ?>
                                <span><?= e((string) ($row['room_name'] ?? 'Studio')) ?><?= $pending ? ' · čeká na platbu' : '' ?></span>
                            </div>
                            <?php if ($isLive): ?>
                                <span class="badge badge-warn">Teď</span>
                            <?php elseif ($pending): ?>
                                <span class="badge badge-muted">Platba</span>
                            <?php elseif ($isPast): ?>
                                <span class="badge badge-done">Hotovo</span>
                            <?php else: ?>
                                <span class="badge badge-ok">Čeká</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <div class="adash-side">
            <section class="adash-panel">
                <div class="adash-panel-head">
                    <div>
                        <p class="eyebrow">DVEŘE</p>
                        <h2>Zámek</h2>
                    </div>
                    <?php if ($configured): ?>
                        <span class="badge <?= $online ? 'badge-ok' : 'badge-bad' ?>"><?= $online ? 'Online' : 'Offline' ?></span>
                    <?php else: ?>
                        <span class="badge badge-warn">Není nastaveno</span>
                    <?php endif; ?>
                </div>
                <dl class="adash-meta">
                    <div>
                        <dt>Provider</dt>
                        <dd><?= e(strtoupper((string) ($door['provider'] ?? 'n/a'))) ?></dd>
                    </div>
                    <div>
                        <dt>Režim</dt>
                        <dd><?= $testMode ? 'Test' : 'Produkce' ?></dd>
                    </div>
                    <div>
                        <dt>Stav</dt>
                        <dd><?= e((string) ($door['lock_state'] ?? '—')) ?></dd>
                    </div>
                    <div>
                        <dt>Baterie</dt>
                        <dd><?= isset($door['battery_percent']) && $door['battery_percent'] !== null && $door['battery_percent'] !== '' ? e((string) $door['battery_percent']) . '%' : '—' ?></dd>
                    </div>
                </dl>
                <a class="btn btn-secondary adash-panel-btn" href="<?= e(url('/user/sprava/vstup')) ?>">Správa dveří</a>
            </section>

            <section class="adash-panel">
                <div class="adash-panel-head">
                    <div>
                        <p class="eyebrow">UPOZORNĚNÍ</p>
                        <h2>Dnes</h2>
                    </div>
                    <span class="badge <?= count($deniedList) > 0 ? 'badge-bad' : 'badge-ok' ?>"><?= count($deniedList) ?></span>
                </div>
                <?php if ($deniedList === []): ?>
                    <p class="adash-empty">Žádné zamítnuté vstupy.</p>
                <?php else: ?>
                    <ul class="adash-alerts">
                        <?php foreach ($deniedList as $log): ?>
                            <?php
                            $who = trim((string) (($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')));
                            if ($who === '') {
                                $who = (string) ($log['username'] ?? 'Neznámý');
                            }
                            $when = \App\Support\Clock::toLocal((string) $log['created_at']);
                            ?>
                            <li>
                                <div>
                                    <?php if (!empty($log['public_id'])): ?>
                                        <a href="<?= e(url('/user/sprava/zakaznici/' . $log['public_id'])) ?>"><?= e($who) ?></a>
                                    <?php else: ?>
                                        <strong><?= e($who) ?></strong>
                                    <?php endif; ?>
                                    <span><?= e((string) ($log['denial_reason'] ?: 'Zamítnuto')) ?></span>
                                </div>
                                <time><?= e($when->format('H:i')) ?></time>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <nav class="adash-links" aria-label="Rychlé odkazy">
        <a href="<?= e(url('/user/sprava/trzby')) ?>"><strong>Tržby</strong><span>Platby a grafy</span></a>
        <a href="<?= e(url('/user/sprava/zakaznici')) ?>"><strong>Zákazníci</strong><span><?= (int) ($stats['customers'] ?? 0) ?> účtů</span></a>
        <a href="<?= e(url('/user/studio')) ?>"><strong>Studia</strong><span>Prostory</span></a>
        <a href="<?= e(url('/user/sprava/tarify')) ?>"><strong>Tarify</strong><span>Ceník</span></a>
        <a href="<?= e(url('/user/sprava/zajem')) ?>"><strong>Zájem</strong><span><?= (int) ($stats['interest'] ?? 0) ?> leadů</span></a>
        <a href="<?= e(url('/user/sprava/nastaveni')) ?>"><strong>Nastavení</strong><span>Pravidla</span></a>
    </nav>
</div>
