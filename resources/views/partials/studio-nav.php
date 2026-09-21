<?php
$section = $section ?? 'studia';
$roomQuery = !empty($room['public_id']) ? '?room=' . rawurlencode((string) $room['public_id']) : '';
$tabs = [
    'studia' => ['Studia', '/user/studio'],
    'ceny' => ['Ceny', '/user/studio/ceny'],
    'doba' => ['Otevírací doba', '/user/studio/doba'],
];
?>
<nav class="studio-tabs" aria-label="Správa studia">
    <?php foreach ($tabs as $key => [$label, $href]): ?>
        <a class="<?= $section === $key ? 'is-on' : '' ?>" href="<?= e(url($href . $roomQuery)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>
<?php if ($section !== 'studia' && count($rooms ?? []) > 1): ?>
    <div class="studio-rooms">
        <?php foreach ($rooms as $item): ?>
            <a class="studio-room<?= !empty($room['public_id']) && $item['public_id'] === $room['public_id'] ? ' is-on' : '' ?>" href="<?= e(url(($tabs[$section][1] ?? '/user/studio') . '?room=' . rawurlencode((string) $item['public_id']))) ?>">
                <strong><?= e($item['name']) ?></strong>
                <span><?= e($item['location'] ?: 'Místo není vyplněné') ?></span>
                <?php if (!(int) $item['is_active']): ?><em>Skryté</em><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
