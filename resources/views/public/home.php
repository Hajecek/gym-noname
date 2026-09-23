<main id="home">
    <section class="hero wrapper">
        <div class="hero-topline">
            <span><i></i> SOUKROMÉ FITNESS. MAXIMÁLNĚ TVOJE.</span>
            <span>MOVE DIFFERENT.</span>
        </div>
        <div class="hero-grid">
            <div class="hero-copy">
                <h1>
                    <span class="line">Tvoje tělo.</span>
                    <span class="line">Tvoje tempo.</span>
                    <span class="line lime">Tvoje pravidla<span class="period">.</span></span>
                </h1>
                <p>
                    Žádné fronty na stroje. Žádné cizí pohledy.<br>
                    Jen ty, tvůj playlist a prostor posunout se dál.
                </p>
                <div class="hero-actions">
                    <a class="button magnetic" href="<?= e(url('/zajem')) ?>">Chci cvičit po svém <span>↗</span></a>
                    <a class="text-button" href="#prostor">Prozkoumat prostor <span>↓</span></a>
                </div>
                <div class="hero-note">
                    <span>100 % soustředění</span>
                    <span>0 zbytečného rozptylování</span>
                </div>
            </div>
            <div class="hero-stage trainer-stage hero-character">
                <div class="character-halo" aria-hidden="true"></div>
                <div id="three-stage" role="button" tabindex="0" aria-label="Klikni na panáčka a zvětšuj mu svaly. Tažením ho můžeš otočit." aria-describedby="scene-hint">
                    <img id="scene-fallback" hidden alt="" width="1" height="1">
                    <span id="scene-loading">Tvůj parťák se rozcvičuje…</span>
                </div>
                <div class="character-hint">
                    <span class="click-spark">↗</span>
                    <span id="scene-hint">Klikni na mě. S každým klikem sílím.</span>
                    <span id="rep-count" aria-hidden="true">0</span>
                </div>
                <p id="trainer-status" class="sr-only" role="status" aria-live="polite"></p>
            </div>
        </div>
        <div class="hero-footer">
            <span>TVŮJ PROGRES ZAČÍNÁ TADY</span>
            <a href="#prostor" aria-label="Přejít na výhody">SCROLLNI SI PRO VÍC <span>↓</span></a>
        </div>
    </section>
    <div class="ticker" aria-label="Méně rozptylování. Více tebe.">
        <div class="ticker-track" aria-hidden="true">
            <span>MÉNĚ ROZPTYLOVÁNÍ</span><b>✳</b><span>VÍCE TEBE</span><b>✳</b>
            <span>YOUR SPACE. YOUR PACE.</span><b>✳</b>
            <span>MÉNĚ ROZPTYLOVÁNÍ</span><b>✳</b><span>VÍCE TEBE</span><b>✳</b>
            <span>YOUR SPACE. YOUR PACE.</span><b>✳</b>
        </div>
        <button id="motion-toggle" aria-label="Pozastavit animace" aria-pressed="false">Ⅱ</button>
    </div>
    <section class="benefits wrapper" id="prostor">
        <div class="section-heading reveal">
            <div>
                <div class="eyebrow">01 — PROČ PRIVOFIT</div>
                <h2>Hodně lidí<br>do fitka nejde.<br><span>A má to důvod.</span></h2>
            </div>
        </div>
        <div class="why-problem reveal">
            <p class="why-lead">Není to vždy o lenosti. Je to o cizích pohledech, o šatně plné lidí, o pocitu, že tam nepatříš. Někteří se bojí začít — protože nechtějí být na očích, když ještě neví, co s činkou. Jiní cvičit umí. Jen chtějí být sami a soustředit se jen na sebe.</p>
            <p class="why-resolve">PRIVOFIT je na tohle stavěný. Celé studio na tu hodinu jen pro tebe. Bez publika, bez front, bez tlaku vypadat, že to umíš. Můžeš začít pomalu. Můžeš jet naplno. Nikdo se nedívá.</p>
        </div>
        <div class="benefit-grid">
            <article class="benefit-card reveal tilt-card">
                <div class="card-top">
                    <span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="5" y="10" width="14" height="11" rx="3"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"/></svg></span>
                    <span>01</span>
                </div>
                <h3>Bojíš se jít<br>do fitka?</h3>
                <p>Cizí pohledy, plná zrcadla, pocit, že všichni vědí, co dělají — jen ty ne. Tady nejsi na očích. Nikdo tě nesrovnává. Můžeš začít z nuly a zůstat ve svém.</p>
                <div class="card-foot">BEZ PUBLIKA <span>↗</span></div>
            </article>
            <article class="benefit-card reveal tilt-card">
                <div class="card-top">
                    <span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M7 5v14M3 8v8m14-11v14m4-11v8M7 12h10"/></svg></span>
                    <span>02</span>
                </div>
                <h3>Chceš být sám.<br>A v klidu.</h3>
                <p>Ne proto, že bys lidi nesnášel. Protože soustředění potřebuje ticho. Playlist, dech, další série — bez malé talky, bez čekání na stroj, bez toho, aby u toho někdo stál.</p>
                <div class="card-foot">JEN TY <span>↗</span></div>
            </article>
            <article class="benefit-card reveal tilt-card">
                <div class="card-top">
                    <span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="9"/><path d="M12 6v6l4 2"/></svg></span>
                    <span>03</span>
                </div>
                <h3>Tempo si<br>určuješ ty.</h3>
                <p>První trénink nebo další osobní rekord? Nemusíš s nikým držet krok. Na tu hodinu je prostor tvůj — zavřeš dveře a jdeš si po svém.</p>
                <div class="card-foot">VLASTNÍ TEMPO <span>↗</span></div>
            </article>
        </div>
    </section>
    <section class="space-section wrapper">
        <div class="space-photo reveal">
            <img src="<?= e(url('/assets/marketing/assets/gym.png')) ?>" alt="Představa prostoru PRIVOFIT s činkami a posilovacími stroji" width="1536" height="1024" loading="lazy">
            <div class="space-overlay"></div>
            <div class="space-caption">
                <span>NECH VŠECHNO OSTATNÍ ZA DVEŘMI.</span>
                <h2>Tady máš<br><em>prostor.</em></h2>
            </div>
            <span class="photo-credit">VIZUALIZACE KONCEPTU</span>
            <div class="space-pill"><span>↗</span> MÍSTO PRO TVOJE LEPŠÍ JÁ</div>
        </div>
    </section>
    <section class="how wrapper" id="jak-to-funguje">
        <div class="how-title reveal">
            <div class="eyebrow">02 — JEDNODUŠE DO POHYBU</div>
            <h2>Méně řešení.<br><span>Více cvičení.</span></h2>
            <p>Od „měl bych“ k „jdu na to“.<br>Stačí tři kroky.</p>
            <a class="button button-outline magnetic" href="<?= e(url('/registrace')) ?>">Jdu do toho <span>↗</span></a>
        </div>
        <div class="steps">
            <article class="reveal">
                <span>01</span>
                <div>
                    <h3>Vytvoř si účet.</h3>
                    <p>Všechno začíná u tebe. Udělej první krok k vlastnímu prostoru na trénink.</p>
                </div>
            </article>
            <article class="reveal">
                <span>02</span>
                <div>
                    <h3>Najdi si svůj čas.</h3>
                    <p>Naplánuj si trénink tak, aby zapadl do tvého dne. Ne naopak.</p>
                </div>
            </article>
            <article class="reveal">
                <span>03</span>
                <div>
                    <h3>Otevři. Vypni. Makej.</h3>
                    <p>Nech okolní svět za dveřmi. Teď už se soustřeď jen na svůj další pohyb.</p>
                </div>
            </article>
        </div>
    </section>
    <section class="entry-section wrapper entry-cinema" id="vstup">
        <div class="entry-heading reveal">
            <div>
                <div class="eyebrow">TVŮJ TELEFON. TVŮJ KLÍČ.</div>
                <h2>Venku svět.<br><span>Uvnitř jen ty.</span></h2>
            </div>
            <p>Přijdeš. Otevřeš. Jsi ve svém.<br>Vyzkoušej si vstup vlastníma očima.</p>
        </div>
        <div class="phone-experience entry-theater reveal">
            <div class="entry-scene-label"><span>PRIVOFIT / VSTUP</span><span id="entry-scene-state">PŘED TVÝM PROSTOREM</span></div>
            <div id="phone-stage" tabindex="0" role="group" aria-label="Pohled očima návštěvníka před fitkem. Klikni na displej telefonu a otevři dveře. Šipkami se rozhlédni, Enter otevře dveře.">
                <p id="phone-loading">Připravujeme tvůj prostor…</p>
            </div>
            <div class="entry-scene-bottom">
                <span>POHLED TVÝMA OČIMA</span>
                <button type="button" id="entry-replay" aria-label="Zopakovat příchod ke dveřím">↺ Znovu</button>
            </div>
        </div>
        <div class="entry-control-row">
            <p id="phone-status" class="phone-status" role="status" aria-live="polite">Klepni na zelený displej. Tvůj prostor čeká.</p>
            <div class="phone-actions" aria-label="Ukázka aplikace">
                <button id="phone-home" type="button" aria-pressed="true">Vstup</button>
                <button id="phone-reserve" type="button" aria-pressed="false">Rezervace</button>
                <button id="phone-profile" type="button" aria-pressed="false">Profil</button>
                <button id="phone-enter" type="button">Otevřít dveře ↗</button>
            </div>
        </div>
        <div id="phone-booking-controls" class="phone-choice-controls" hidden>
            <label>Den<select id="phone-day"><option value="0">Dnes</option><option value="1">Zítra</option></select></label>
            <label>Čas<select id="phone-time"><option value="0">10:00</option><option value="1">14:00</option><option value="2" selected>17:00</option><option value="3">19:00</option></select></label>
        </div>
        <div class="entry-footnote">
            <span>01 Přijdeš ke dveřím</span>
            <span>02 Otevřeš telefonem</span>
            <span>03 Prostor je tvůj</span>
            <small>Interaktivní ukázka · bez napojení na skutečný zámek</small>
        </div>
    </section>
    <section class="statement">
        <div class="wrapper">
            <span class="eyebrow reveal">NEJLEPŠÍ INVESTICE? TY.</span>
            <p class="statement-text reveal">Nemusíš být<br><span>nejlepší v místnosti.</span><br>Ta místnost je tvoje.</p>
            <a href="<?= e(url('/registrace')) ?>" class="circle-link magnetic" aria-label="Vytvořit účet">↗</a>
        </div>
    </section>
    <section class="pricing wrapper" id="cenik">
        <?php
        $hourly = (float) ($hourlyPrice ?? 150);
        $singlePrice = $hourly;
        foreach ($plans ?? [] as $plan) {
            if (($plan['type'] ?? '') === 'single') {
                $singlePrice = (float) $plan['price'];
            }
        }
        ?>
        <div class="section-heading reveal">
            <div>
                <div class="eyebrow">04 — CENÍK</div>
                <h2>Jak často<br><span>chceš přijít?</span></h2>
            </div>
        </div>
        <div class="price-grid">
            <?php
            foreach ($plans ?? [] as $plan):
                $type = (string) ($plan['type'] ?? '');
                $featured = $type === 'monthly';
                $price = (float) $plan['price'];
                $entries = $plan['entries'];
                if ($entries === null && $type === 'monthly') {
                    $entriesLabel = 'Neomezené vstupy';
                } else {
                    $count = (int) $entries;
                    $entriesLabel = $count === 1 ? '1 vstup' : ($count > 1 && $count < 5 ? $count . ' vstupy' : $count . ' vstupů');
                }
                $lines = ['Den si vybereš po zaplacení'];
                if ($type === 'single') {
                    $lines = [
                        'Jeden vstup do studia',
                        '1 h 15 min',
                        'Den si vybereš po zaplacení',
                    ];
                } elseif ($type === 'pack' && (int) $entries > 0) {
                    $unit = $price / (int) $entries;
                    $saved = max(0, $singlePrice * (int) $entries - $price);
                    $lines = [
                        money_format_czk($unit) . ' za vstup',
                        $saved > 0 ? 'Ušetříš ' . money_format_czk($saved) . ' proti jednorázovým' : 'Levnější než jednorázové vstupy',
                        'Platí 180 dní',
                    ];
                } elseif ($type === 'monthly') {
                    $included = $singlePrice > 0 ? (int) round($price / $singlePrice) : 0;
                    $includedLabel = $included === 1 ? '1 vstup v ceně' : ($included > 1 && $included < 5 ? $included . ' vstupy v ceně' : $included . ' vstupů v ceně');
                    $lines = [
                        'Neomezené vstupy na 30 dní',
                        $included > 0 ? $includedLabel . ', další už zdarma' : 'Když chodíš víckrát do týdne',
                        'Jeden termín = jeden vstup',
                    ];
                }
            ?>
            <article class="price-card reveal<?= $featured ? ' is-featured' : '' ?>">
                <?php if ($featured): ?><p class="price-flag">Pravidelně</p><?php endif; ?>
                <h3><?= e($plan['name']) ?></h3>
                <p class="price-amount"><?= e(money_format_czk($plan['price'])) ?></p>
                <p class="price-entries"><?= e($entriesLabel) ?></p>
                <ul>
                    <?php foreach ($lines as $line): ?>
                        <li><?= e($line) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($user): ?>
                    <form method="post" action="<?= e(url('/user/clenstvi')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="plan" value="<?= e($plan['public_id']) ?>">
                        <button class="button" type="submit">Zaplatit <span>↗</span></button>
                    </form>
                <?php else: ?>
                    <a class="button" href="<?= e(url('/user/clenstvi')) ?>">Přihlas se a zaplať <span>↗</span></a>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
        <p class="price-note reveal">Bez tarifu stojí blok taky <?= e(money_format_czk($hourly)) ?> · 1 h 15 min. Dva bloky <?= e(money_format_czk($hourly * 2)) ?>, tři <?= e(money_format_czk($hourly * 3)) ?>. <a href="<?= e(url('/user/rezervace')) ?>">Rezervace</a> i platba tarifu jdou až z účtu.</p>
    </section>
    <section class="faq wrapper" id="otazky">
        <div class="section-heading reveal">
            <div>
                <div class="eyebrow">03 — DOBRÉ VĚDĚT</div>
                <h2>Ještě něco<br><span>v hlavě?</span></h2>
            </div>
            <p>Odpovědi, ať můžeš<br>v klidu začít.</p>
        </div>
        <div class="faq-list reveal">
            <details>
                <summary>Je PRIVOFIT i pro začátečníky?<span>+</span></summary>
                <p>Ano. Soukromý prostor ti dává možnost začít vlastním tempem a bez porovnávání s ostatními. Při cvičení vždy respektuj své možnosti.</p>
            </details>
            <details>
                <summary>Co znamená soukromé fitness?<span>+</span></summary>
                <p>Koncept PRIVOFIT je postavený na prostoru vyhrazeném pro tvůj trénink. Podmínky jednotlivých rezervací a kapacita budou uvedené při spuštění provozu.</p>
            </details>
            <details>
                <summary>Co si mám vzít na trénink?<span>+</span></summary>
                <p>Pohodlné sportovní oblečení, čistou obuv do interiéru, ručník a vodu. A chuť udělat něco pro sebe.</p>
            </details>
            <details>
                <summary>Kde najdu ceny a dostupné termíny?<span>+</span></summary>
                <p>Jeden vstup stojí <?= e(money_format_czk($singlePrice ?? $hourlyPrice ?? 150)) ?>. Balíček a měsíc vycházejí líp, když chodíš častěji. Ceny jsou v <a href="#cenik">ceníku</a>.</p>
            </details>
        </div>
    </section>
    <section class="last-cta wrapper reveal" id="zajem">
        <div id="peek-stage" aria-hidden="true"></div>
        <div class="cta-surface">
            <div class="interest-split">
                <div class="interest-copy">
                    <div class="cta-top"><span>ZJIŠŤUJEME ZÁJEM</span></div>
                    <h2>Ještě neotevíráme.</h2>
                    <p>PRIVOFIT je soukromé fitness — celé studio jen pro tebe, bez cizích lidí a bez front. Teď sbíráme e-maily, abychom věděli, kolik lidí o to stojí.</p>
                    <ul class="interest-points">
                        <li>Tarif i rezervace se platí až z přihlášeného účtu</li>
                        <li>Až spustíme první termíny, ozveme se ti jako první</li>
                    </ul>
                </div>
                <div class="interest-action">
                    <?php $source = 'home'; require dirname(__DIR__) . '/partials/interest-form.php'; ?>
                    <p class="interest-note">Žádný spam. Jen zpráva, až PRIVOFIT spustíme.</p>
                </div>
            </div>
        </div>
    </section>
</main>
