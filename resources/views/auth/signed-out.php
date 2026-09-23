<?php
$reason = (string) ($logout_reason ?? 'idle');
$message = trim((string) ($logout_message ?? ''));
$minutes = (int) ($idle_minutes ?? 1440);
if ($minutes >= 60 && $minutes % 60 === 0) {
    $hours = intdiv($minutes, 60);
    $span = $hours . ' ' . ($hours === 1 ? 'hodině' : 'hodinách');
} else {
    $span = $minutes . ' minutách';
}

$isForced = in_array($reason, ['blocked', 'deleted'], true);
$eyebrow = match ($reason) {
    'blocked' => 'Účet zablokován',
    'deleted' => 'Účet smazán',
    default => 'Bezpečnost',
};
$title = match ($reason) {
    'blocked' => 'Přístup je pozastavený.',
    'deleted' => 'Účet už neexistuje.',
    default => 'Odhlásili jsme tě.',
};
$lead = match ($reason) {
    'blocked' => 'Do PRIVOFIT se teď nepřihlásíš. Pokud to chceš řešit, ozvi se nám.',
    'deleted' => 'Tvůj účet byl trvale odstraněn a už se k němu nejde vrátit.',
    default => 'Relace skončila po ' . $span . ' bez aktivity. Platí to i když je notebook zavřený.',
};
if ($message === '') {
    $message = match ($reason) {
        'blocked' => 'Tvůj účet byl zablokován administrátorem.',
        'deleted' => 'Tvůj účet PRIVOFIT byl smazán administrátorem.',
        default => '',
    };
}
$showReasonBox = $isForced && $message !== '';
$ctaHref = $reason === 'deleted' ? url('/') : url('/prihlaseni');
$ctaLabel = $reason === 'deleted' ? 'Zpět na web' : 'Přihlásit se';
?>
<main class="signed-out is-<?= e($reason) ?>">
    <section class="signed-out-card" aria-labelledby="signed-out-title">
        <div class="signed-out-brand">
            <?php $brandHref = url('/'); $brandLabel = 'PRIVOFIT'; require dirname(__DIR__) . '/partials/brand-logo.php'; ?>
        </div>

        <div class="signed-out-icon" aria-hidden="true">
            <?php if ($reason === 'blocked'): ?>
            <svg viewBox="0 0 24 24" fill="none"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/><circle cx="12" cy="16" r="1.2" fill="currentColor"/></svg>
            <?php elseif ($reason === 'deleted'): ?>
            <svg viewBox="0 0 24 24" fill="none"><path d="M5 8h14"/><path d="M9 8V5h6v3"/><path d="M7 8l1 12h8l1-12"/><path d="M10 12v5M14 12v5"/></svg>
            <?php else: ?>
            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l2.5 1.5"/></svg>
            <?php endif; ?>
        </div>

        <p class="signed-out-eyebrow"><?= e($eyebrow) ?></p>
        <h1 id="signed-out-title"><?= e($title) ?></h1>
        <p class="signed-out-lead"><?= e($lead) ?></p>

        <?php if ($showReasonBox): ?>
        <div class="signed-out-reason">
            <span>Zpráva</span>
            <p><?= e($message) ?></p>
        </div>
        <?php endif; ?>

        <div class="signed-out-actions">
            <a class="button" href="<?= e($ctaHref) ?>"><?= e($ctaLabel) ?> <span>↗</span></a>
            <?php if ($reason === 'blocked'): ?>
            <a class="signed-out-link" href="<?= e(url('/kontakt')) ?>">Kontaktovat podporu</a>
            <?php elseif ($reason === 'idle'): ?>
            <a class="signed-out-link" href="<?= e(url('/')) ?>">Zpět na web</a>
            <?php endif; ?>
        </div>
    </section>
</main>
