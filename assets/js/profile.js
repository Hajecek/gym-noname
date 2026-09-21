(() => {
  const form = document.querySelector("[data-profile-form]");
  const bar = document.querySelector("[data-profile-save]");
  if (!form || !bar) return;

  const countEl = bar.querySelector("[data-profile-save-count]");
  const fields = () => [...form.querySelectorAll("[data-track]")];

  const labelFor = (count) => {
    if (count === 1) return "1 změna";
    if (count >= 2 && count <= 4) return count + " změny";
    return count + " změn";
  };

  const changed = (input) => input.value !== (input.getAttribute("data-original") ?? "");

  const sync = () => {
    let count = 0;
    fields().forEach((input) => {
      const dirty = changed(input);
      input.closest(".field")?.classList.toggle("is-dirty", dirty);
      if (dirty) count += 1;
    });
    form.querySelectorAll("[data-profile-section]").forEach((section) => {
      section.classList.toggle("is-dirty", section.querySelector(".field.is-dirty") !== null);
    });
    if (countEl) countEl.textContent = labelFor(count);
    const on = count > 0;
    bar.hidden = !on;
    bar.classList.toggle("is-on", on);
  };

  form.addEventListener("input", sync);
  bar.querySelector("[data-profile-reset]")?.addEventListener("click", () => {
    fields().forEach((input) => {
      input.value = input.getAttribute("data-original") ?? "";
    });
    sync();
  });
  sync();
})();
