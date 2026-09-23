<?php
$user = $user ?? current_user();
if (!isset($sidebarPlan) && !empty($user['id'])) {
    $sidebarPlan = (new \App\Services\MembershipService(app()->db()))->activeForUser((int) $user['id']);
}
$planName = $sidebarPlan['plan_name'] ?? 'Bez tarifu';
$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
if ($fullName === '') {
    $fullName = (string) ($user['username'] ?? 'Profil');
}
?>
<div class="side-foot">
    <?php if (is_admin_user($user ?? null)): ?>
    <div class="side-view-mode">
        <?php require __DIR__ . '/view-mode-switch.php'; ?>
    </div>
    <?php endif; ?>
    <a class="side-profile" href="<?= e(url('/user/profil')) ?>" title="<?= e($fullName . ' · ' . $planName) ?>">
        <img class="side-avatar" src="<?= e(avatar_url($user)) ?>" alt="">
        <span class="side-profile-copy">
            <strong><?= e($fullName) ?></strong>
            <span class="side-plan"><?= e($planName) ?></span>
        </span>
    </a>
    <form method="post" action="<?= e(url('/odhlaseni')) ?>">
        <?= csrf_field() ?>
        <button class="btn btn-danger button-small side-logout" type="submit" title="Odhlásit">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M10 7V5a2 2 0 0 1 2-2h7v18h-7a2 2 0 0 1-2-2v-2"/><path d="M4 12h11M8 8l-4 4 4 4"/></svg>
            <span class="logout-label">Odhlásit</span>
        </button>
    </form>
    <button class="side-collapse" type="button" aria-expanded="true">
        <span data-collapse-label>Sbalit menu</span>
    </button>
</div>
