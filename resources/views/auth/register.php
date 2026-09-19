<?php $errors = $errors ?? []; ?>
<div class="auth-card">
    <a class="logo" href="<?= e(url('/')) ?>"><span class="logo-mark"><span></span></span>PRIVOFIT</a>
    <h2 style="margin-top:24px">Vytvoř si svůj účet</h2>
    <p class="muted">Připoj se k PRIVOFIT a rezervuj si svůj vlastní prostor pro trénink.</p>
    <form method="post" enctype="multipart/form-data" class="card" style="margin-top:18px">
        <?= csrf_field() ?>
        <div class="avatar-picker">
            <img class="avatar avatar-lg" data-avatar-preview src="<?= e(avatar_url(null)) ?>" alt="Náhled avataru">
            <label class="btn btn-secondary" for="avatar">Nahrát foto</label>
            <input id="avatar" data-avatar-input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" hidden>
        </div>
        <div class="field"><label>Uživatelské jméno</label><input name="username" required minlength="3" maxlength="30" value="<?= e(old('username')) ?>"><span class="field-error"><?= e($errors['username'][0] ?? '') ?></span></div>
        <div class="row-2">
            <div class="field"><label>Jméno</label><input name="first_name" required value="<?= e(old('first_name')) ?>"><span class="field-error"><?= e($errors['first_name'][0] ?? '') ?></span></div>
            <div class="field"><label>Příjmení</label><input name="last_name" required value="<?= e(old('last_name')) ?>"></div>
        </div>
        <div class="field"><label>E-mail</label><input type="email" name="email" required value="<?= e(old('email')) ?>"><span class="field-error"><?= e($errors['email'][0] ?? '') ?></span></div>
        <div class="field"><label>Telefon</label><input type="tel" name="phone" value="<?= e(old('phone')) ?>"><span class="field-error"><?= e($errors['phone'][0] ?? '') ?></span></div>
        <div class="field"><label>Heslo</label><input type="password" name="password" required minlength="12" autocomplete="new-password"><span class="field-error"><?= e($errors['password'][0] ?? '') ?></span></div>
        <div class="field"><label>Potvrzení hesla</label><input type="password" name="password_confirmation" required minlength="12" autocomplete="new-password"></div>
        <label class="check"><input type="checkbox" name="terms" required> Souhlasím s <a href="<?= e(url('/dokument/obchodni-podminky')) ?>">obchodními podmínkami</a>.</label>
        <label class="check"><input type="checkbox" name="privacy" required> Potvrzuji seznámení se <a href="<?= e(url('/dokument/ochrana-udaju')) ?>">zásadami ochrany osobních údajů</a>.</label>
        <label class="check"><input type="checkbox" name="marketing"> Chci dostávat novinky (volitelné).</label>
        <button class="btn btn-primary btn-block" type="submit">Vytvořit účet</button>
    </form>
    <p class="center muted">Už máš účet? <a href="<?= e(url('/prihlaseni')) ?>">Přihlásit se</a></p>
</div>
