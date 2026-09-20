<div class="page-head"><div><p class="eyebrow">TVŮJ KLÍČ</p><h1>Vstup</h1><p class="muted">Otevření dveří vždy vyžaduje aktuální online autorizaci.</p></div></div>
<div class="card">
<?php if (!empty($state['allowed'])): ?>
    <p>Máš platnou rezervaci. Server ověří oprávnění až ve chvíli, kdy stiskneš tlačítko.</p>
    <form method="post" style="margin-top:16px"><?= csrf_field() ?><button class="btn btn-primary">Otevřít dveře</button></form>
<?php else: ?>
    <p>Momentálně nemáš oprávnění ke vstupu.</p>
    <p class="muted">Potřebuješ ověřený účet a potvrzenou rezervaci v aktuálním čase.</p>
<?php endif; ?>
<?php if (!empty($state['test_mode'])): ?>
    <p class="badge badge-warn" style="margin-top:16px">Testovací režim zámku. Fyzické dveře se neotevřou.</p>
<?php endif; ?>
</div>
