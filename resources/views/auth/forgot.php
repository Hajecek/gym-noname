<main id="auth">
    <section class="auth-layout wrapper">
        <div class="auth-story">
            <a class="back" href="<?= e(url('/prihlaseni')) ?>">← Zpět na přihlášení</a>
            <div class="eyebrow">TVŮJ PROSTOR ZAČÍNÁ TADY</div>
            <h1>Obnovení<br><span>hesla.</span></h1>
            <p>Pošleme ti odkaz, pokud účet existuje.</p>
        </div>
        <div class="auth-card">
            <h2>Zapomenuté heslo</h2>
            <p>Zadej e-mail k účtu PRIVOFIT.</p>
            <?php require dirname(__DIR__) . '/partials/form-alert.php'; ?>
            <form method="post" action="<?= e(url('/zapomenute-heslo')) ?>">
                <?= csrf_field() ?>
                <label>E-mail<input type="email" name="email" required autocomplete="email"></label>
                <div class="form-actions">
                    <button class="button submit-button" type="submit">Odeslat odkaz <span>↗</span></button>
                </div>
            </form>
        </div>
    </section>
</main>
