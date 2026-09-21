<?php
$minutes = (int) ($idle_minutes ?? 1440);
if ($minutes >= 60 && $minutes % 60 === 0) {
    $hours = intdiv($minutes, 60);
    $span = $hours . ' ' . ($hours === 1 ? 'hodině' : 'hodinách');
} else {
    $span = $minutes . ' minutách';
}
?>
<main class="signed-out">
    <section class="signed-out-card">
        <p class="eyebrow">BEZPEČNOST</p>
        <h1>Odhlásili jsme tě.</h1>
        <p>Relace skončila po <?= e($span) ?> bez aktivity. Platí to i když je notebook zavřený.</p>
        <a class="button" href="<?= e(url('/prihlaseni')) ?>">Přihlásit se <span>↗</span></a>
    </section>
</main>
