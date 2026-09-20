<main id="interest">
    <section class="interest-page wrapper">
        <div class="cta-surface interest-surface">
            <div class="interest-split">
                <div class="interest-copy">
                    <div class="cta-top"><span>ZJIŠŤUJEME ZÁJEM</span></div>
                    <h1>Ještě neotevíráme.</h1>
                    <p>PRIVOFIT je soukromé fitness — celé studio jen pro tebe, bez cizích lidí a bez front. Teď sbíráme e-maily, abychom věděli, kolik lidí o to stojí.</p>
                    <ul class="interest-points">
                        <li>Nic se teď neplatí a nic se nerezervuje</li>
                        <li>Až spustíme první termíny, ozveme se ti jako první</li>
                    </ul>
                </div>
                <div class="interest-action">
                    <?php $source = 'zajem'; require dirname(__DIR__) . '/partials/interest-form.php'; ?>
                    <p class="interest-note">Žádný spam. Jen zpráva, až PRIVOFIT spustíme.</p>
                </div>
            </div>
        </div>
        <a class="interest-back" href="<?= e(url('/')) ?>">← Podívat se, o co jde</a>
    </section>
</main>
