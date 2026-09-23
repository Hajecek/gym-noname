<div class="page-head">
    <div>
        <p class="eyebrow">SPRÁVA</p>
        <h1>Zákazníci</h1>
        <p class="muted">Kliknutím otevři profil. Blokaci přepni spínačem.</p>
    </div>
</div>

<form method="get" class="card cust-search">
    <div class="field">
        <label>Hledat</label>
        <input name="q" value="<?= e($q) ?>" placeholder="jméno, e-mail, username" autocomplete="off">
    </div>
    <button class="btn btn-secondary" type="submit">Filtrovat</button>
</form>

<?php if (!$users): ?>
<div class="card"><p class="muted">Žádní zákazníci.</p></div>
<?php else: ?>
<div class="cust-list">
<?php foreach ($users as $row):
    $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
    $isBlocked = ($row['status'] ?? '') === 'blocked';
    $isLifetime = ($row['membership_type'] ?? '') === 'lifetime';
    $profileUrl = url('/user/sprava/zakaznici/' . $row['public_id']);
    $label = $name !== '' ? $name : $row['username'];
    ?>
    <article class="card cust-row <?= $isBlocked ? 'is-blocked' : '' ?>" data-href="<?= e($profileUrl) ?>" tabindex="0" role="link">
        <div class="cust-row-main">
            <img class="avatar" src="<?= e(avatar_url($row)) ?>" alt="">
            <div class="cust-row-copy">
                <div class="cust-row-title">
                    <strong><?= e($name !== '' ? $name : '@' . $row['username']) ?></strong>
                    <?php if ($row['role'] === 'admin'): ?>
                    <span class="badge badge-muted"><?= e(role_label('admin')) ?></span>
                    <?php endif; ?>
                    <?php if ($isLifetime): ?>
                    <span class="badge badge-rare">Rare</span>
                    <?php endif; ?>
                </div>
                <p class="muted">@<?= e($row['username']) ?> · <?= e($row['email']) ?></p>
                <?php if (!empty($row['membership_name'])): ?>
                <p class="cust-row-plan"><?= e($row['membership_name']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="cust-row-actions" data-stop>
            <button
                type="button"
                class="cust-switch <?= $isBlocked ? 'is-on' : '' ?>"
                role="switch"
                aria-checked="<?= $isBlocked ? 'true' : 'false' ?>"
                data-cust-switch
                data-name="<?= e($label) ?>"
                data-redirect="list"
                data-block="<?= e(url('/user/sprava/zakaznici/' . $row['public_id'] . '/blokovat')) ?>"
                data-unblock="<?= e(url('/user/sprava/zakaznici/' . $row['public_id'] . '/odblokovat')) ?>"
                title="Blokace účtu"
            >
                <span class="cust-switch-track" aria-hidden="true"><span class="cust-switch-knob"></span></span>
                <span class="cust-switch-text" data-cust-switch-label><?= $isBlocked ? 'Blokováno' : 'Aktivní' ?></span>
            </button>
            <button type="button" class="btn btn-danger btn-sm" data-cust-open="delete"
                data-name="<?= e($label) ?>"
                data-action="<?= e(url('/user/sprava/zakaznici/' . $row['public_id'] . '/smazat')) ?>">Smazat</button>
        </div>
    </article>
<?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" hidden data-cust-form="block">
    <?= csrf_field() ?>
    <input type="hidden" name="redirect" value="list">
</form>
<form method="post" hidden data-cust-form="unblock">
    <?= csrf_field() ?>
    <input type="hidden" name="redirect" value="list">
</form>
<form method="post" hidden data-cust-form="delete">
    <?= csrf_field() ?>
</form>

<div class="cancel-modal" data-cust-modal hidden>
    <div class="cancel-modal-backdrop" data-cust-close></div>
    <section class="cancel-modal-panel" role="dialog" aria-modal="true" aria-labelledby="cust-modal-title">
        <p class="eyebrow" data-cust-eyebrow>Potvrzení</p>
        <h2 id="cust-modal-title" data-cust-title>Potvrdit akci</h2>
        <p class="muted" data-cust-body></p>
        <div class="cancel-actions">
            <button type="button" class="btn btn-secondary" data-cust-close>Zpět</button>
            <button type="button" class="btn btn-danger" data-cust-confirm>Potvrdit</button>
        </div>
    </section>
</div>
