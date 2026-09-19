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
})();
