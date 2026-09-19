<div class="card">
    <h1>Záložní kódy</h1>
    <p class="muted">Uložte si je na bezpečné místo. Každý kód lze použít jen jednou.</p>
    <ul><?php foreach ($codes as $code): ?><li><code><?= e($code) ?></code></li><?php endforeach; ?></ul>
    <a class="btn btn-primary" href="<?= e(url('/app/profil')) ?>">Zpět do profilu</a>
</div>
