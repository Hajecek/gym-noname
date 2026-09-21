<?php
$allowed = !empty($state['allowed']);
$reasons = $state['reasons'] ?? [];
$visit = is_array($visit ?? null) ? $visit : null;
$upcoming = is_array($upcoming ?? null) ? $upcoming : null;
$earlyMinutes = (int) ($earlyMinutes ?? 5);
$window = static function (array $row) use ($earlyMinutes): array {
    $start = \App\Support\Clock::toLocal((string) $row['starts_at']);
    $trainingEnd = \App\Support\Clock::toLocal((string) $row['ends_at']);
    $shownEnd = $trainingEnd->modify('+' . (int) ($row['buffer_minutes'] ?? 15) . ' minutes');
    return [
        'when' => $start->format('j. n. Y'),
        'span' => $start->format('H:i') . '–' . $shownEnd->format('H:i'),
        'open' => $start->modify('-' . $earlyMinutes . ' minutes')->format('H:i') . '–' . $shownEnd->format('H:i'),
        'room' => (string) ($row['room_name'] ?? 'Studio'),
    ];
};

$blocks = [];
if (in_array('unverified', $reasons, true)) {
    $blocks[] = 'Nejdřív ověř e-mail. Bez toho zámek účet nevezme.';
}
if (in_array('inactive', $reasons, true)) {
    $blocks[] = 'Účet není aktivní, takže dveře zůstanou zavřené.';
}
if (!$visit && $blocks === []) {
    $blocks[] = $upcoming
        ? 'Rezervaci máš, ale dveře se otevřou až ' . $earlyMinutes . ' minut před jejím začátkem.'
        : 'Nemáš potvrzenou rezervaci, která právě běží.';
}
?>
<div class="page-head">
    <div>
        <p class="eyebrow">TVŮJ KLÍČ</p>
        <h1>Vstup</h1>
        <p class="muted">Dveře se otevřou jen k tvé rezervaci. Pozdní příchod nevadí, platí celá rezervovaná doba.</p>
    </div>
    <button type="button" class="entry-info" data-entry-info aria-label="Jak vstup funguje">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <circle cx="12" cy="12" r="9"/>
            <path d="M12 11v6"/>
            <path d="M12 8h.01"/>
        </svg>
    </button>
</div>

<section class="entry-hero card <?= $allowed ? 'is-open' : 'is-wait' ?>">
    <div>
        <p class="eyebrow"><?= $allowed ? 'Právě teď' : 'Zatím zavřeno' ?></p>
        <h2><?= $allowed ? 'Dveře můžeš otevřít' : 'Teď tě zámek nepustí' ?></h2>
        <?php if ($allowed && $visit): ?>
            <?php $info = $window($visit); ?>
            <p class="entry-lead">Jsi v čase rezervace ve studiu <?= e($info['room']) ?>. Zámek se odemkne jen pro tebe a jen teď.</p>
            <div class="entry-facts">
                <div><span>Studio</span><strong><?= e($info['room']) ?></strong></div>
                <div><span>Termín</span><strong><?= e($info['span']) ?></strong></div>
                <div><span>Dveře jdou otevřít</span><strong><?= e($info['open']) ?></strong></div>
            </div>
            <form method="post" action="<?= e(url('/user/vstup')) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-primary entry-open">Otevřít dveře</button>
            </form>
        <?php else: ?>
            <p class="entry-lead"><?= e($blocks[0] ?? 'Vstup teď není povolený.') ?></p>
            <?php if ($upcoming): ?>
                <?php $next = $window($upcoming); ?>
                <div class="entry-facts">
                    <div><span>Další termín</span><strong><?= e($next['when']) ?></strong></div>
                    <div><span>Studio</span><strong><?= e($next['room']) ?></strong></div>
                    <div><span>Dveře od–do</span><strong><?= e($next['open']) ?></strong></div>
                </div>
            <?php endif; ?>
            <div class="entry-actions">
                <?php if (!$verified): ?>
                    <a class="btn btn-primary" href="<?= e(url('/user/overeni')) ?>">Ověřit e-mail</a>
                <?php endif; ?>
                <?php if (!$upcoming): ?>
                    <a class="btn btn-primary" href="<?= e(url('/user/rezervace')) ?>">Rezervovat termín</a>
                <?php else: ?>
                    <a class="btn btn-secondary" href="<?= e(url('/user/moje-rezervace')) ?>">Moje rezervace</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="entry-mark" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <?php if ($allowed): ?>
                <rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 7.5-2"/>
            <?php else: ?>
                <rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>
            <?php endif; ?>
        </svg>
    </div>
</section>

<div class="cancel-modal" data-entry-modal hidden>
    <div class="cancel-modal-backdrop" data-entry-close></div>
    <div class="cancel-modal-panel" role="dialog" aria-modal="true" aria-labelledby="entry-help-title">
        <p class="eyebrow">JAK TO FUNGUJE</p>
        <h2 id="entry-help-title">Kdy se dveře otevřou</h2>
        <ul class="entry-help">
            <li>
                <strong>Potvrzená rezervace</strong>
                Čekající platba dveře neotevře. Termín musí být zaplacený, nebo odečtený z členství.
            </li>
            <li>
                <strong>Jen v okně termínu</strong>
                Otevřít jde <?= (int) $earlyMinutes ?> min před začátkem a potom po celou rezervovanou dobu, i když přijdeš později.
            </li>
            <li>
                <strong>Kontrola až při stisku</strong>
                Účet, rezervace i zámek se ověří, až když stiskneš tlačítko. Samotná stránka dveře neodemyká.
            </li>
        </ul>
        <div class="cancel-actions">
            <button type="button" class="btn btn-primary" data-entry-close>Rozumím</button>
        </div>
    </div>
</div>

<?php if (!empty($state['test_mode'])): ?>
<p class="entry-note">Testovací zámek. Příkaz se ověří, fyzické dveře se neotevřou.</p>
<?php endif; ?>
