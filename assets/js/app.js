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

  const file = document.querySelector('[data-avatar-input]');
  const preview = document.querySelector('[data-avatar-preview]');
  if (file && preview) {
    file.addEventListener('change', () => {
      const chosen = file.files?.[0];
      if (!chosen) return;
      const url = URL.createObjectURL(chosen);
      if (preview.tagName === 'IMG') {
        preview.src = url;
      } else {
        preview.style.backgroundImage = `url(${url})`;
      }
    });
  }

  document.querySelectorAll('[data-slot]').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (btn.classList.contains('busy')) return;
      document.querySelectorAll('[data-slot]').forEach((el) => el.classList.remove('selected'));
      btn.classList.add('selected');
      const start = document.querySelector('[name="start"]');
      if (start) start.value = btn.getAttribute('data-start') || '';
    });
  });

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
})();
