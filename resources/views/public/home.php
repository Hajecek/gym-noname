<section class="hero">
    <div class="container hero-grid">
        <div>
            <p class="eyebrow">PRIVOFIT</p>
            <h1><?= e($hero['title'] ?? 'Tvoje fitko. Tvůj prostor.') ?></h1>
            <p class="lead"><?= e(strip_tags($hero['body_html'] ?? 'Trénuj bez čekání na stroje, bez přeplněných prostor a bez kompromisů. Rezervuj si vlastní fitness studio a užij si trénink přesně podle sebe.')) ?></p>
            <div class="actions">
                <a class="btn btn-primary" href="<?= e(url('/registrace')) ?>">Rezervovat trénink</a>
                <a class="btn btn-secondary" href="#jak-to-funguje">Jak to funguje</a>
            </div>
        </div>
        <div class="card">
            <p class="eyebrow">Soukromý prostor</p>
            <h2>Celé studio jen pro tebe</h2>
            <p class="muted">Žádné fronty na stroje. Žádný hluk od cizích lidí. Jen tvoje tempo, tvoje hudba a tvoje soustředění.</p>
        </div>
    </div>
</section>

<section class="section" id="jak-to-funguje">
    <div class="container">
        <p class="eyebrow">Tři kroky</p>
        <h2>Jak PRIVOFIT funguje</h2>
        <div class="grid-3">
            <article class="card step"><div class="step-num">1</div><h3>Vyber si termín</h3><p class="muted">Otevři kalendář a zvol volný čas, který ti vyhovuje.</p></article>
            <article class="card step"><div class="step-num">2</div><h3>Rezervuj a zaplať</h3><p class="muted">Potvrď rezervaci členstvím nebo jednorázovým vstupem.</p></article>
            <article class="card step"><div class="step-num">3</div><h3>Přijď a otevři si fitko</h3><p class="muted">Vstup funguje přes aplikaci v čase tvé rezervace.</p></article>
        </div>
    </div>
</section>

<section class="section" style="background:var(--bg-secondary)">
    <div class="container">
        <p class="eyebrow">Důvody</p>
        <h2>Proč PRIVOFIT</h2>
        <div class="grid-3">
            <article class="card"><h3>Soukromé studio</h3><p class="muted">Během rezervace máš prostor pro sebe, nebo pro předem povolený počet osob.</p></article>
            <article class="card"><h3>Žádné čekání</h3><p class="muted">Stroje jsou volné. Trénink probíhá podle tebe, ne podle davu.</p></article>
            <article class="card"><h3>Vlastní tempo</h3><p class="muted">Klid, soustředění a svoboda. Bez kompromisů.</p></article>
            <article class="card"><h3>Online rezervace</h3><p class="muted">Termín vybereš z telefonu za pár sekund.</p></article>
            <article class="card"><h3>Moderní vybavení</h3><p class="muted">Přehled vybavení spravuje provozovatel a je vždy aktuální.</p></article>
            <article class="card"><h3>Samoobslužný vstup</h3><p class="muted">Dveře otevřeš aplikací po ověření platné rezervace.</p></article>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <p class="eyebrow">Studio</p>
        <h2>Vybavení fitka</h2>
        <?php if (!$equipment && !$media): ?>
            <div class="card"><p class="muted">Konkrétní vybavení a fotografie doplní provozovatel v administraci. Nechceme uvádět nic, co zatím nebylo potvrzeno.</p></div>
        <?php endif; ?>
        <div class="grid-3">
            <?php foreach ($equipment as $item): ?>
                <article class="card"><h3><?= e($item['name']) ?></h3><p class="muted"><?= e($item['description'] ?? '') ?></p></article>
            <?php endforeach; ?>
            <?php foreach ($media as $item): ?>
                <article class="card"><img src="<?= e(url('/uploads/' . basename($item['file_path']))) ?>" alt="<?= e($item['title']) ?>"><h3><?= e($item['title']) ?></h3></article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" style="background:var(--bg-secondary)">
    <div class="container">
        <p class="eyebrow">Ceník</p>
        <h2>Aktuální nabídka</h2>
        <div class="grid-3">
            <?php foreach ($plans as $plan): ?>
                <article class="card">
                    <h3><?= e($plan['name']) ?></h3>
                    <p class="stat-value"><?= e(money_format_czk($plan['price'])) ?></p>
                    <p class="muted"><?= e($plan['description'] ?? '') ?></p>
                    <a class="btn btn-primary" href="<?= e(url('/registrace')) ?>">Vybrat</a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container faq">
        <p class="eyebrow">FAQ</p>
        <h2>Časté otázky</h2>
        <?php foreach ($faqs as $faq): ?>
            <details><summary><?= e($faq['question']) ?></summary><p class="muted"><?= nl2br(e($faq['answer'])) ?></p></details>
        <?php endforeach; ?>
    </div>
</section>

<section class="section" style="background:var(--bg-secondary)">
    <div class="container grid-2">
        <div>
            <p class="eyebrow">Kontakt</p>
            <h2>Ozvěte se nám</h2>
            <p><?= e($contact['address']) ?></p>
            <p class="muted"><?= e($contact['email']) ?> · <?= e($contact['phone']) ?></p>
            <p class="muted"><?= e($contact['hours']) ?></p>
        </div>
        <form class="card" method="post" action="<?= e(url('/kontakt')) ?>">
            <?= csrf_field() ?>
            <div class="field"><label>Jméno</label><input name="name" required value="<?= e(old('name')) ?>"></div>
            <div class="field"><label>E-mail</label><input type="email" name="email" required value="<?= e(old('email')) ?>"></div>
            <div class="field"><label>Zpráva</label><textarea name="message" required><?= e(old('message')) ?></textarea></div>
            <button class="btn btn-primary btn-block" type="submit">Odeslat</button>
        </form>
    </div>
</section>
