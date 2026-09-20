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
                    <a class="button magnetic" href="<?= e(url('/registrace')) ?>">Chci cvičit po svém <span>↗</span></a>
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
                <div class="eyebrow">01 — PROSTOR PRO ZMĚNU</div>
                <h2>Vypni okolní svět.<br><span>Zapni sebe.</span></h2>
            </div>
            <p>Fitko nemusí být plné lidí,<br>aby bylo plné možností.</p>
        </div>
        <div class="benefit-grid">
            <article class="benefit-card reveal tilt-card">
                <div class="card-top">
                    <span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="5" y="10" width="14" height="11" rx="3"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"/></svg></span>
                    <span>01</span>
                </div>
                <h3>Ve svém.<br>A sám sebou.</h3>
                <p>Žádné porovnávání. Žádné publikum. Jen klidný prostor, kde se můžeš soustředit na sebe.</p>
                <div class="card-foot">SOUKROMÍ <span>↗</span></div>
            </article>
            <article class="benefit-card reveal tilt-card">
                <div class="card-top">
                    <span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M7 5v14M3 8v8m14-11v14m4-11v8M7 12h10"/></svg></span>
                    <span>02</span>
                </div>
                <h3>Tvoje série.<br>Bez pořadníku.</h3>
                <p>Činky i stroje máš po ruce. Tvůj trénink má rytmus, který ti nikdo nerozhodí.</p>
                <div class="card-foot">SVOBODA <span>↗</span></div>
            </article>
            <article class="benefit-card reveal tilt-card">
                <div class="card-top">
                    <span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="9"/><path d="M12 6v6l4 2"/></svg></span>
                    <span>03</span>
                </div>
                <h3>Tempo si<br>určuješ ty.</h3>
                <p>První trénink nebo další osobní rekord? Nemusíš s nikým držet krok. Stačí začít.</p>
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
                <p>Ceník, adresa a dostupné časy budou zveřejněné před spuštěním rezervací. Aktuální nabídku najdeš také v <a href="<?= e(url('/cenik')) ?>">ceníku</a>.</p>
            </details>
        </div>
    </section>
    <section class="last-cta wrapper reveal">
        <div id="peek-stage" aria-hidden="true"></div>
        <div class="cta-surface">
            <div class="cta-top"><span>TVŮJ DALŠÍ KROK</span><span>↗</span></div>
            <h2>TAK CO,<br><span>JDEŠ DO TOHO?</span></h2>
            <div class="cta-bottom">
                <p>Udělej si čas na sebe.<br>Zbytek počká.</p>
                <a class="button button-dark magnetic" href="<?= e(url('/registrace')) ?>">Chci svůj prostor <span>↗</span></a>
            </div>
        </div>
    </section>
</main>
