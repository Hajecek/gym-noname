<?php
$error = flash('error');
$ok = flash('success');
?>
<?php if ($error): ?>
    <div class="toast toast-error" role="alert" data-toast>
        <p><?= e($error) ?></p>
        <button type="button" data-toast-close aria-label="Zavřít hlášku">✕</button>
    </div>
<?php endif; ?>
<?php if ($ok): ?>
    <div class="done-modal" data-done-modal role="dialog" aria-modal="true" aria-label="Hotovo">
        <div class="done-modal-panel">
            <p class="done-kicker">Hotovo</p>
            <h2><?= e($ok) ?></h2>
        </div>
    </div>
<?php endif; ?>
