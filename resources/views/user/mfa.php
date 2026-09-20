<div class="card">
    <p class="eyebrow">ZABEZPEČENÍ</p>
    <h1>Dvoufaktorové ověření</h1>
    <p class="muted">Naskenuj tajný klíč v autentizační aplikaci a zadej kód.</p>
    <p style="margin:14px 0"><code><?= e($setup['secret']) ?></code></p>
    <p class="muted" style="word-break:break-all"><?= e($setup['otpauth']) ?></p>
    <form method="post" style="margin-top:16px"><?= csrf_field() ?>
        <div class="field"><label>Kód</label><input name="code" required inputmode="numeric"></div>
        <button class="btn btn-primary">Aktivovat MFA</button>
    </form>
</div>
