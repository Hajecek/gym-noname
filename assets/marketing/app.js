const APP_BASE = document.documentElement.dataset.base || "/";
const page = document.body.dataset.page || "";
const path =
  "/" +
  location.pathname
    .slice(APP_BASE.length)
    .replace(/index\.html$/, "")
    .replace(/\/+$/, "");
const isRegister = page === "register" || path === "/registrace";
const isLogin = page === "login" || path === "/prihlaseni";
const isMfa = document.body.dataset.mfa === "1";
const year = document.getElementById("year");
if (year) year.textContent = new Date().getFullYear();
const menu = document.querySelector(".menu-toggle");
const nav = document.querySelector(".header nav");
if (menu && nav) {
  menu.addEventListener("click", () => {
    const open = menu.getAttribute("aria-expanded") !== "true";
    menu.setAttribute("aria-expanded", String(open));
    menu.setAttribute("aria-label", open ? "Zavřít menu" : "Otevřít menu");
    nav.classList.toggle("open", open);
  });
  nav.querySelectorAll("a").forEach((a) =>
    a.addEventListener("click", () => {
      nav.classList.remove("open");
      menu.setAttribute("aria-expanded", "false");
    }),
  );
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      nav.classList.remove("open");
      menu.setAttribute("aria-expanded", "false");
    }
  });
}
if (isRegister) {
  document.body.classList.add("standalone-registration");
}
if (isLogin) {
  document.body.classList.add("standalone-login");
  if (!isMfa) {
    const eyebrow = document.querySelector(".auth-story .eyebrow");
    const heading = document.querySelector(".auth-story h1");
    const copy = document.getElementById("auth-story-copy");
    const caption = document.querySelector(".auth-card-caption");
    const model = document.getElementById("auth-3d");
    if (eyebrow) eyebrow.textContent = "TVŮJ PROSTOR NA TEBE ČEKÁ";
    if (heading) heading.innerHTML = "Tvůj klíč.<br><span>Tvůj prostor.</span>";
    if (copy) copy.textContent = "Vítej zpátky. Odemkni si čas jen pro sebe.";
    if (caption) caption.textContent = "Tvůj přístup k vlastnímu tempu. Tažením otoč klíč.";
    if (model) {
      model.setAttribute("role", "button");
      model.setAttribute(
        "aria-label",
        "3D členský klíč PRIVOFIT. Tažením nebo šipkami ho otočíš. Kliknutím ho obrátíš.",
      );
    }
  }
}
if (isLogin || isRegister) {
  const homeEl = document.getElementById("home");
  const authEl = document.getElementById("auth");
  if (homeEl) homeEl.hidden = true;
  if (authEl) authEl.hidden = false;
  document.title = (isRegister ? "Registrace" : "Přihlášení") + " | PRIVOFIT";
  const activeTab = document.getElementById(isRegister ? "register-tab" : "login-tab");
  if (activeTab) {
    activeTab.classList.add("active");
    activeTab.setAttribute("aria-current", "page");
  }
  const form = document.getElementById("auth-form");
  if (!form) {
    // stránka bez auth formuláře
  } else {
  const identity = document.getElementById("identity-step");
  const security = document.getElementById("security-step");
  const review = document.getElementById("review-step");
  const fields = [identity, security, review];
  const password = document.getElementById("password");
  const confirm = document.getElementById("confirm-password");
  const submit = document.getElementById("submit-button");
  const back = document.getElementById("step-back");
  const status = document.getElementById("form-status");
  let step = 0;
  if (isLogin && !isMfa) {
    const identifier = form.querySelector('[name="email"], [name="identifier"]');
    if (identifier) {
      identifier.type = "text";
      identifier.name = "identifier";
      identifier.autocomplete = "username";
      identifier.placeholder = "E-mail nebo uživatelské jméno";
      identifier.spellcheck = false;
      identifier.setAttribute("autocapitalize", "none");
    }
    const identifierLabel = document.getElementById("login-identifier-label");
    if (identifierLabel) identifierLabel.textContent = "E-mail nebo uživatelské jméno";
    const dividerSpan = document.querySelector("#auth-divider span");
    if (dividerSpan) dividerSpan.textContent = "nebo pomocí účtu";
    password.removeAttribute("minlength");
  }
  document.querySelectorAll("[data-provider]").forEach((button) =>
    button.addEventListener("click", () => {
      const message = document.getElementById("social-status");
      if (!message) return;
      message.hidden = false;
      message.textContent =
        "Připojení k účtu " +
        button.dataset.provider +
        " zatím není aktivní. Toto je ukázka rozhraní; žádné údaje se neodesílají.";
    }),
  );
  document.getElementById("registration-progress").hidden = !isRegister;
  document.getElementById("confirm-field").hidden = !isRegister;
  document.getElementById("password-hint").hidden = !isRegister;
  password.autocomplete = isRegister ? "new-password" : "current-password";
  const authBottom = document.getElementById("auth-bottom");
  if (authBottom) {
    authBottom.innerHTML = isRegister
      ? 'Už máš svůj účet? <a href="' + APP_BASE + 'prihlaseni">Přihlas se</a>'
      : 'Ještě nemáš účet? <a href="' + APP_BASE + 'registrace">Začni tady</a>';
  }
  const input = (name) => form.querySelector('[name="' + name + '"]');
  const syncCard = () =>
    window.dispatchEvent(
      new CustomEvent("privofit-registration", {
        detail: {
          step,
          name: (input("first_name")?.value || "").trim(),
          complete: false,
        },
      }),
    );
  function showStep(next, focus = false) {
    step = next;
    const socialAuth = document.getElementById("social-auth");
    const authDivider = document.getElementById("auth-divider");
    const socialStatus = document.getElementById("social-status");
    if (socialAuth) socialAuth.hidden = isRegister && step !== 0;
    if (authDivider) authDivider.hidden = isRegister && step !== 0;
    if (socialStatus) socialStatus.hidden = true;
    fields.forEach((field, i) => {
      field.hidden = isRegister ? i !== step : i !== 1;
      field.disabled = field.hidden;
    });
    confirm.disabled = !isRegister;
    const titles = [
      "Začni u sebe.",
      "Tvůj účet. Tvůj klíč.",
      "Všechno připravené.",
    ];
    const subtitles = [
      "Nejdřív se trochu poznáme.",
      "Vytvoř si přístup do svého prostoru.",
      "Ještě rychlá kontrola a můžeš pokračovat.",
    ];
    document.getElementById("auth-title").textContent = isRegister
      ? titles[step]
      : isMfa
        ? "Ověření přihlášení"
        : "Pojďme na to.";
    document.getElementById("auth-subtitle").textContent = isRegister
      ? subtitles[step]
      : isMfa
        ? "Zadej kód z autentizační aplikace."
        : "Přihlas se do svého prostoru.";
    document.getElementById("step-count").textContent =
      "KROK 0" + (step + 1) + " / 03";
    document.getElementById("step-name").textContent = [
      "O tobě",
      "Přístup",
      "Kontrola",
    ][step];
    document
      .querySelectorAll(".step-bars i")
      .forEach((bar, i) => bar.classList.toggle("active", i <= step));
    back.hidden = !isRegister || step === 0;
    submit.disabled = false;
    submit.innerHTML =
      (isRegister
        ? step < 2
          ? "Pokračovat"
          : "Vytvořit účet"
        : "Přihlásit se") + " <span>↗</span>";
    if (status && !status.dataset.keep) status.hidden = true;
    if (isRegister && step === 2) {
      const name =
        input("first_name").value.trim() + " " + input("last_name").value.trim();
      document.getElementById("review-name").textContent = name;
      document.getElementById("review-username").textContent =
        "@" + input("username").value.trim();
      document.getElementById("review-email").textContent =
        input("email").value;
      document.getElementById("review-avatar").textContent =
        (input("first_name").value[0] || "") +
        (input("last_name").value[0] || "");
    }
    syncCard();
    if (focus)
      document.getElementById("auth-title").focus({ preventScroll: true });
  }
  function validateCurrent() {
    const active = isRegister ? fields[step] : security;
    if (isLogin) {
      const identifierInput = input("identifier");
      if (identifierInput) identifierInput.value = identifierInput.value.trim();
    }
    if (isRegister && step === 0)
      active
        .querySelectorAll("input")
        .forEach((el) => (el.value = el.value.trim()));
    confirm.setCustomValidity(
      isRegister && step === 1 && password.value !== confirm.value
        ? "Hesla se neshodují."
        : "",
    );
    return Array.from(active.querySelectorAll("input")).every(
      (el) => el.disabled || el.reportValidity(),
    );
  }
  back.addEventListener("click", () => showStep(Math.max(0, step - 1), true));
  password.addEventListener("input", () => confirm.setCustomValidity(""));
  confirm.addEventListener("input", () => confirm.setCustomValidity(""));
  input("first_name")?.addEventListener("input", syncCard);
  document.getElementById("show-password")?.addEventListener("click", (e) => {
    const reveal = password.type === "password";
    password.type = reveal ? "text" : "password";
    e.currentTarget.textContent = reveal ? "Skrýt" : "Zobrazit";
    e.currentTarget.setAttribute(
      "aria-label",
      reveal ? "Skrýt heslo" : "Zobrazit heslo",
    );
  });
  form.addEventListener("submit", (e) => {
    if (!validateCurrent()) {
      e.preventDefault();
      return;
    }
    if (isRegister && step < 2) {
      e.preventDefault();
      showStep(step + 1, true);
      return;
    }
    if (isRegister) {
      fields.forEach((field) => {
        field.disabled = false;
      });
    }
  });
  showStep(0);
  }
}

// Progressive motion: content stays readable if animations are unavailable.
const reduceMotion = matchMedia("(prefers-reduced-motion: reduce)");
let motionPaused = reduceMotion.matches;
const motionToggle = document.getElementById("motion-toggle");
function updateMotion() {
  document.body.classList.toggle("motion-paused", motionPaused);
  if (motionToggle) {
    motionToggle.textContent = motionPaused ? "▷" : "Ⅱ";
    motionToggle.setAttribute(
      "aria-label",
      motionPaused ? "Spustit animace" : "Pozastavit animace",
    );
    motionToggle.setAttribute("aria-pressed", String(motionPaused));
  }
  window.dispatchEvent(
    new CustomEvent("privofit-motion", { detail: { paused: motionPaused } }),
  );
}
if (motionToggle)
  motionToggle.addEventListener("click", () => {
    motionPaused = !motionPaused;
    updateMotion();
  });
reduceMotion.addEventListener("change", (e) => {
  motionPaused = e.matches;
  updateMotion();
});
updateMotion();
if ("IntersectionObserver" in window) {
  document.body.classList.add("motion-ready");
  const observer = new IntersectionObserver(
    (entries) =>
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("visible");
          observer.unobserve(entry.target);
        }
      }),
    { threshold: 0.08 },
  );
  document.querySelectorAll(".reveal").forEach((el) => observer.observe(el));
}
let scrollQueued = false;
function scrollPaint() {
  scrollQueued = false;
  const max = document.documentElement.scrollHeight - innerHeight;
  const progress = document.querySelector(".scroll-progress");
  if (progress) {
    progress.style.transform = `scaleX(${max > 0 ? scrollY / max : 0})`;
  }
  const photo = document.querySelector(".space-photo");
  if (photo && !motionPaused && !isLogin && !isRegister) {
    const r = photo.getBoundingClientRect();
    if (r.top < innerHeight && r.bottom > 0)
      photo.style.setProperty(
        "--photo-shift",
        `${Math.max(-35, Math.min(35, (innerHeight / 2 - r.top - r.height / 2) * 0.08))}px`,
      );
  }
}
addEventListener(
  "scroll",
  () => {
    if (!scrollQueued) {
      scrollQueued = true;
      requestAnimationFrame(scrollPaint);
    }
  },
  { passive: true },
);
scrollPaint();
if (matchMedia("(hover: hover) and (pointer: fine)").matches) {
  document.querySelectorAll(".tilt-card").forEach((card) => {
    card.addEventListener("pointermove", (e) => {
      if (motionPaused) return;
      const r = card.getBoundingClientRect();
      const x = (e.clientX - r.left) / r.width - 0.5,
        y = (e.clientY - r.top) / r.height - 0.5;
      card.style.transform = `perspective(850px) rotateX(${-y * 5}deg) rotateY(${x * 5}deg)`;
    });
    card.addEventListener("pointerleave", () => {
      card.style.transform = "";
    });
  });
  document.querySelectorAll(".magnetic").forEach((button) => {
    button.addEventListener("pointermove", (e) => {
      if (motionPaused) return;
      const r = button.getBoundingClientRect();
      button.style.transform = `translate(${(e.clientX - r.left - r.width / 2) * 0.08}px,${(e.clientY - r.top - r.height / 2) * 0.12}px)`;
    });
    button.addEventListener(
      "pointerleave",
      () => (button.style.transform = ""),
    );
  });
}
