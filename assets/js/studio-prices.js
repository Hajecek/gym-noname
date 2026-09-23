(() => {
  const form = document.getElementById("price-form");
  if (!form) return;

  const oneInput = form.querySelector('[name="default_hourly_price"]');
  const twoInput = form.querySelector('[name="default_hourly_price_two"]');
  const dayInputs = [...form.querySelectorAll("[data-day-price]")];

  const extra = () => {
    const one = Number(oneInput?.value || 150);
    const two = Number(twoInput?.value || 200);
    return Math.max(0, two - one);
  };

  const syncDay = (input) => {
    const row = input.closest(".price-table-row");
    const out = row?.querySelector("[data-day-two]");
    if (!out) return;
    const base = Number(oneInput?.value || 150);
    const raw = String(input.value || "").trim();
    const one = raw === "" ? base : Number(raw);
    const value = (Number.isFinite(one) ? one : base) + extra();
    out.textContent = Math.round(value) + "\u00a0Kč";
    input.dataset.extra = String(extra());
  };

  const syncAll = () => dayInputs.forEach(syncDay);

  oneInput?.addEventListener("input", syncAll);
  twoInput?.addEventListener("input", syncAll);
  dayInputs.forEach((input) => input.addEventListener("input", () => syncDay(input)));
})();
