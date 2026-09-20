<div class="card">
    <p class="eyebrow">ZABEZPEČENÍ</p>
    <h1>Záložní kódy</h1>
    <p class="muted">Ulož si je na bezpečné místo. Každý kód lze použít jen jednou.</p>
    <ul><?php foreach ($codes as $code): ?><li><code><?= e($code) ?></code></li><?php endforeach; ?></ul>
    <a class="btn btn-primary" href="<?= e(url('/user/profil')) ?>">Zpět do profilu</a>
</div>
