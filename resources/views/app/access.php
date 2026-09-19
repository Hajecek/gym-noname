<div class="page-head"><div><h1>Vstup</h1><p class="muted">Otevření dveří vždy vyžaduje aktuální online autorizaci.</p></div></div>
<div class="card">
<?php if (!empty($state['allowed'])): ?>
    <p>Máte platnou rezervaci. Server ověří oprávnění až v okamžiku stisku tlačítka.</p>
    <form method="post"><?= csrf_field() ?><button class="btn btn-primary">Otevřít dveře</button></form>
<?php else: ?>
    <p>Momentálně nemáte oprávnění ke vstupu.</p>
    <p class="muted">Potřebujete ověřený účet a potvrzenou rezervaci v aktuálním čase.</p>
<?php endif; ?>
<?php if (!empty($state['test_mode'])): ?>
    <p class="badge badge-warn">Testovací režim zámku. Fyzické dveře se neotevřou.</p>
<?php endif; ?>
</div>
