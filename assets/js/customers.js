(() => {
  const modal = document.querySelector("[data-cust-modal]");
  if (!modal) return;

  const titleEl = modal.querySelector("[data-cust-title]");
  const bodyEl = modal.querySelector("[data-cust-body]");
  const eyebrowEl = modal.querySelector("[data-cust-eyebrow]");
  const confirmBtn = modal.querySelector("[data-cust-confirm]");
  const blockForm = document.querySelector('[data-cust-form="block"]');
  const unblockForm = document.querySelector('[data-cust-form="unblock"]');
  const deleteForm = document.querySelector('[data-cust-form="delete"]');
  let pending = null;

  const copy = {
    block: {
      eyebrow: "Blokace",
      title: "Zablokovat účet?",
      body: (name) =>
        `Účet ${name} se nebude moct přihlásit ani rezervovat. Kdykoli zase odblokuješ přepínačem.`,
      confirm: "Zablokovat",
      danger: true,
    },
    unblock: {
      eyebrow: "Odblokování",
      title: "Odblokovat účet?",
      body: (name) => `Účet ${name} znovu získá přístup k přihlášení a rezervacím.`,
      confirm: "Odblokovat",
      danger: false,
    },
    delete: {
      eyebrow: "Smazání",
      title: "Trvale smazat účet?",
      body: (name) =>
        `Účet ${name} se soft-smaže a už se neobjeví v seznamu. Tuhle akci nejde snadno vrátit.`,
      confirm: "Smazat",
      danger: true,
    },
  };

  const close = () => {
    if (pending?.revert) pending.revert();
    modal.hidden = true;
    pending = null;
    if (confirmBtn) confirmBtn.disabled = false;
  };

  const open = (kind, opts = {}) => {
    const cfg = copy[kind];
    if (!cfg) return;
    const name = opts.name || "tohoto zákazníka";
    if (titleEl) titleEl.textContent = cfg.title;
    if (bodyEl) bodyEl.textContent = cfg.body(name);
    if (eyebrowEl) eyebrowEl.textContent = cfg.eyebrow;
    if (confirmBtn) {
      confirmBtn.textContent = cfg.confirm;
      confirmBtn.className = cfg.danger ? "btn btn-danger" : "btn btn-primary";
    }
    pending = {
      kind,
      form: opts.form || null,
      revert: opts.revert || null,
    };
    modal.hidden = false;
    modal.querySelector("[data-cust-close]")?.focus();
  };

  const setSwitch = (el, on) => {
    el.classList.toggle("is-on", on);
    el.setAttribute("aria-checked", on ? "true" : "false");
    const label = el.querySelector("[data-cust-switch-label]");
    if (label) label.textContent = on ? "Blokováno" : "Aktivní";
  };

  document.querySelectorAll("[data-cust-switch]").forEach((sw) => {
    sw.addEventListener("click", (event) => {
      event.preventDefault();
      event.stopPropagation();
      const blocked = sw.getAttribute("aria-checked") === "true";
      const name = sw.getAttribute("data-name") || "tohoto zákazníka";
      const redirect = sw.getAttribute("data-redirect") || "";

      if (!blocked) {
        setSwitch(sw, true);
        if (blockForm) {
          const action = sw.getAttribute("data-block");
          if (action) blockForm.setAttribute("action", action);
          const redirectInput = blockForm.querySelector('[name="redirect"]');
          if (redirectInput) redirectInput.value = redirect;
        }
        open("block", {
          name,
          form: blockForm,
          revert: () => setSwitch(sw, false),
        });
        return;
      }

      setSwitch(sw, false);
      if (unblockForm) {
        const action = sw.getAttribute("data-unblock");
        if (action) unblockForm.setAttribute("action", action);
        const redirectInput = unblockForm.querySelector('[name="redirect"]');
        if (redirectInput) redirectInput.value = redirect;
      }
      open("unblock", {
        name,
        form: unblockForm,
        revert: () => setSwitch(sw, true),
      });
    });
  });

  document.querySelectorAll("[data-cust-open]").forEach((button) => {
    button.addEventListener("click", (event) => {
      event.preventDefault();
      event.stopPropagation();
      const kind = button.getAttribute("data-cust-open") || "";
      const action = button.getAttribute("data-action");
      const form = kind === "delete" ? deleteForm : null;
      if (!form) return;
      if (action) form.setAttribute("action", action);
      open(kind, {
        name: button.getAttribute("data-name") || "tohoto zákazníka",
        form,
      });
    });
  });

  modal.querySelectorAll("[data-cust-close]").forEach((el) => {
    el.addEventListener("click", close);
  });

  confirmBtn?.addEventListener("click", () => {
    if (!pending?.form) return;
    const form = pending.form;
    pending.revert = null;
    confirmBtn.disabled = true;
    form.submit();
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !modal.hidden) close();
  });

  document.querySelectorAll(".cust-row[data-href]").forEach((row) => {
    const go = () => {
      const href = row.getAttribute("data-href");
      if (href) window.location.href = href;
    };
    row.addEventListener("click", (event) => {
      if (event.target.closest("[data-stop]")) return;
      go();
    });
    row.addEventListener("keydown", (event) => {
      if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        go();
      }
    });
  });
})();
