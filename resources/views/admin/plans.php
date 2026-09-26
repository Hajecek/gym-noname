<?php
$plans = is_array($plans ?? null) ? $plans : [];
$typeLabels = [
    'single' => 'Jednorázový',
    'pack' => 'Balíček',
    'monthly' => 'Měsíční',
    'credit' => 'Kredit',
    'voucher' => 'Voucher',
    'lifetime' => 'Doživotní',
];
$activeCount = count(array_filter($plans, static fn(array $p): bool => !empty($p['is_active'])));
?>
<div class="page-head">
    <div>
        <p class="eyebrow">SPRÁVA</p>
        <h1>Tarify</h1>
        <p class="muted">Ceník členství, balíčků a jednorázových vstupů.</p>
    </div>
</div>

<section class="entry-hero card plans-admin-hero is-open">
    <div>
        <p class="eyebrow">Ceník</p>
        <h2>Tarify studia</h2>
        <p class="entry-lead">Uprav ceny a dostupnost. Změny se projeví na webu i při nákupu členství.</p>
        <div class="entry-facts door-admin-facts">
            <div>
                <span>Celkem</span>
                <strong><?= count($plans) ?></strong>
            </div>
            <div>
                <span>Aktivní</span>
                <strong><?= $activeCount ?></strong>
            </div>
            <div>
                <span>Skryté</span>
                <strong><?= count($plans) - $activeCount ?></strong>
            </div>
            <div>
                <span>Měna</span>
                <strong>CZK</strong>
            </div>
        </div>
    </div>
    <div class="entry-mark" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <path d="M4 7h16v3H4z"/><path d="M6 10v7a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-7"/><path d="M9 14h6"/>
        </svg>
    </div>
</section>

<?php if ($plans === []): ?>
    <section class="card door-admin-panel">
        <p class="door-admin-empty muted">Zatím nemáš žádný tarif. Vytvoř první níže.</p>
    </section>
<?php endif; ?>

<div class="plans-admin-grid">
<?php foreach ($plans as $plan): ?>
    <?php
    $type = (string) ($plan['type'] ?? 'single');
    $typeLabel = $typeLabels[$type] ?? $type;
    $isActive = !empty($plan['is_active']);
    $price = (string) ($plan['price'] ?? '0');
    ?>
    <form class="card plans-admin-card<?= $isActive ? '' : ' is-off' ?>" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $plan['id'] ?>">
        <div class="plans-admin-card-head">
            <div>
                <p class="eyebrow"><?= e($typeLabel) ?></p>
                <h2><?= e((string) $plan['name']) ?></h2>
                <p class="plans-admin-price"><?= e(money_format_czk($price)) ?></p>
            </div>
            <span class="badge <?= $isActive ? 'badge-ok' : 'badge-muted' ?>"><?= $isActive ? 'Aktivní' : 'Skrytý' ?></span>
        </div>

        <div class="row-2">
            <div class="field">
                <label>Název</label>
                <input name="name" value="<?= e((string) $plan['name']) ?>" required>
            </div>
            <div class="field">
                <label>Cena (CZK)</label>
                <input name="price" inputmode="decimal" value="<?= e($price) ?>" required>
            </div>
        </div>

        <div class="field">
            <label>Popis</label>
            <textarea name="description" rows="3"><?= e((string) ($plan['description'] ?? '')) ?></textarea>
        </div>

        <div class="row-2">
            <div class="field">
                <label>Typ</label>
                <select name="type">
                    <?php foreach ($typeLabels as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $type === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Vstupy</label>
                <input name="entries" inputmode="numeric" value="<?= e((string) ($plan['entries'] ?? '')) ?>" placeholder="prázdné = neomezené">
            </div>
        </div>

        <div class="row-2">
            <div class="field">
                <label>Platnost (dny)</label>
                <input name="duration_days" inputmode="numeric" value="<?= e((string) ($plan['duration_days'] ?? '')) ?>" placeholder="např. 30">
            </div>
            <div class="field">
                <label>Max. hosté</label>
                <input name="max_guests" inputmode="numeric" value="<?= e((string) ($plan['max_guests'] ?? '0')) ?>">
            </div>
        </div>

        <div class="row-2">
            <div class="field">
                <label>Pořadí</label>
                <input name="sort_order" inputmode="numeric" value="<?= e((string) ($plan['sort_order'] ?? '0')) ?>">
            </div>
            <div class="field plans-admin-active">
                <label class="check">
                    <input type="checkbox" name="is_active" value="1" <?= $isActive ? 'checked' : '' ?>>
                    Zobrazit v ceníku
                </label>
            </div>
        </div>

        <div class="plans-admin-actions">
            <button class="btn btn-primary" type="submit">Uložit tarif</button>
            <?php if (!empty($plan['slug'])): ?>
                <span class="muted">slug: <?= e((string) $plan['slug']) ?></span>
            <?php endif; ?>
        </div>
    </form>
<?php endforeach; ?>
</div>

<section class="card door-admin-panel plans-admin-create">
    <div class="door-admin-panel-head">
        <div>
            <p class="eyebrow">NOVÝ TARIF</p>
            <h2>Vytvořit tarif</h2>
            <p class="muted">Po vytvoření doplníš popis, vstupy a pořadí v kartě výše.</p>
        </div>
    </div>
    <form method="post" class="plans-admin-create-form">
        <?= csrf_field() ?>
        <div class="row-2">
            <div class="field">
                <label>Název</label>
                <input name="name" required placeholder="Např. Balíček 10">
            </div>
            <div class="field">
                <label>Cena (CZK)</label>
                <input name="price" inputmode="decimal" required placeholder="0">
            </div>
        </div>
        <div class="row-2">
            <div class="field">
                <label>Typ</label>
                <select name="type">
                    <option value="single">Jednorázový</option>
                    <option value="pack">Balíček</option>
                    <option value="monthly">Měsíční</option>
                    <option value="lifetime">Doživotní</option>
                </select>
            </div>
            <div class="field">
                <label>Vstupy</label>
                <input name="entries" inputmode="numeric" placeholder="volitelné">
            </div>
        </div>
        <label class="check">
            <input type="checkbox" name="is_active" value="1" checked>
            Hned aktivní v ceníku
        </label>
        <button class="btn btn-secondary" type="submit">Vytvořit tarif</button>
    </form>
</section>
