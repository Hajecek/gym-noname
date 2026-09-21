(() => {
  document.querySelectorAll("[data-plan-form]").forEach((form) => {
    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      const button = form.querySelector("button");
      if (button) button.disabled = true;
      try {
        const response = await fetch(form.action, {
          method: "POST",
          body: new FormData(form),
          headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
          credentials: "same-origin",
        });
        if (response.status === 401) {
          window.location.assign(document.body.dataset.signedOut || "/odhlaseno");
          return;
        }
        const body = await response.json();
        const checkout = body.data?.checkout_url;
        if (checkout) {
          window.location.assign(checkout);
          return;
        }
        window.location.assign(form.action);
      } catch (error) {
        if (button) button.disabled = false;
      }
    });
  });
})();
