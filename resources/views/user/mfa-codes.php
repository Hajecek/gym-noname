<div class="page-head">
    <div>
        <p class="eyebrow">ZABEZPEČENÍ</p>
        <h1>Záložní kódy</h1>
        <p class="muted">Ulož si je na bezpečné místo. Každý kód jde použít jen jednou, místo kódu z aplikace.</p>
    </div>
</div>

<section class="card profile-panel">
    <?php if ($codes): ?>
        <ul class="recovery-grid">
            <?php foreach ($codes as $code): ?>
                <li><code><?= e($code) ?></code></li>
            <?php endforeach; ?>
        </ul>
        <div class="profile-hero-actions">
            <button class="btn btn-secondary" type="button" data-copy="<?= e(implode("\n", $codes)) ?>">Kopírovat všechny</button>
            <a class="btn btn-primary" href="<?= e(url('/user/profil')) ?>">Zpět do profilu</a>
        </div>
    <?php else: ?>
        <p class="muted">Záložní kódy se zobrazují jen jednou, hned po zapnutí 2FA.</p>
        <a class="btn btn-secondary" href="<?= e(url('/user/profil')) ?>">Zpět do profilu</a>
    <?php endif; ?>
</section>
