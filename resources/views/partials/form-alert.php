<?php
$error = flash('error');
$ok = flash('success');
?>
<?php if ($error): ?>
    <div class="form-alert" role="alert"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($ok): ?>
    <div class="form-alert is-ok" role="status"><?= e($ok) ?></div>
<?php endif; ?>
