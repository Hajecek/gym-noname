<?php
$errors = $errors ?? [];
$hasAvatar = \App\Services\AvatarService::resolveFile((string) ($user['avatar_path'] ?? '')) !== null;
$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$planName = $membership['plan_name'] ?? 'Bez tarifu';
$mfaEnabled = !empty($mfaEnabled);
$mfaRequired = !empty($mfaRequired);
$initials = mb_strtoupper(mb_substr((string) ($user['first_name'] ?? ''), 0, 1) . mb_substr((string) ($user['last_name'] ?? ''), 0, 1));
if ($initials === '') {
    $initials = mb_strtoupper(mb_substr((string) ($user['username'] ?? 'PF'), 0, 2)) ?: 'PF';
}
?>
<div class="page-head">
    <div>
        <p class="eyebrow">ÚČET</p>
        <h1>Profil</h1>
        <p class="muted">Fotka, údaje, přístup a zabezpečení.</p>
    </div>
</div>

<section class="card profile-hero">
    <p class="eyebrow">Identita</p>
    <form class="profile-hero-main" method="post" action="<?= e(url('/user/profil/avatar')) ?>" enctype="multipart/form-data" data-avatar-picker>
        <?= csrf_field() ?>
        <input id="profile-avatar-file" data-avatar-input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" hidden>
        <label class="profile-avatar-edit" for="profile-avatar-file">
            <span class="profile-avatar-preview<?= $hasAvatar ? ' has-photo' : '' ?>" data-avatar-preview>
                <span data-avatar-initials<?= $hasAvatar ? ' hidden' : '' ?>><?= e($initials) ?></span>
                <img data-avatar-preview-img<?= $hasAvatar ? ' src="' . e(avatar_url($user)) . '"' : ' hidden' ?> alt="">
            </span>
            <span class="profile-avatar-cam" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 8h2.5l1.6-2.4h6L17.7 8H20a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-9a2 2 0 0 1 2-2z"/><circle cx="12.5" cy="14" r="3.4"/></svg>
            </span>
        </label>
        <div class="profile-hero-copy">
            <h2><?= e($fullName !== '' ? $fullName : $user['username']) ?></h2>
            <p class="muted">@<?= e($user['username']) ?> · <?= e(role_label($user['role'] ?? 'user')) ?></p>
            <dl class="profile-facts">
                <div>
                    <dt>Člen od</dt>
                    <dd><?= e(format_datetime($user['created_at'], 'd. m. Y')) ?></dd>
                </div>
                <div class="is-plan">
                    <dt>Tarif</dt>
                    <dd><?= e($planName) ?></dd>
                </div>
            </dl>
            <p class="avatar-picker-error" data-avatar-error hidden></p>
            <div class="profile-hero-actions">
                <button class="btn btn-primary button-small" type="submit" data-avatar-save hidden>Uložit fotku</button>
                <label class="btn btn-secondary button-small" for="profile-avatar-file">Změnit fotku</label>
                <?php if ($hasAvatar): ?>
                    <button class="btn btn-ghost button-small" type="submit" form="avatar-delete-form">Odstranit</button>
                <?php endif; ?>
            </div>
            <p class="profile-hero-hint">JPEG, PNG nebo WebP do 5 MB. Fotka se ořízne do čtverce.</p>
        </div>
    </form>
</section>
<?php if ($hasAvatar): ?>
<form id="avatar-delete-form" method="post" action="<?= e(url('/user/profil/avatar/smazat')) ?>"><?= csrf_field() ?></form>
<?php endif; ?>

<form class="card profile-section" method="post" action="<?= e(url('/user/profil')) ?>">
    <?= csrf_field() ?>
    <p class="eyebrow">Osobní údaje</p>
    <h3>O tobě</h3>
    <div class="field">
        <label>Uživatelské jméno</label>
        <input name="username" value="<?= e($user['username']) ?>" autocomplete="username">
        <span class="field-error"><?= e($errors['username'][0] ?? '') ?></span>
    </div>
    <div class="row-2">
        <div class="field"><label>Jméno</label><input name="first_name" value="<?= e($user['first_name']) ?>" autocomplete="given-name"></div>
        <div class="field"><label>Příjmení</label><input name="last_name" value="<?= e($user['last_name']) ?>" autocomplete="family-name"></div>
    </div>
    <div class="field"><label>Telefon</label><input name="phone" value="<?= e($user['phone'] ?? '') ?>" autocomplete="tel"></div>
    <button class="btn btn-primary">Uložit změny</button>
</form>

<form class="card profile-section" method="post" action="<?= e(url('/user/profil/email')) ?>">
    <?= csrf_field() ?>
    <p class="eyebrow">Přístup</p>
    <h3>E-mail</h3>
    <p class="profile-current-label">Aktuální e-mail</p>
    <p class="profile-current"><?= e($user['email']) ?></p>
    <div class="field"><label>Nový e-mail</label><input type="email" name="email" required autocomplete="email"></div>
    <button class="btn btn-secondary">Odeslat ověření</button>
</form>

<form class="card profile-section" method="post" action="<?= e(url('/user/profil/heslo')) ?>">
    <?= csrf_field() ?>
    <h3>Heslo</h3>
    <div class="field"><label>Současné heslo</label><input type="password" name="current_password" required autocomplete="current-password"></div>
    <div class="field"><label>Nové heslo</label><input name="password" type="password" required minlength="12" autocomplete="new-password"></div>
    <div class="field"><label>Potvrzení</label><input name="password_confirmation" type="password" required minlength="12" autocomplete="new-password"></div>
    <button class="btn btn-primary">Změnit heslo</button>
</form>

<section class="card profile-section">
    <p class="eyebrow">Zabezpečení</p>
    <div class="profile-panel-head">
        <h3>Dvoufaktorové ověření</h3>
        <span class="badge <?= $mfaEnabled ? 'badge-ok' : 'badge-warn' ?>"><?= $mfaEnabled ? 'Aktivní' : 'Vypnuto' ?></span>
    </div>
    <p class="muted"><?= $mfaEnabled ? 'Heslo samo účet neotevře. Po něm je ještě druhý krok.' : 'Heslo samo o sobě účet neotevře. Přidej druhý krok.' ?></p>
    <ul class="profile-mfa-points">
        <li>
            <span class="session-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="6" y="3" width="12" height="18" rx="2"/><path d="M10 18h4"/></svg>
            </span>
            <span>
                <strong>Kód z aplikace</strong>
                <span>Šest číslic z Authenticatoru, 1Password nebo Authy.</span>
            </span>
        </li>
        <li>
            <span class="session-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M7 11V8a5 5 0 0 1 10 0v3"/><rect x="5" y="11" width="14" height="9" rx="2"/></svg>
            </span>
            <span>
                <strong>Záložní kód</strong>
                <span>Jednorázový kód, když nemáš telefon po ruce.</span>
            </span>
        </li>
    </ul>
    <?php if ($mfaRequired): ?>
        <p class="profile-note">U účtu <?= e(role_label($user['role'] ?? 'admin')) ?> je zapnutí povinné.</p>
    <?php endif; ?>
    <div class="profile-hero-actions">
        <?php if ($mfaEnabled): ?>
            <a class="btn btn-secondary" href="<?= e(url('/user/zabezpeceni/mfa')) ?>">Spravovat</a>
        <?php else: ?>
            <a class="btn btn-primary" href="<?= e(url('/user/zabezpeceni/mfa')) ?>">Zapnout 2FA</a>
        <?php endif; ?>
    </div>
</section>

<section class="card profile-section">
    <h3>Aktivní zařízení</h3>
    <ul class="session-list">
        <?php foreach ($sessions as $session): ?>
            <?php $isCurrent = (int) $session['id'] === (int) $currentSession; ?>
            <li class="session-item">
                <div class="session-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="5" y="4" width="14" height="16" rx="2"/><path d="M9 8h6M9 12h6M9 16h3"/></svg>
                </div>
                <div class="session-copy">
                    <strong><?= e(device_label($session['user_agent'] ?? '')) ?><?= $isCurrent ? ' · toto zařízení' : '' ?></strong>
                    <span><?= e($session['ip_address'] ?? '—') ?> · <?= e(format_datetime($session['last_activity_at'])) ?></span>
                </div>
                <?php if (!$isCurrent): ?>
                    <form method="post" action="<?= e(url('/user/profil/relace/' . $session['id'] . '/odhlasit')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-ghost button-small" type="submit">Odhlásit</button>
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <form method="post" action="<?= e(url('/user/profil/odhlasit-vse')) ?>">
        <?= csrf_field() ?>
        <button class="btn btn-secondary" type="submit">Odhlásit ostatní zařízení</button>
    </form>
</section>

<section class="card profile-section profile-privacy">
    <div>
        <p class="eyebrow">Soukromí</p>
        <h3>Ochrana osobních údajů</h3>
        <p class="muted">Export obsahuje údaje účtu, rezervace, členství a platby.</p>
    </div>
    <div class="profile-hero-actions">
        <a class="btn btn-secondary" href="<?= e(url('/user/profil/export')) ?>">Exportovat data</a>
        <form method="post" action="<?= e(url('/user/profil/vymaz')) ?>"><?= csrf_field() ?><button class="btn btn-danger">Žádost o výmaz</button></form>
    </div>
</section>
