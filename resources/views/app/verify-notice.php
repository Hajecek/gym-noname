<div class="card">
    <h1>Ověřte e-mail</h1>
    <p class="muted">Bez ověřené adresy nelze otevřít dveře ani dokončit rezervaci.</p>
    <form method="post"><?= csrf_field() ?><button class="btn btn-primary">Znovu odeslat ověření</button></form>
</div>
