(() => {
  const modal = document.querySelector("[data-cancel-modal]");
  if (!modal) return;

  const outcomeEl = modal.querySelector("[data-cancel-outcome]");
  const confirmBtn = modal.querySelector("[data-cancel-confirm]");
  const refundSeconds = Number(modal.getAttribute("data-refund-seconds") || 120);
  const openedAt = performance.now();
  const serverNow = Number(modal.getAttribute("data-now") || 0);
  let pendingForm = null;

  const nowUnix = () => serverNow + Math.floor((performance.now() - openedAt) / 1000);

  const describe = (button) => {
    const price = Number(button.getAttribute("data-price") || 0);
    const membership = button.getAttribute("data-membership") === "1";
    const status = button.getAttribute("data-status") || "";
    const paidAt = Number(button.getAttribute("data-paid-at") || 0);
    if (membership && price <= 0) {
      return { late: false, text: "Tahle rezervace je ze členství. Peníze se nestrhly a vstup se po zrušení vrátí." };
    }
    if (status === "pending_payment" || paidAt <= 0 || price <= 0) {
      return { late: false, text: "Platba ještě neproběhla. Zrušením se nic nestrhne." };
    }
    const left = paidAt + refundSeconds - nowUnix();
    if (left >= 0) {
      return { late: false, text: "Od potvrzení ještě neuběhly 2 minuty. Když termín zrušíš teď, peníze se vrátí." };
    }
    return { late: true, text: "Od potvrzení už uběhly víc než 2 minuty. Když termín zrušíš, peníze se nevrátí." };
  };

  const close = () => {
    modal.hidden = true;
    pendingForm = null;
    if (confirmBtn) confirmBtn.disabled = false;
  };

  const open = (button) => {
    pendingForm = button.closest("form");
    const outcome = describe(button);
    if (outcomeEl) {
      outcomeEl.textContent = outcome.text;
      outcomeEl.classList.toggle("is-late", outcome.late);
    }
    modal.hidden = false;
    modal.querySelector(".cancel-actions [data-cancel-close]")?.focus();
  };

  document.querySelectorAll("[data-cancel-open]").forEach((button) => {
    button.addEventListener("click", (event) => {
      event.preventDefault();
      open(button);
    });
  });

  modal.querySelectorAll("[data-cancel-close]").forEach((button) => {
    button.addEventListener("click", close);
  });
  confirmBtn?.addEventListener("click", () => {
    if (!pendingForm) return;
    confirmBtn.disabled = true;
    pendingForm.submit();
  });
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !modal.hidden) close();
  });
})();
