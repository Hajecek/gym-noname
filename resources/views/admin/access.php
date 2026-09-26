<?php
$status = is_array($status ?? null) ? $status : [];
$doors = is_array($doors ?? null) ? $doors : [];
$logs = is_array($logs ?? null) ? $logs : [];
$configured = !empty($status['configured']);
$online = !empty($status['online']);
$testMode = !empty($status['test_mode']);
$batteryCritical = !empty($status['battery_critical']);
$battery = $status['battery_percent'] ?? null;
$lockState = (string) ($status['lock_state'] ?? '');
$doorState = (string) ($status['door_state'] ?? '');
$provider = (string) ($status['provider'] ?? 'n/a');

$lockLabel = match (strtolower($lockState)) {
    'locked', 'zakleceno', 'locked_lock' => 'Zamčeno',
    'unlocked', 'odkleceno', 'unlocked_lock' => 'Odemčeno',
    'locking' => 'Zamyká se',
    'unlocking' => 'Odemyká se',
    'unlatched' => 'Odjištěno',
    '' => 'Neznámý',
    default => $lockState !== '' ? $lockState : 'Neznámý',
};

$heroClass = !$configured ? 'is-wait' : ($online ? 'is-open' : 'is-wait');
$heroEyebrow = !$configured ? 'Neaktivní' : ($online ? 'Online' : 'Offline');
$heroTitle = !$configured
    ? 'Dveře ještě nejsou nastavené'
    : ($online ? 'Zámek je připojený' : 'Zámek teď neodpovídá');
$heroLead = !$configured
    ? 'Přidej aktivní dveře a poskytovatele. Do té doby běží bezpečný testovací režim.'
    : ($testMode
        ? 'Testovací provider. Příkazy se ověří, fyzické dveře se neotevřou.'
        : 'Nuki token zůstává na serveru. Ostré otevírání jde jen přes ověřený backend.');
?>
<div class="page-head">
    <div>
        <p class="eyebrow">SPRÁVA</p>
        <h1>Vstupní systém</h1>
        <p class="muted">Stav zámku, režim dveří a logy vstupů členů.</p>
    </div>
</div>

<section class="entry-hero card door-admin-hero <?= e($heroClass) ?>">
    <div>
        <p class="eyebrow"><?= e($heroEyebrow) ?></p>
        <h2><?= e($heroTitle) ?></h2>
        <p class="entry-lead"><?= e($heroLead) ?></p>
        <div class="entry-facts door-admin-facts">
            <div>
                <span>Poskytovatel</span>
                <strong><?= e(strtoupper($provider)) ?></strong>
            </div>
            <div>
                <span>Režim</span>
                <strong><?= $testMode ? 'Test' : 'Produkce' ?></strong>
            </div>
            <div>
                <span>Zámek</span>
                <strong><?= e($lockLabel) ?></strong>
            </div>
            <div>
                <span>Baterie</span>
                <strong><?= $battery === null || $battery === '' ? '—' : e((string) $battery) . '%' ?></strong>
            </div>
        </div>
        <div class="door-admin-badges">
            <?php if ($configured): ?>
                <span class="badge <?= $online ? 'badge-ok' : 'badge-bad' ?>"><?= $online ? 'Online' : 'Offline' ?></span>
            <?php else: ?>
                <span class="badge badge-warn">Není nakonfigurováno</span>
            <?php endif; ?>
            <span class="badge <?= $testMode ? 'badge-warn' : 'badge-ok' ?>"><?= $testMode ? 'Test režim' : 'Nuki' ?></span>
            <?php if ($batteryCritical): ?>
                <span class="badge badge-bad">Kritická baterie</span>
            <?php endif; ?>
            <?php if ($doorState !== ''): ?>
                <span class="badge badge-muted">Dveře: <?= e($doorState) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="entry-mark" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <?php if ($online && $configured): ?>
                <rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 7.5-2"/>
            <?php else: ?>
                <rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>
            <?php endif; ?>
        </svg>
    </div>
</section>

<?php if ($doors !== []): ?>
<section class="card door-admin-panel">
    <div class="door-admin-panel-head">
        <div>
            <p class="eyebrow">ZAŘÍZENÍ</p>
            <h2>Registrované dveře</h2>
        </div>
        <span class="badge badge-muted"><?= count($doors) ?></span>
    </div>
    <div class="door-admin-list">
        <?php foreach ($doors as $door): ?>
            <?php
            $active = !empty($door['is_active']);
            $crit = !empty($door['battery_critical']);
            $pct = $door['last_battery_percent'] ?? null;
            ?>
            <article class="door-admin-item<?= $active ? '' : ' is-off' ?>">
                <div class="door-admin-item-main">
                    <strong><?= e((string) ($door['name'] ?? 'Dveře')) ?></strong>
                    <span class="muted"><?= e(strtoupper((string) ($door['provider'] ?? 'n/a'))) ?><?= !empty($door['external_id']) ? ' · ' . e((string) $door['external_id']) : '' ?></span>
                </div>
                <div class="door-admin-item-meta">
                    <span class="badge <?= $active ? 'badge-ok' : 'badge-muted' ?>"><?= $active ? 'Aktivní' : 'Neaktivní' ?></span>
                    <?php if ($pct !== null && $pct !== ''): ?>
                        <span class="badge <?= $crit ? 'badge-bad' : 'badge-muted' ?>"><?= e((string) $pct) ?>%</span>
                    <?php endif; ?>
                    <?php if (!empty($door['last_known_state'])): ?>
                        <span class="badge badge-muted"><?= e((string) $door['last_known_state']) ?></span>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="card door-admin-panel door-admin-verify">
    <div class="door-admin-panel-head">
        <div>
            <p class="eyebrow">KRITICKÁ AKCE</p>
            <h2>Testovací ověření</h2>
            <p class="muted">Pro potvrzení test režimu zadej znovu své heslo. Ostré otevření se aktivuje až po konfiguraci Nuki.</p>
        </div>
    </div>
    <form method="post" action="<?= e(url('/user/sprava/vstup/test')) ?>" class="door-admin-form">
        <?= csrf_field() ?>
        <div class="field">
            <label for="door-verify-password">Tvoje heslo</label>
            <input id="door-verify-password" type="password" name="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-danger" type="submit">Potvrdit testovací režim</button>
    </form>
</section>

<section class="card door-admin-panel">
    <div class="door-admin-panel-head">
        <div>
            <p class="eyebrow">HISTORIE</p>
            <h2>Logy vstupů</h2>
            <p class="muted">Posledních <?= count($logs) ?> pokusů o otevření.</p>
        </div>
    </div>
    <?php if ($logs === []): ?>
        <p class="door-admin-empty muted">Zatím žádné záznamy.</p>
    <?php else: ?>
        <div class="table-wrap door-admin-table">
            <table>
                <thead>
                    <tr>
                        <th>Čas</th>
                        <th>Uživatel</th>
                        <th>Autorizace</th>
                        <th>Příkaz</th>
                        <th>Důvod</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $log): ?>
                    <?php
                    $authOk = ($log['authorization_result'] ?? '') === 'granted';
                    $cmd = (string) ($log['command_result'] ?? 'not_sent');
                    $cmdClass = match ($cmd) {
                        'accepted' => 'badge-ok',
                        'failed', 'timeout', 'conflict' => 'badge-bad',
                        default => 'badge-muted',
                    };
                    $cmdLabel = match ($cmd) {
                        'accepted' => 'Přijat',
                        'failed' => 'Selhal',
                        'timeout' => 'Timeout',
                        'conflict' => 'Konflikt',
                        'not_sent' => 'Neodeslán',
                        default => $cmd,
                    };
                    ?>
                    <tr>
                        <td><?= e(format_datetime((string) ($log['created_at'] ?? ''))) ?></td>
                        <td><?= e((string) ($log['username'] ?? '—')) ?></td>
                        <td><span class="badge <?= $authOk ? 'badge-ok' : 'badge-bad' ?>"><?= $authOk ? 'Povoleno' : 'Zamítnuto' ?></span></td>
                        <td><span class="badge <?= e($cmdClass) ?>"><?= e($cmdLabel) ?></span></td>
                        <td class="muted"><?= e((string) ($log['denial_reason'] ?? '—')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
