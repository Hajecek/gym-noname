(() => {
  const modal = document.querySelector("[data-entry-modal]");
  const openBtn = document.querySelector("[data-entry-info]");
  if (!modal || !openBtn) return;

  const close = () => {
    modal.hidden = true;
    openBtn.focus();
  };

  openBtn.addEventListener("click", () => {
    modal.hidden = false;
    modal.querySelector("[data-entry-close].btn")?.focus();
  });

  modal.querySelectorAll("[data-entry-close]").forEach((node) => {
    node.addEventListener("click", close);
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !modal.hidden) close();
  });
})();
