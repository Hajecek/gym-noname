<?php
$pid = $customer['public_id'] ?? '';
$isBlocked = ($customer['status'] ?? '') === 'blocked';
$active = $activeMembership ?? null;
$isLifetime = $active && ($active['plan_type'] ?? '') === 'lifetime';
$fullName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));

$entriesLabel = static function (?int $entries): string {
    if ($entries === null) {
        return 'Neomezené vstupy';
    }
    if ($entries === 1) {
        return '1 vstup';
    }
    if ($entries < 5) {
        return $entries . ' vstupy';
    }
    return $entries . ' vstupů';
};

$lifetime = null;
$standard = [];
foreach ($plans as $plan) {
    if (($plan['type'] ?? '') === 'lifetime') {
        $lifetime = $plan;
    } else {
        $standard[] = $plan;
    }
}
?>
<div class="page-head">
    <div>
        <p class="eyebrow"><a class="muted" href="<?= e(url('/user/sprava/zakaznici')) ?>">← Zákazníci</a></p>
        <h1><?= e($fullName !== '' ? $fullName : '@' . $customer['username']) ?></h1>
        <p class="muted">@<?= e($customer['username']) ?></p>
    </div>
    <div class="page-head-actions cust-head-actions">
        <button
            type="button"
            class="cust-switch <?= $isBlocked ? 'is-on' : '' ?>"
            role="switch"
            aria-checked="<?= $isBlocked ? 'true' : 'false' ?>"
            data-cust-switch
            data-name="<?= e($fullName !== '' ? $fullName : $customer['username']) ?>"
            data-block="<?= e(url('/user/sprava/zakaznici/' . $pid . '/blokovat')) ?>"
            data-unblock="<?= e(url('/user/sprava/zakaznici/' . $pid . '/odblokovat')) ?>"
            title="Blokace účtu"
        >
            <span class="cust-switch-track" aria-hidden="true"><span class="cust-switch-knob"></span></span>
            <span class="cust-switch-text" data-cust-switch-label><?= $isBlocked ? 'Blokováno' : 'Aktivní' ?></span>
        </button>
        <button type="button" class="btn btn-danger" data-cust-open="delete" data-name="<?= e($fullName !== '' ? $fullName : $customer['username']) ?>">Smazat</button>
    </div>
</div>

<section class="card cust-profile">
    <div class="cust-profile-top">
        <img class="avatar avatar-lg cust-avatar" src="<?= e(avatar_url($customer)) ?>" alt="">
        <div class="cust-profile-info">
            <div class="cust-hero-badges">
                <span class="badge <?= e(status_badge_class((string) $customer['status'])) ?>"><?= e(status_label((string) $customer['status'])) ?></span>
                <span class="badge badge-muted"><?= e(role_label((string) $customer['role'])) ?></span>
                <?php if ($isLifetime): ?>
                <span class="badge badge-rare">Rare</span>
                <?php elseif ($active): ?>
                <span class="badge badge-ok"><?= e((string) ($active['plan_name'] ?? 'Členství')) ?></span>
                <?php endif; ?>
            </div>
            <div class="cust-facts">
                <div class="cust-fact">
                    <span>E-mail</span>
                    <strong><?= e($customer['email']) ?></strong>
                </div>
                <div class="cust-fact">
                    <span>Telefon</span>
                    <strong><?= e($customer['phone'] ?: '—') ?></strong>
                </div>
                <div class="cust-fact">
                    <span>Registrace</span>
                    <strong><?= e(!empty($customer['created_at']) ? format_datetime((string) $customer['created_at'], 'd. m. Y') : '—') ?></strong>
                </div>
                <?php if ($isBlocked && !empty($customer['blocked_reason'])): ?>
                <div class="cust-fact cust-fact-wide">
                    <span>Důvod blokace</span>
                    <strong><?= e((string) $customer['blocked_reason']) ?></strong>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($active): ?>
    <div class="cust-current <?= $isLifetime ? 'is-rare' : '' ?>">
        <div>
            <p class="eyebrow"><?= $isLifetime ? 'Rare · Aktivní' : 'Aktivní členství' ?></p>
            <strong><?= e((string) $active['plan_name']) ?></strong>
            <p class="cust-current-meta">
                <?php if ($isLifetime || empty($active['ends_at'])): ?>
                    Bez konce
                <?php else: ?>
                    do <?= e(format_datetime((string) $active['ends_at'], 'd. m. Y')) ?>
                <?php endif; ?>
                <?php if ($active['entries_remaining'] !== null): ?>
                    · <?= e($entriesLabel((int) $active['entries_remaining'])) ?>
                <?php else: ?>
                    · Neomezené vstupy
                <?php endif; ?>
            </p>
        </div>
        <button
            type="button"
            class="btn btn-danger btn-sm"
            data-cust-open="revoke-membership"
            data-name="<?= e((string) $active['plan_name']) ?>"
            data-action="<?= e(url('/user/sprava/zakaznici/' . $pid . '/clenstvi/odebrat')) ?>"
            data-membership-id="<?= e((string) $active['public_id']) ?>"
        >Odebrat</button>
    </div>
    <?php else: ?>
    <div class="cust-current is-empty">
        <strong>Bez aktivního členství</strong>
        <p class="muted">Přidej tarif níže.</p>
    </div>
    <?php endif; ?>
</section>

<section class="cust-grant">
    <div class="cust-grant-head">
        <div>
            <p class="eyebrow">Členství</p>
            <h2>Přiřadit tarif</h2>
            <p class="muted">Nové přiřazení nahradí současné aktivní členství.</p>
        </div>
    </div>

    <?php if ($lifetime): ?>
    <form method="post" action="<?= e(url('/user/sprava/zakaznici/' . $pid . '/clenstvi')) ?>" class="cust-rare">
        <?= csrf_field() ?>
        <input type="hidden" name="plan_id" value="<?= (int) $lifetime['id'] ?>">
        <div class="cust-rare-copy">
            <span class="badge badge-rare">Rare</span>
            <h3><?= e($lifetime['name']) ?></h3>
            <p>Neomezené vstupy bez konce. Jen ruční přiřazení.</p>
        </div>
        <button type="submit" class="btn btn-rare">Přidělit doživotní</button>
    </form>
    <?php endif; ?>

    <?php if ($standard): ?>
    <div class="cust-tariffs">
        <?php foreach ($standard as $plan):
            $days = (int) ($plan['duration_days'] ?? 0);
            $entries = $plan['entries'] !== null ? (int) $plan['entries'] : null;
            $price = (float) $plan['price'];
            ?>
        <form method="post" action="<?= e(url('/user/sprava/zakaznici/' . $pid . '/clenstvi')) ?>" class="card cust-tariff">
            <?= csrf_field() ?>
            <input type="hidden" name="plan_id" value="<?= (int) $plan['id'] ?>">
            <div class="cust-tariff-body">
                <h3><?= e($plan['name']) ?></h3>
                <p class="cust-tariff-price"><?= e(number_format($price, 0, ',', ' ')) ?> <small>Kč</small></p>
                <ul>
                    <li><?= e($entriesLabel($entries)) ?></li>
                    <?php if ($days > 0): ?>
                    <li><?= $days ?> dní platnost</li>
                    <?php else: ?>
                    <li>Bez časového limitu</li>
                    <?php endif; ?>
                </ul>
            </div>
            <button type="submit" class="btn btn-secondary">Přidělit</button>
        </form>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<div class="cust-panels">
    <section class="card">
        <div class="cust-history-head">
            <h3>Historie členství</h3>
            <?php if ($memberships): ?>
            <button
                type="button"
                class="btn btn-danger btn-sm"
                data-cust-open="reset-membership"
                data-name="<?= e($fullName !== '' ? $fullName : $customer['username']) ?>"
                data-action="<?= e(url('/user/sprava/zakaznici/' . $pid . '/clenstvi/reset')) ?>"
            >Resetovat historii</button>
            <?php endif; ?>
        </div>
        <?php if (!$memberships): ?>
        <p class="muted">Zatím bez členství.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Tarif</th><th>Stav</th><th>Od</th><th>Do</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($memberships as $row):
                    $canRevoke = in_array((string) $row['status'], ['active', 'pending'], true);
                    ?>
                <tr>
                    <td>
                        <?= e($row['plan_name']) ?>
                        <?php if (($row['plan_type'] ?? '') === 'lifetime'): ?>
                        <span class="badge badge-rare">Rare</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e(status_label((string) $row['status'])) ?></td>
                    <td><?= e(!empty($row['starts_at']) ? format_datetime((string) $row['starts_at'], 'd. m. Y') : '—') ?></td>
                    <td><?= empty($row['ends_at']) ? '∞' : e(format_datetime((string) $row['ends_at'], 'd. m. Y')) ?></td>
                    <td>
                        <?php if ($canRevoke): ?>
                        <button
                            type="button"
                            class="btn btn-danger btn-sm"
                            data-cust-open="revoke-membership"
                            data-name="<?= e((string) $row['plan_name']) ?>"
                            data-action="<?= e(url('/user/sprava/zakaznici/' . $pid . '/clenstvi/odebrat')) ?>"
                            data-membership-id="<?= e((string) $row['public_id']) ?>"
                        >Odebrat</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>

    <section class="card cust-account">
        <h3>Účet</h3>
        <div class="cust-toggles">
            <?php if (($user['role'] ?? '') === 'admin'):
                $isAdminRole = ($customer['role'] ?? '') === 'admin';
                ?>
            <div class="cust-toggle-row">
                <div class="cust-toggle-copy">
                    <strong>Role</strong>
                    <span>Administrátor má přístup do správy.</span>
                </div>
                <div
                    class="cust-seg <?= $isAdminRole ? 'is-on' : '' ?>"
                    data-cust-seg
                    data-current="<?= $isAdminRole ? 'admin' : 'user' ?>"
                >
                    <span class="cust-seg-thumb <?= $isAdminRole ? 'is-right' : 'is-left' ?>" data-cust-seg-thumb aria-hidden="true"></span>
                    <button
                        type="button"
                        class="cust-seg-btn <?= !$isAdminRole ? 'is-active' : '' ?>"
                        data-cust-seg-target="user"
                        data-cust-open="role-user"
                        data-name="<?= e($fullName !== '' ? $fullName : $customer['username']) ?>"
                        data-action="<?= e(url('/user/sprava/zakaznici/' . $pid . '/role')) ?>"
                    >Zákazník</button>
                    <button
                        type="button"
                        class="cust-seg-btn <?= $isAdminRole ? 'is-active' : '' ?>"
                        data-cust-seg-target="admin"
                        data-cust-open="role-admin"
                        data-name="<?= e($fullName !== '' ? $fullName : $customer['username']) ?>"
                        data-action="<?= e(url('/user/sprava/zakaznici/' . $pid . '/role')) ?>"
                    >Admin</button>
                </div>
            </div>
            <?php endif; ?>

            <?php
            $statusKey = $isBlocked ? 'blocked' : (($customer['status'] ?? '') === 'pending' ? 'pending' : 'active');
            ?>
            <div class="cust-toggle-row">
                <div class="cust-toggle-copy">
                    <strong>Stav účtu</strong>
                    <span><?= $isBlocked ? 'Teď je zablokovaný — přepínačem nahoře ho odblokuješ.' : 'Čekající ještě nemá plný přístup.' ?></span>
                </div>
                <div
                    class="cust-seg <?= $statusKey === 'active' ? 'is-on' : '' ?><?= $isBlocked ? ' is-disabled' : '' ?>"
                    data-cust-seg
                    data-current="<?= $statusKey === 'pending' ? 'pending' : 'active' ?>"
                >
                    <span class="cust-seg-thumb <?= $statusKey === 'pending' ? 'is-left' : 'is-right' ?>" data-cust-seg-thumb aria-hidden="true"></span>
                    <button
                        type="button"
                        class="cust-seg-btn <?= $statusKey === 'pending' ? 'is-active' : '' ?>"
                        data-cust-seg-target="pending"
                        data-cust-open="status-pending"
                        data-name="<?= e($fullName !== '' ? $fullName : $customer['username']) ?>"
                        data-action="<?= e(url('/user/sprava/zakaznici/' . $pid)) ?>"
                        <?= $isBlocked ? 'disabled' : '' ?>
                    >Čeká</button>
                    <button
                        type="button"
                        class="cust-seg-btn <?= $statusKey !== 'pending' ? 'is-active' : '' ?>"
                        data-cust-seg-target="active"
                        data-cust-open="status-active"
                        data-name="<?= e($fullName !== '' ? $fullName : $customer['username']) ?>"
                        data-action="<?= e(url('/user/sprava/zakaznici/' . $pid)) ?>"
                        <?= $isBlocked ? 'disabled' : '' ?>
                    >Aktivní</button>
                </div>
            </div>
        </div>
    </section>
</div>

<?php if ($reservations): ?>
<section class="card cust-reservations">
    <h3>Rezervace</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Začátek</th><th>Stav</th><th>Osob</th><th>Cena</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($reservations, 0, 12) as $res): ?>
            <tr>
                <td><?= e(format_datetime((string) $res['starts_at'])) ?></td>
                <td><?= e((string) $res['status']) ?></td>
                <td><?= (int) ($res['persons'] ?? 1) ?></td>
                <td><?= e(number_format((float) ($res['price'] ?? 0), 0, ',', ' ')) ?> Kč</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<form method="post" action="<?= e(url('/user/sprava/zakaznici/' . $pid . '/role')) ?>" hidden data-cust-form="role-user">
    <?= csrf_field() ?>
    <input type="hidden" name="role" value="user">
</form>
<form method="post" action="<?= e(url('/user/sprava/zakaznici/' . $pid . '/role')) ?>" hidden data-cust-form="role-admin">
    <?= csrf_field() ?>
    <input type="hidden" name="role" value="admin">
</form>
<form method="post" action="<?= e(url('/user/sprava/zakaznici/' . $pid)) ?>" hidden data-cust-form="status-pending">
    <?= csrf_field() ?>
    <input type="hidden" name="status" value="pending">
</form>
<form method="post" action="<?= e(url('/user/sprava/zakaznici/' . $pid)) ?>" hidden data-cust-form="status-active">
    <?= csrf_field() ?>
    <input type="hidden" name="status" value="active">
</form>
<form method="post" action="<?= e(url('/user/sprava/zakaznici/' . $pid . '/blokovat')) ?>" hidden data-cust-form="block">
    <?= csrf_field() ?>
    <input type="hidden" name="blocked_reason" value="" data-cust-reason-field>
</form>
<form method="post" action="<?= e(url('/user/sprava/zakaznici/' . $pid . '/odblokovat')) ?>" hidden data-cust-form="unblock">
    <?= csrf_field() ?>
</form>
<form method="post" action="<?= e(url('/user/sprava/zakaznici/' . $pid . '/smazat')) ?>" hidden data-cust-form="delete">
    <?= csrf_field() ?>
    <input type="hidden" name="delete_reason" value="" data-cust-reason-field>
</form>
<form method="post" action="<?= e(url('/user/sprava/zakaznici/' . $pid . '/clenstvi/odebrat')) ?>" hidden data-cust-form="revoke-membership">
    <?= csrf_field() ?>
    <input type="hidden" name="membership_id" value="" data-cust-membership-id>
</form>
<form method="post" action="<?= e(url('/user/sprava/zakaznici/' . $pid . '/clenstvi/reset')) ?>" hidden data-cust-form="reset-membership">
    <?= csrf_field() ?>
</form>

<div class="cancel-modal" data-cust-modal hidden>
    <div class="cancel-modal-backdrop" data-cust-close></div>
    <section class="cancel-modal-panel" role="dialog" aria-modal="true" aria-labelledby="cust-modal-title">
        <p class="eyebrow" data-cust-eyebrow>Potvrzení</p>
        <h2 id="cust-modal-title" data-cust-title>Potvrdit akci</h2>
        <p class="muted" data-cust-body></p>
        <div class="field" data-cust-reason-wrap hidden>
            <label for="cust-reason">Zpráva pro uživatele</label>
            <textarea id="cust-reason" data-cust-reason rows="3" placeholder="Volitelné — jinak se použije výchozí text."></textarea>
        </div>
        <div class="cancel-actions">
            <button type="button" class="btn btn-secondary" data-cust-close>Zpět</button>
            <button type="button" class="btn btn-danger" data-cust-confirm>Potvrdit</button>
        </div>
    </section>
</div>
