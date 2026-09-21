(() => {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  document.querySelectorAll('form').forEach((form) => {
    if (token && !form.querySelector('input[name="_csrf"]')) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = '_csrf';
      input.value = token;
      form.appendChild(input);
    }
  });

  const picker = document.querySelector("[data-avatar-picker]");
  const file = picker?.querySelector("[data-avatar-input]") || document.querySelector("[data-avatar-input]");
  const preview = picker?.querySelector("[data-avatar-preview]") || document.querySelector("[data-avatar-preview]");
  const save = picker?.querySelector("[data-avatar-save]");
  const errorEl = picker?.querySelector("[data-avatar-error]");
  const showPickerError = (message) => {
    if (!errorEl) return;
    errorEl.hidden = !message;
    errorEl.textContent = message || "";
  };
  if (file && preview) {
    file.addEventListener("change", () => {
      const chosen = file.files?.[0];
      showPickerError("");
      if (!chosen) {
        if (save) save.hidden = true;
        return;
      }
      const type = (chosen.type || "").toLowerCase();
      const allowed = /^image\/(jpeg|jpg|pjpeg|png|webp)$/.test(type)
        || (type === "" && /\.(jpe?g|png|webp)$/i.test(chosen.name || ""));
      if (!allowed) {
        showPickerError("Povolené formáty jsou JPEG, PNG a WebP.");
        file.value = "";
        if (save) save.hidden = true;
        return;
      }
      if (chosen.size > 5 * 1024 * 1024) {
        showPickerError("Obrázek je větší než 5 MB.");
        file.value = "";
        if (save) save.hidden = true;
        return;
      }
      const applyUrl = (url) => {
        if (preview.tagName === "IMG") {
          preview.src = url;
        } else {
          preview.style.backgroundImage = "url('" + url + "')";
          preview.style.backgroundSize = "cover";
          preview.style.backgroundPosition = "center";
        }
        if (save) save.hidden = false;
      };
      applyUrl(URL.createObjectURL(chosen));
      const reader = new FileReader();
      reader.onload = () => applyUrl(String(reader.result || ""));
      reader.readAsDataURL(chosen);
    });
  }

  const toggle = document.querySelector('.menu-toggle');
  const menu = document.querySelector('.side-menu');
  const backdrop = document.querySelector('.side-backdrop');
  if (toggle && menu) {
    const setOpen = (open) => {
      menu.classList.toggle('open', open);
      toggle.setAttribute('aria-expanded', String(open));
      toggle.setAttribute('aria-label', open ? 'Zavřít menu' : 'Otevřít menu');
      document.body.classList.toggle('menu-open', open);
      if (backdrop) {
        backdrop.classList.toggle('show', open);
      }
    };
    const closeBtn = document.querySelector('.menu-close');
    const collapseBtn = document.querySelector('.side-collapse');
    const desktop = window.matchMedia('(min-width: 981px)');
    const storageKey = 'privofit-side-collapsed';
    const applyCollapsed = () => {
      const collapsed = desktop.matches && localStorage.getItem(storageKey) === '1';
      document.body.classList.toggle('side-collapsed', collapsed);
      if (collapseBtn) {
        collapseBtn.setAttribute('aria-expanded', String(!collapsed));
        const label = collapseBtn.querySelector('[data-collapse-label]');
        if (label) label.textContent = collapsed ? 'Otevřít menu' : 'Sbalit menu';
      }
    };
    applyCollapsed();
    desktop.addEventListener('change', applyCollapsed);
    collapseBtn?.addEventListener('click', () => {
      const next = localStorage.getItem(storageKey) !== '1';
      localStorage.setItem(storageKey, next ? '1' : '0');
      applyCollapsed();
    });
    toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
    closeBtn?.addEventListener('click', () => setOpen(false));
    backdrop?.addEventListener('click', () => setOpen(false));
    menu.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => setOpen(false)));
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') setOpen(false);
    });
  }

  document.querySelectorAll("[data-copy]").forEach((btn) => {
    btn.addEventListener("click", async () => {
      const value = btn.getAttribute("data-copy") || "";
      try {
        await navigator.clipboard.writeText(value);
        const previous = btn.textContent;
        btn.textContent = "Zkopírováno";
        window.setTimeout(() => {
          btn.textContent = previous;
        }, 1600);
      } catch {
        btn.textContent = "Zkopíruj ručně";
      }
    });
  });
})();
