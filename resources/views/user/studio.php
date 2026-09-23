<?php
$room = $room ?? null;
$defaultPrice = (int) (float) ($hourly_price ?? 150);
$defaultPriceTwo = (int) (float) ($hourly_price_two ?? 200);
$dayNames = [1 => 'pondělí', 2 => 'úterý', 3 => 'středa', 4 => 'čtvrtek', 5 => 'pátek', 6 => 'sobota', 7 => 'neděle'];
$todayName = $dayNames[(int) ($weekday ?? 1)] ?? 'dnes';
?>
<div class="page-head">
    <div>
        <p class="eyebrow">SPRÁVA</p>
        <h1>Studia</h1>
        <p class="muted">Dnes je <?= e($todayName) ?>. Klikni na studio a upravíš název, místo a jestli jde rezervovat.</p>
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
        <?php foreach ($rooms as $item):
            $active = (int) $item['is_active'] === 1;
            $closedToday = (int) ($item['today_closed'] ?? 1) === 1 || empty($item['today_open']);
            $price = ($item['today_price'] ?? '') !== '' && $item['today_price'] !== null
                ? (int) (float) $item['today_price']
                : $defaultPrice;
            $priceTwo = $price + max(0, $defaultPriceTwo - $defaultPrice);
        ?>
            <button
                class="studio-tile<?= $active ? '' : ' is-hidden' ?>"
                type="button"
                data-edit-studio
                data-id="<?= e((string) $item['public_id']) ?>"
                data-name="<?= e((string) $item['name']) ?>"
                data-location="<?= e((string) ($item['location'] ?? '')) ?>"
                data-latitude="<?= e((string) ($item['latitude'] ?? '')) ?>"
                data-longitude="<?= e((string) ($item['longitude'] ?? '')) ?>"
                data-active="<?= $active ? '1' : '0' ?>"
            >
                <span class="studio-tile-top">
                    <strong><?= e($item['name']) ?></strong>
                    <em><?= $active ? 'V rezervacích' : 'Skryté' ?></em>
                </span>
                <span class="studio-tile-place"><?= e($item['location'] ?: 'Místo není vyplněné') ?></span>
                <span class="studio-tile-meta">
                    <b><?= $closedToday ? 'Dnes zavřeno' : e(substr((string) $item['today_open'], 0, 5) . ' – ' . substr((string) $item['today_close'], 0, 5)) ?></b>
                    <b><?= $price ?> / <?= $priceTwo ?> Kč</b>
                </span>
            </button>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="cal-modal" data-edit-modal hidden>
    <button class="cal-modal-backdrop" type="button" data-close-edit aria-label="Zavřít"></button>
    <form class="cal-modal-panel studio-modal" method="post" action="<?= e(url('/user/studio/ulozit')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="room" value="">
        <div class="studio-modal-head">
            <h2>Upravit studio</h2>
            <button class="studio-modal-x" type="button" data-close-edit aria-label="Zavřít">✕</button>
        </div>
        <div class="field"><label>Název</label><input name="name" required maxlength="120"></div>
        <div class="field"><label>Kde to je</label><input name="location" required maxlength="190" placeholder="Ulice, město"></div>
        <div class="field"><label>Zeměpisná šířka</label><input name="latitude" inputmode="decimal" placeholder="50.0755"></div>
        <div class="field"><label>Zeměpisná délka</label><input name="longitude" inputmode="decimal" placeholder="14.4378"></div>
        <label class="check"><input type="checkbox" name="is_active" value="1"> Viditelné v rezervacích</label>
        <div class="studio-modal-actions">
            <button class="btn btn-secondary" type="button" data-close-edit>Zrušit</button>
            <button class="btn btn-primary">Uložit změny</button>
        </div>
    </form>
</div>

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
        <div class="field"><label>Zeměpisná šířka</label><input name="latitude" inputmode="decimal" placeholder="50.0755"></div>
        <div class="field"><label>Zeměpisná délka</label><input name="longitude" inputmode="decimal" placeholder="14.4378"></div>
        <div class="studio-modal-actions">
            <button class="btn btn-secondary" type="button" data-close-studio>Zrušit</button>
            <button class="btn btn-primary">Přidat studio</button>
        </div>
    </form>
</div>
<script nonce="<?= e($cspNonce ?? '') ?>">
(() => {
  const addModal = document.querySelector("[data-studio-modal]");
  const editModal = document.querySelector("[data-edit-modal]");
  const openModal = (modal, focus) => {
    modal.hidden = false;
    document.body.classList.add("cal-open");
    focus?.focus();
  };
  const closeModal = (modal) => {
    modal.hidden = true;
    if ((addModal?.hidden ?? true) && (editModal?.hidden ?? true)) {
      document.body.classList.remove("cal-open");
    }
  };
  if (addModal) {
    const name = addModal.querySelector("input[name=name]");
    document.querySelectorAll("[data-open-studio]").forEach((button) => {
      button.addEventListener("click", () => openModal(addModal, name));
    });
    addModal.querySelectorAll("[data-close-studio]").forEach((button) => {
      button.addEventListener("click", () => closeModal(addModal));
    });
  }
  if (editModal) {
    const room = editModal.querySelector("input[name=room]");
    const name = editModal.querySelector("input[name=name]");
    const location = editModal.querySelector("input[name=location]");
    const latitude = editModal.querySelector("input[name=latitude]");
    const longitude = editModal.querySelector("input[name=longitude]");
    const active = editModal.querySelector("input[name=is_active]");
    document.querySelectorAll("[data-edit-studio]").forEach((card) => {
      card.addEventListener("click", () => {
        room.value = card.dataset.id || "";
        name.value = card.dataset.name || "";
        location.value = card.dataset.location || "";
        if (latitude) latitude.value = card.dataset.latitude || "";
        if (longitude) longitude.value = card.dataset.longitude || "";
        active.checked = card.dataset.active === "1";
        openModal(editModal, name);
      });
    });
    editModal.querySelectorAll("[data-close-edit]").forEach((button) => {
      button.addEventListener("click", () => closeModal(editModal));
    });
  }
  document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") return;
    if (editModal && !editModal.hidden) closeModal(editModal);
    else if (addModal && !addModal.hidden) closeModal(addModal);
  });
})();
</script>
