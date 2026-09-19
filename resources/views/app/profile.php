<div class="page-head">
    <div>
        <img class="avatar avatar-lg" src="<?= e(avatar_url($user)) ?>" alt="">
        <h1><?= e($user['first_name'] . ' ' . $user['last_name']) ?></h1>
        <p class="muted">@<?= e($user['username']) ?> · člen od <?= e(format_datetime($user['created_at'], 'd. m. Y')) ?></p>
    </div>
</div>
<?php $errors = $errors ?? []; ?>
<div class="grid-2">
<form class="card" method="post" action="<?= e(url('/app/profil')) ?>">
    <?= csrf_field() ?>
    <h3>Osobní údaje</h3>
    <div class="field"><label>Username</label><input name="username" value="<?= e($user['username']) ?>"><span class="field-error"><?= e($errors['username'][0] ?? '') ?></span></div>
    <div class="row-2">
        <div class="field"><label>Jméno</label><input name="first_name" value="<?= e($user['first_name']) ?>"></div>
        <div class="field"><label>Příjmení</label><input name="last_name" value="<?= e($user['last_name']) ?>"></div>
    </div>
    <div class="field"><label>Telefon</label><input name="phone" value="<?= e($user['phone'] ?? '') ?>"></div>
    <button class="btn btn-primary">Uložit</button>
</form>
<form class="card" method="post" action="<?= e(url('/app/profil/avatar')) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <h3>Profilový obrázek</h3>
    <input data-avatar-input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required>
    <button class="btn btn-primary" style="margin-top:12px">Nahrát</button>
</form>
<form class="card" method="post" action="<?= e(url('/app/profil/avatar/smazat')) ?>"><?= csrf_field() ?><button class="btn btn-danger">Odstranit obrázek</button></form>
</div>
<form class="card" method="post" action="<?= e(url('/app/profil/email')) ?>" style="margin-top:16px">
    <?= csrf_field() ?>
    <h3>Změna e-mailu</h3>
    <div class="field"><label>Nový e-mail</label><input type="email" name="email" required></div>
    <button class="btn btn-secondary">Odeslat ověření</button>
</form>
<form class="card" method="post" action="<?= e(url('/app/profil/heslo')) ?>" style="margin-top:16px">
    <?= csrf_field() ?>
    <h3>Změna hesla</h3>
    <div class="field"><label>Současné heslo</label><input type="password" name="current_password" required></div>
    <div class="field"><label>Nové heslo</label><input type="password" name="password" required minlength="12"></div>
    <div class="field"><label>Potvrzení</label><input type="password" name="password_confirmation" required minlength="12"></div>
    <button class="btn btn-primary">Změnit heslo</button>
</form>
<div class="card" style="margin-top:16px">
    <h3>Aktivní zařízení</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>IP</th><th>Poslední aktivita</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($sessions as $session): ?>
                <tr>
                    <td><?= e($session['ip_address']) ?><?= (int) $session['id'] === (int) $currentSession ? ' (toto zařízení)' : '' ?></td>
                    <td><?= e(format_datetime($session['last_activity_at'])) ?></td>
                    <td>
                        <form method="post" action="<?= e(url('/app/profil/relace/' . $session['id'] . '/odhlasit')) ?>"><?= csrf_field() ?><button class="btn btn-danger">Odhlásit</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <form method="post" action="<?= e(url('/app/profil/odhlasit-vse')) ?>" style="margin-top:12px"><?= csrf_field() ?><button class="btn btn-secondary">Odhlásit všechna zařízení</button></form>
    <p style="margin-top:12px"><a class="btn btn-secondary" href="<?= e(url('/app/zabezpeceni/mfa')) ?>">Nastavit MFA</a></p>
</div>
<div class="card" style="margin-top:16px">
    <h3>Ochrana osobních údajů</h3>
    <a class="btn btn-secondary" href="<?= e(url('/app/profil/export')) ?>">Exportovat data</a>
    <form method="post" action="<?= e(url('/app/profil/vymaz')) ?>" style="display:inline"><?= csrf_field() ?><button class="btn btn-danger">Žádost o výmaz</button></form>
</div>
