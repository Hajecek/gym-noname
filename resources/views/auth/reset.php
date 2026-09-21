<main id="auth">
    <section class="auth-layout wrapper">
        <div class="auth-story">
            <a class="back" href="<?= e(url('/prihlaseni')) ?>">← Zpět na přihlášení</a>
            <div class="eyebrow">TVŮJ PROSTOR ZAČÍNÁ TADY</div>
            <h1>Nové<br><span>heslo.</span></h1>
            <p>Zvol si silné heslo, které ještě nikde neuniklo.</p>
        </div>
        <div class="auth-card">
            <h2>Nové heslo</h2>
            <p>Alespoň 12 znaků.</p>
            <?php require dirname(__DIR__) . '/partials/form-alert.php'; ?>
            <form method="post" action="<?= e(url('/obnoveni-hesla')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <label>Nové heslo<input type="password" name="password" required minlength="12" autocomplete="new-password"></label>
                <label>Potvrzení hesla<input type="password" name="password_confirmation" required minlength="12" autocomplete="new-password"></label>
                <div class="form-actions">
                    <button class="button submit-button" type="submit">Uložit heslo <span>↗</span></button>
                </div>
            </form>
        </div>
    </section>
</main>
