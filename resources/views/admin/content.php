<div class="page-head">
    <div>
        <p class="eyebrow">SPRÁVA</p>
        <h1>Obsah webu</h1>
        <p class="muted">Kontaktní údaje a FAQ. Právní dokumenty jsou v souborech projektu.</p>
    </div>
</div>

<form method="post" class="card door-admin-panel">
    <?= csrf_field() ?>
    <div class="door-admin-panel-head">
        <div>
            <p class="eyebrow">KONTAKT</p>
            <h2>Údaje na webu</h2>
            <p class="muted">Zobrazí se na kontaktní stránce a v mobilní aplikaci.</p>
        </div>
    </div>
    <div class="settings-admin-fields">
        <div class="field">
            <label>Adresa</label>
            <input name="address" value="<?= e($contact['address']) ?>">
        </div>
        <div class="field">
            <label>E-mail</label>
            <input name="email" type="email" value="<?= e($contact['email']) ?>">
        </div>
        <div class="field">
            <label>Telefon</label>
            <input name="phone" value="<?= e($contact['phone']) ?>">
        </div>
        <div class="field">
            <label>Provozní info</label>
            <input name="hours" value="<?= e($contact['hours']) ?>">
        </div>
    </div>
    <div class="field">
        <label>Mapa (iframe HTML)</label>
        <textarea name="map_embed" rows="4"><?= e($contact['map_embed']) ?></textarea>
    </div>
    <button class="btn btn-primary" type="submit">Uložit kontakt</button>
</form>

<section class="card door-admin-panel">
    <div class="door-admin-panel-head">
        <div>
            <p class="eyebrow">DOKUMENTY</p>
            <h2>Právní texty</h2>
            <p class="muted">Obchodní podmínky a ochrana údajů se editují v <code>resources/content/</code>.</p>
        </div>
    </div>
    <div class="door-admin-item-meta">
        <a class="btn btn-secondary" href="<?= e(url('/dokument/obchodni-podminky')) ?>" target="_blank" rel="noopener">Obchodní podmínky</a>
        <a class="btn btn-secondary" href="<?= e(url('/dokument/ochrana-udaju')) ?>" target="_blank" rel="noopener">Ochrana údajů</a>
    </div>
</section>

<form method="post" action="<?= e(url('/user/sprava/obsah/faq')) ?>" class="card door-admin-panel">
    <?= csrf_field() ?>
    <div class="door-admin-panel-head">
        <div>
            <p class="eyebrow">FAQ</p>
            <h2>Nová otázka</h2>
        </div>
    </div>
    <div class="field"><label>Otázka</label><input name="question" required></div>
    <div class="field"><label>Odpověď</label><textarea name="answer" required></textarea></div>
    <button class="btn btn-secondary" type="submit">Přidat FAQ</button>
</form>

<?php if (!empty($faqs)): ?>
<section class="card door-admin-panel">
    <div class="door-admin-panel-head">
        <div>
            <p class="eyebrow">PŘEHLED</p>
            <h2>FAQ položky</h2>
        </div>
        <span class="badge badge-muted"><?= count($faqs) ?></span>
    </div>
    <div class="door-admin-list">
        <?php foreach ($faqs as $faq): ?>
            <article class="door-admin-item">
                <div class="door-admin-item-main">
                    <strong><?= e((string) $faq['question']) ?></strong>
                    <span class="muted"><?= e(mb_strimwidth(strip_tags((string) $faq['answer']), 0, 120, '…')) ?></span>
                </div>
                <span class="badge <?= !empty($faq['is_published']) ? 'badge-ok' : 'badge-muted' ?>">
                    <?= !empty($faq['is_published']) ? 'Publikováno' : 'Skryté' ?>
                </span>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
