<?php $room = $room ?? null; ?>
<div class="page-head">
    <div>
        <p class="eyebrow">SPRÁVA</p>
        <h1>Studia</h1>
        <p class="muted">Vyber studio a uprav název, místo a viditelnost v rezervacích.</p>
    </div>
    <button class="btn btn-primary" type="button" data-open-studio>Přidat studio</button>
</div>
<?php require dirname(__DIR__) . '/partials/studio-nav.php'; ?>

<?php if ($rooms === []): ?>
    <div class="card studio-empty">
        <p>Zatím tu není žádné studio.</p>
        <button class="btn btn-primary" type="button" data-open-studio>Přidat první studio</button>
    </div>
<?php else: ?>
    <div class="studio-grid">
        <?php foreach ($rooms as $item): ?>
            <a class="studio-tile<?= $room && $item['public_id'] === $room['public_id'] ? ' is-on' : '' ?>" href="<?= e(url('/user/studio?room=' . rawurlencode((string) $item['public_id']))) ?>">
                <strong><?= e($item['name']) ?></strong>
                <span><?= e($item['location'] ?: 'Místo není vyplněné') ?></span>
                <em><?= (int) $item['is_active'] ? 'V rezervacích' : 'Skryté' ?></em>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($room): ?>
    <form class="card studio-card" method="post" action="<?= e(url('/user/studio/ulozit')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="room" value="<?= e($room['public_id']) ?>">
        <h2>Upravit studio</h2>
        <div class="row-2">
            <div class="field"><label>Název</label><input name="name" required maxlength="120" value="<?= e($room['name']) ?>"></div>
            <div class="field"><label>Kde to je</label><input name="location" required maxlength="190" value="<?= e((string) ($room['location'] ?? '')) ?>" placeholder="Ulice, město"></div>
        </div>
        <label class="check"><input type="checkbox" name="is_active" value="1" <?= (int) $room['is_active'] ? 'checked' : '' ?>> Viditelné v rezervacích</label>
        <button class="btn btn-secondary">Uložit změny</button>
    </form>
<?php endif; ?>

<div class="cal-modal" data-studio-modal hidden>
    <button class="cal-modal-backdrop" type="button" data-close-studio aria-label="Zavřít"></button>
    <form class="cal-modal-panel studio-modal" method="post" action="<?= e(url('/user/studio')) ?>">
        <?= csrf_field() ?>
        <div class="studio-modal-head">
            <h2>Nové studio</h2>
            <button class="studio-modal-x" type="button" data-close-studio aria-label="Zavřít">✕</button>
        </div>
        <p class="muted">Po přidání nastavíš cenu a otevírací dobu. Výchozí je každý den 6:00–22:00.</p>
        <div class="field"><label>Jak se jmenuje</label><input name="name" required maxlength="120" placeholder="Studio Vinohrady"></div>
        <div class="field"><label>Kde je</label><input name="location" required maxlength="190" placeholder="Vinohradská 12, Praha"></div>
        <div class="studio-modal-actions">
            <button class="btn btn-secondary" type="button" data-close-studio>Zrušit</button>
            <button class="btn btn-primary">Přidat studio</button>
        </div>
    </form>
</div>
<script nonce="<?= e($cspNonce ?? '') ?>">
(() => {
  const modal = document.querySelector("[data-studio-modal]");
  if (!modal) return;
  const name = modal.querySelector("input[name=name]");
  const open = () => {
    modal.hidden = false;
    document.body.classList.add("cal-open");
    name?.focus();
  };
  const close = () => {
    modal.hidden = true;
    document.body.classList.remove("cal-open");
  };
  document.querySelectorAll("[data-open-studio]").forEach((button) => button.addEventListener("click", open));
  modal.querySelectorAll("[data-close-studio]").forEach((button) => button.addEventListener("click", close));
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !modal.hidden) close();
  });
})();
</script>
