<div class="auth-card">
    <h2>Zapomenuté heslo</h2>
    <p class="muted">Zadejte e-mail. Pokud účet existuje, pošleme odkaz k obnovení.</p>
    <form method="post" class="card">
        <?= csrf_field() ?>
        <div class="field"><label>E-mail</label><input type="email" name="email" required></div>
        <button class="btn btn-primary btn-block">Odeslat odkaz</button>
    </form>
</div>
