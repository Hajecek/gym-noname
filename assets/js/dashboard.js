(() => {
  const pulse = document.querySelector("[data-studio-pulse]");
  const modal = document.querySelector("[data-studio-modal]");
  if (!pulse || !modal) return;

  const label = pulse.querySelector("[data-studio-label]");
  const dot = pulse.querySelector("[data-studio-dot]");
  const panel = modal.querySelector("[data-studio-panel]");
  const title = modal.querySelector("[data-studio-title]");
  const bannerDot = modal.querySelector("[data-studio-banner-dot]");
  const detail = modal.querySelector("[data-studio-detail]");
  const room = modal.querySelector("[data-studio-room]");
  const hours = modal.querySelector("[data-studio-hours]");
  const next = modal.querySelector("[data-studio-next]");
  const nextLabel = modal.querySelector("[data-studio-next-label]");
  const url = pulse.getAttribute("data-studio-url");

  const headlines = {
    green: "Fitko volné",
    orange: "Blíží se termín",
    red: "Fitko uzavřené",
  };

  const apply = (payload) => {
    if (!payload || typeof payload !== "object") return;
    const state = String(payload.state || "open");
    const color = String(payload.dot || "green");
    const text = String(payload.label || headlines[color] || "Volné");
    const headline = color === "orange" ? text : (headlines[color] || text);
    const openAt = String(payload.opens_at || "");
    const closeAt = String(payload.closes_at || "");
    pulse.dataset.state = state;
    if (label) label.textContent = text;
    if (dot) dot.className = "studio-pulse-dot is-" + color;
    if (panel) panel.className = "cancel-modal-panel studio-pulse-panel is-" + color;
    if (bannerDot) bannerDot.className = "studio-pulse-dot is-" + color;
    if (title) title.textContent = headline;
    if (detail) detail.textContent = String(payload.detail || "");
    if (room) room.textContent = String(payload.room || "Studio");
    if (hours) hours.textContent = openAt && closeAt ? openAt + "–" + closeAt : "—";
    if (next) next.textContent = String(payload.next_at || "—");
    if (nextLabel) {
      nextLabel.textContent = state === "soon" && String(payload.label || "").includes("Probíhá")
        ? "Končí"
        : "Další termín";
    }
  };

  const open = () => {
    modal.hidden = false;
    modal.querySelector("[data-studio-close].btn")?.focus();
  };
  const close = () => {
    modal.hidden = true;
    pulse.focus();
  };

  pulse.addEventListener("click", open);
  modal.querySelectorAll("[data-studio-close]").forEach((node) => {
    node.addEventListener("click", close);
  });
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !modal.hidden) close();
  });

  let checking = false;
  const refresh = async () => {
    if (!url || checking || document.visibilityState === "hidden") return;
    checking = true;
    try {
      const response = await fetch(url, {
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
        credentials: "same-origin",
        cache: "no-store",
      });
      if (!response.ok) return;
      const body = await response.json();
      apply(body && body.data ? body.data : body);
    } catch {
      // krátký výpadek sítě nevadí
    } finally {
      checking = false;
    }
  };

  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible") refresh();
  });
  window.addEventListener("focus", refresh);
  window.addEventListener("online", refresh);
  window.setInterval(refresh, 15000);
})();
