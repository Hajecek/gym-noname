<div class="auth-card">
    <a class="logo" href="<?= e(url('/')) ?>"><span class="logo-mark"><span></span></span>PRIVOFIT</a>
    <h2 style="margin-top:24px"><?= !empty($mfa) ? 'Ověření přihlášení' : 'Přihlášení' ?></h2>
    <form method="post" class="card" style="margin-top:18px">
        <?= csrf_field() ?>
        <div class="field"><label>E-mail</label><input type="email" name="email" required value="<?= e($email ?? old('email')) ?>"></div>
        <?php if (empty($mfa)): ?>
            <div class="field"><label>Heslo</label><input type="password" name="password" required autocomplete="current-password"></div>
            <label class="check"><input type="checkbox" name="remember" value="1"> Zapamatovat přihlášení</label>
        <?php else: ?>
            <input type="hidden" name="password" value="">
            <input type="hidden" name="remember" value="<?= !empty($remember) ? '1' : '' ?>">
            <div class="field"><label>Kód z aplikace</label><input name="totp" inputmode="numeric" required></div>
        <?php endif; ?>
        <button class="btn btn-primary btn-block">Přihlásit se</button>
    </form>
    <p class="center muted"><a href="<?= e(url('/zapomenute-heslo')) ?>">Zapomenuté heslo</a> · <a href="<?= e(url('/registrace')) ?>">Vytvořit účet</a></p>
</div>
