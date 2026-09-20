<main id="interest">
    <section class="interest-page wrapper">
        <div id="peek-stage" aria-hidden="true"></div>
        <div class="cta-surface interest-surface">
            <div class="cta-top"><span>ZJIŠŤUJEME ZÁJEM</span><span>↗</span></div>
            <h1>Ještě neotevíráme.<br><span>Nejdřív zjišťujeme zájem.</span></h1>
            <div class="interest-page-copy">
                <p>PRIVOFIT je soukromé fitness. Celé studio jen pro tebe, v čase, který si rezervuješ. Žádné fronty, žádné cizí pohledy.</p>
                <p>Ještě neotevíráme. Teď potřebujeme zjistit zájem — jestli by o něco takového stálo dost lidí, abychom to spustili.</p>
                <p>Když ano, nech e-mail. Ozveme se, až bude prostor připravený.</p>
                <?php $source = 'zajem'; require dirname(__DIR__) . '/partials/interest-form.php'; ?>
                <p class="interest-note">Žádný spam. Jen zpráva, až PRIVOFIT spustíme.</p>
                <a class="interest-back" href="<?= e(url('/')) ?>">← Podívat se, o co jde</a>
            </div>
        </div>
    </section>
</main>
