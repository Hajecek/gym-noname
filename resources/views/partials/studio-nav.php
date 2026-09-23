<?php
$section = $section ?? 'studia';
$pages = [
    'studia' => '/user/studio',
    'ceny' => '/user/studio/ceny',
    'doba' => '/user/studio/doba',
];
?>
<?php if ($section !== 'studia' && count($rooms ?? []) > 1): ?>
    <div class="studio-rooms">
        <?php foreach ($rooms as $item): ?>
            <a class="studio-room<?= !empty($room['public_id']) && $item['public_id'] === $room['public_id'] ? ' is-on' : '' ?>" href="<?= e(url(($pages[$section] ?? '/user/studio') . '?room=' . rawurlencode((string) $item['public_id']))) ?>">
                <strong><?= e($item['name']) ?></strong>
                <span><?= e($item['location'] ?: 'Místo není vyplněné') ?></span>
                <?php if (!(int) $item['is_active']): ?><em>Skryté</em><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
