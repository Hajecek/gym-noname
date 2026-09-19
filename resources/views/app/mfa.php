<div class="card">
    <h1>Dvoufaktorové ověření</h1>
    <p class="muted">Naskenujte tajný klíč v autentizační aplikaci a zadejte kód.</p>
    <p><code><?= e($setup['secret']) ?></code></p>
    <p class="muted" style="word-break:break-all"><?= e($setup['otpauth']) ?></p>
    <form method="post"><?= csrf_field() ?>
        <div class="field"><label>Kód</label><input name="code" required inputmode="numeric"></div>
        <button class="btn btn-primary">Aktivovat MFA</button>
    </form>
</div>
