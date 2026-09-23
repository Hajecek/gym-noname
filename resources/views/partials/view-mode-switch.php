<?php if (is_admin_user($user ?? null)):
    $adminMode = admin_view_mode() === 'admin';
?>
<div class="view-mode view-mode-side" data-view-mode data-current="<?= $adminMode ? 'admin' : 'user' ?>">
    <div class="view-mode-track" role="group" aria-label="Režim zobrazení">
        <span class="view-mode-thumb <?= $adminMode ? 'is-admin' : 'is-user' ?>" data-view-thumb aria-hidden="true"></span>
        <form method="post" action="<?= e(url('/user/rezim')) ?>" class="view-mode-form" data-view-form="user">
            <?= csrf_field() ?>
            <input type="hidden" name="mode" value="user">
            <button
                type="submit"
                class="view-mode-btn <?= !$adminMode ? 'is-active' : '' ?>"
                data-view-target="user"
                aria-pressed="<?= !$adminMode ? 'true' : 'false' ?>"
                title="Uživatel"
            >
                <span class="view-mode-full">Uživatel</span>
                <span class="view-mode-short" aria-hidden="true">U</span>
            </button>
        </form>
        <form method="post" action="<?= e(url('/user/rezim')) ?>" class="view-mode-form" data-view-form="admin">
            <?= csrf_field() ?>
            <input type="hidden" name="mode" value="admin">
            <button
                type="submit"
                class="view-mode-btn <?= $adminMode ? 'is-active' : '' ?>"
                data-view-target="admin"
                aria-pressed="<?= $adminMode ? 'true' : 'false' ?>"
                title="Admin"
            >
                <span class="view-mode-full">Admin</span>
                <span class="view-mode-short" aria-hidden="true">A</span>
            </button>
        </form>
    </div>
</div>
<?php endif; ?>
