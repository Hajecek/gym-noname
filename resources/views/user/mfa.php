<?php
$enabled = !empty($enabled);
$mfaRequired = !empty($mfaRequired);
$setup = $setup ?? null;
$errors = $errors ?? [];
$recoveryLeft = (int) ($recoveryLeft ?? 0);
?>
<div class="page-head">
    <div>
        <p class="eyebrow">ZABEZPEČENÍ</p>
        <h1>Dvoufaktorové ověření</h1>
        <p class="muted"><?= $enabled ? 'Účet je chráněný kódem z autentizační aplikace.' : 'Přidej druhý krok k přihlášení. Heslo samotné pak nestačí.' ?></p>
    </div>
    <a class="btn btn-secondary button-small" href="<?= e(url('/user/profil')) ?>">Zpět do profilu</a>
</div>

<?php if ($enabled): ?>
<section class="card profile-panel">
    <div class="profile-panel-head">
        <div>
            <p class="eyebrow">Stav</p>
            <h3>2FA je zapnuté</h3>
        </div>
        <span class="badge badge-ok">Aktivní</span>
    </div>
    <p class="muted">Při každém přihlášení po hesle zadáš šestimístný kód z aplikace, nebo jednorázový záložní kód.</p>
    <p class="profile-current">Zbývá záložních kódů: <?= $recoveryLeft ?></p>
    <?php if ($recoveryLeft === 0): ?>
        <p class="profile-note">Záložní kódy už nemáš. Pokud ztratíš telefon, bude potřeba obnovit přístup přes podporu.</p>
    <?php endif; ?>
    <?php if ($mfaRequired): ?>
        <p class="profile-note">Pro účet <?= e(role_label($user['role'] ?? 'admin')) ?> nelze dvoufaktorové ověření vypnout.</p>
    <?php else: ?>
        <div class="profile-divider"></div>
        <form method="post" action="<?= e(url('/user/zabezpeceni/mfa/vypnout')) ?>">
            <?= csrf_field() ?>
            <h3>Vypnout 2FA</h3>
            <p class="muted">Pro potvrzení zadej současné heslo.</p>
            <div class="field">
                <label>Současné heslo</label>
                <input type="password" name="current_password" required autocomplete="current-password">
                <span class="field-error"><?= e($errors['current_password'][0] ?? '') ?></span>
            </div>
            <button class="btn btn-danger">Vypnout dvoufaktorové ověření</button>
        </form>
    <?php endif; ?>
</section>
<?php else: ?>
<div class="profile-grid mfa-setup">
    <section class="card profile-panel">
        <p class="eyebrow">Krok 1</p>
        <h3>Naskenuj QR kód</h3>
        <p class="muted">Otevři Google Authenticator, 1Password, Authy nebo jinou TOTP aplikaci a přidej účet PRIVOFIT.</p>
        <div class="mfa-qr">
            <?php if (!empty($setup['qr_svg'])): ?>
                <?= $setup['qr_svg'] ?>
            <?php else: ?>
                <p class="muted">QR se nepodařilo vytvořit<?php if (!empty($setup['qr_error'])): ?>: <?= e((string) $setup['qr_error']) ?><?php endif; ?></p>
            <?php endif; ?>
        </div>
        <p class="profile-current-label">Nebo zadej klíč ručně</p>
        <p class="mfa-secret"><code><?= e($setup['secret_grouped'] ?? $setup['secret'] ?? '') ?></code></p>
        <button class="btn btn-secondary button-small" type="button" data-copy="<?= e($setup['secret'] ?? '') ?>">Kopírovat klíč</button>
    </section>
    <section class="card profile-panel">
        <p class="eyebrow">Krok 2</p>
        <h3>Potvrď kódem z aplikace</h3>
        <p class="muted">Až se účet v aplikaci objeví, zadej sem aktuální šestimístný kód.</p>
        <form method="post">
            <?= csrf_field() ?>
            <div class="field">
                <label>Ověřovací kód</label>
                <input name="code" required inputmode="numeric" autocomplete="one-time-code" maxlength="8" placeholder="123456">
            </div>
            <button class="btn btn-primary">Aktivovat 2FA</button>
        </form>
    </section>
</div>
<?php endif; ?>
