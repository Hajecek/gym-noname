<?php $source = $source ?? 'home'; ?>
<form class="interest-form" method="post" action="<?= e(url('/zajem')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="source" value="<?= e($source) ?>">
    <input class="interest-honeypot" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
    <label class="sr-only" for="interest-email-<?= e($source) ?>">E-mail</label>
    <input id="interest-email-<?= e($source) ?>" type="email" name="email" required maxlength="190" autocomplete="email" placeholder="tvuj@email.cz" value="<?= e(old('email')) ?>">
    <button class="button button-dark" type="submit">Ozvěte se <span>↗</span></button>
</form>
