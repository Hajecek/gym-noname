<div class="auth-card">
    <h2>Nové heslo</h2>
    <form method="post" class="card">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="field"><label>Nové heslo</label><input type="password" name="password" required minlength="12" autocomplete="new-password"></div>
        <div class="field"><label>Potvrzení hesla</label><input type="password" name="password_confirmation" required minlength="12" autocomplete="new-password"></div>
        <button class="btn btn-primary btn-block">Uložit heslo</button>
    </form>
</div>
