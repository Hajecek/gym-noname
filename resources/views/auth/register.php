<?php
$errors = $errors ?? [];
$oldFirst = old('first_name');
$oldLast = old('last_name');
$oldUser = old('username');
$oldEmail = old('email');
?>
<main id="auth">
    <section class="auth-layout wrapper">
        <div class="auth-story">
            <a class="back" href="<?= e(url('/')) ?>">← Zpět na úvod</a>
            <div class="eyebrow">TVŮJ PROSTOR ZAČÍNÁ TADY</div>
            <h1>Malý krok.<br><span>Velká změna.</span></h1>
            <p id="auth-story-copy">Víc energie. Čistší hlava.<br>A chvíle, která patří jen tobě.</p>
            <div id="auth-3d" tabindex="0" role="img" aria-label="3D členská karta PRIVOFIT. Tažením ji můžeš otočit.">
                <p id="auth-3d-loading">Tvůj nový začátek.</p>
            </div>
            <p class="auth-card-caption">Tvůj vstup do nového tempa.</p>
        </div>
        <div class="auth-card">
            <div class="auth-switch" hidden>
                <a href="<?= e(url('/prihlaseni')) ?>" id="login-tab">Přihlášení</a>
                <a href="<?= e(url('/registrace')) ?>" id="register-tab">Registrace</a>
            </div>
            <div id="registration-progress" hidden>
                <div class="step-counter">
                    <span id="step-count">KROK 01 / 04</span>
                    <span id="step-name">O tobě</span>
                </div>
                <div class="step-bars" aria-hidden="true"><i class="active"></i><i></i><i></i><i></i></div>
            </div>
            <h2 id="auth-title" tabindex="-1">Začni u sebe.</h2>
            <p id="auth-subtitle">Nejdřív se trochu poznáme.</p>
            <?php require dirname(__DIR__) . '/partials/social-auth.php'; ?>
            <form id="auth-form" method="post" action="<?= e(url('/registrace')) ?>" enctype="multipart/form-data" novalidate>
                <?= csrf_field() ?>
                <input type="file" id="register-avatar" name="avatar" accept="image/jpeg,image/png,image/webp" hidden data-avatar-input>
                <fieldset id="identity-step" hidden>
                    <legend class="sr-only">O tobě</legend>
                    <div class="field-row">
                        <label>Jméno<input name="first_name" autocomplete="given-name" placeholder="Tvoje jméno" maxlength="80" required value="<?= e($oldFirst) ?>"></label>
                        <label>Příjmení<input name="last_name" autocomplete="family-name" placeholder="Tvoje příjmení" maxlength="80" required value="<?= e($oldLast) ?>"></label>
                    </div>
                    <label>Uživatelské jméno
                        <input name="username" autocomplete="username" placeholder="Jak ti máme říkat?" minlength="3" maxlength="30" pattern="[a-zA-Z0-9_.\-]+" required value="<?= e($oldUser) ?>">
                        <small>Písmena bez diakritiky, čísla, tečka nebo podtržítko.</small>
                        <?php if (!empty($errors['username'][0])): ?><small><?= e($errors['username'][0]) ?></small><?php endif; ?>
                    </label>
                </fieldset>
                <fieldset id="security-step">
                    <legend class="sr-only">Přihlašovací údaje</legend>
                    <label>E-mail
                        <input type="email" name="email" autocomplete="email" placeholder="ty@example.cz" required maxlength="190" value="<?= e($oldEmail) ?>">
                        <?php if (!empty($errors['email'][0])): ?><small><?= e($errors['email'][0]) ?></small><?php endif; ?>
                    </label>
                    <label>Heslo
                        <div class="password-field">
                            <input id="password" type="password" name="password" autocomplete="new-password" placeholder="Tvoje heslo" required minlength="12">
                            <button type="button" id="show-password" aria-label="Zobrazit heslo">Zobrazit</button>
                        </div>
                        <small id="password-hint">Alespoň 12 znaků.</small>
                        <?php if (!empty($errors['password'][0])): ?><small><?= e($errors['password'][0]) ?></small><?php endif; ?>
                    </label>
                    <div id="confirm-field" hidden>
                        <label>Heslo ještě jednou
                            <input type="password" id="confirm-password" name="password_confirmation" autocomplete="new-password" placeholder="Zopakuj heslo" minlength="12" required>
                        </label>
                    </div>
                </fieldset>
                <fieldset id="avatar-step" hidden>
                    <legend class="sr-only">Profilová fotka</legend>
                    <div class="avatar-picker" data-avatar-picker>
                        <label class="avatar-picker-face" for="register-avatar">
                            <span class="avatar-picker-preview" data-avatar-preview>
                                <span data-avatar-initials>P</span>
                                <img data-avatar-preview-img hidden alt="">
                            </span>
                            <span class="avatar-picker-badge" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 8h2.5l1.6-2.4h6L17.7 8H20a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-9a2 2 0 0 1 2-2z"/><circle cx="12.5" cy="14" r="3.4"/></svg>
                            </span>
                        </label>
                        <p class="avatar-picker-hint">Přidej fotku, nebo pokračuj bez ní. Doplnit ji můžeš i později v profilu.</p>
                        <button type="button" class="avatar-picker-clear" data-avatar-clear hidden>Odebrat fotku</button>
                        <p class="avatar-picker-error" data-avatar-error hidden></p>
                    </div>
                </fieldset>
                <fieldset id="review-step" hidden>
                    <legend class="sr-only">Kontrola údajů</legend>
                    <div class="review-profile">
                        <span id="review-avatar">
                            <span data-review-initials>P</span>
                            <img hidden alt="">
                        </span>
                        <div>
                            <strong id="review-name"></strong>
                            <small id="review-username"></small>
                        </div>
                    </div>
                    <div class="review-row"><span>E-mail</span><strong id="review-email"></strong></div>
                    <div class="review-row"><span>Heslo</span><strong>Vyplněno ✓</strong></div>
                    <p class="review-hint">Všechno sedí? Tvůj první krok je připravený.</p>
                    <label class="legal-check">
                        <input type="checkbox" name="terms" value="1" required>
                        <span>Souhlasím s <a href="<?= e(url('/dokument/obchodni-podminky')) ?>" target="_blank" rel="noopener">obchodními podmínkami</a>.</span>
                    </label>
                    <label class="legal-check">
                        <input type="checkbox" name="privacy" value="1" required>
                        <span>Potvrzuji seznámení se <a href="<?= e(url('/dokument/ochrana-udaju')) ?>" target="_blank" rel="noopener">zásadami ochrany osobních údajů</a>.</span>
                    </label>
                </fieldset>
                <p class="demo-note"><span>i</span> Heslo má alespoň 12 znaků. Účet se vytvoří hned a e-mail ověříš v další zprávě.</p>
                <div class="form-actions">
                    <button type="button" id="step-back" hidden>← Zpět</button>
                    <button class="button submit-button" type="submit" id="submit-button">Pokračovat <span>↗</span></button>
                </div>
                <p id="form-status" class="form-status" role="status" tabindex="-1" hidden></p>
            </form>
            <p class="auth-bottom" id="auth-bottom" hidden></p>
        </div>
    </section>
</main>
