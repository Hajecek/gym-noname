(() => {
  const root = document.querySelector("[data-booker]");
  if (!root) return;

  const MONTHS = ["leden", "únor", "březen", "duben", "květen", "červen", "červenec", "srpen", "září", "říjen", "listopad", "prosinec"];
  const MONTHS_GEN = ["ledna", "února", "března", "dubna", "května", "června", "července", "srpna", "září", "října", "listopadu", "prosince"];
  const DAYS = ["neděle", "pondělí", "úterý", "středa", "čtvrtek", "pátek", "sobota"];

  const payload = JSON.parse(root.getAttribute("data-payload") || "{}");
  const hoursEl = root.querySelector("[data-hour-list]");
  const emptyEl = root.querySelector("[data-hours-empty]");
  const closedEl = root.querySelector("[data-hours-closed]");
  const loadingEl = root.querySelector("[data-hours-loading]");
  const hintEl = root.querySelector("[data-hours-hint]");
  const dateLabelEl = root.querySelector("[data-date-label]");
  const form = root.querySelector("[data-book-form]");
  const startInput = form?.querySelector('[name="start"]');
  const durationInput = form?.querySelector('[name="duration"]');
  const guestsInput = form?.querySelector('[name="guests"]');
  const bar = document.querySelector("[data-bar]");
  const barTime = bar?.querySelector("[data-bar-time]");
  const barMeta = bar?.querySelector("[data-bar-meta]");
  const confirmBtn = bar?.querySelector("[data-confirm]");
  const guestCountEl = bar?.querySelector("[data-guest-count]");
  const dialog = document.querySelector("[data-calendar-dialog]");
  const calGrid = dialog?.querySelector("[data-cal-grid]");
  const calTitle = dialog?.querySelector("[data-cal-title]");

  const state = {
    date: payload.date,
    today: payload.today,
    availability: payload.availability || {},
    selected: [],
    hours: 1,
    guests: 1,
    calYear: Number((payload.date || "").slice(0, 4)) || new Date().getFullYear(),
    calMonth: Number((payload.date || "").slice(5, 7)) || (new Date().getMonth() + 1),
    calDays: [],
    calCache: new Map(),
    error: "",
  };

  const step = () => Number(state.availability.duration_step_minutes || 60);
  const minMinutes = () => Number(state.availability.min_minutes || 60);
  const maxMinutes = () => Number(state.availability.max_minutes || 180);
  const maxHours = () => Math.max(1, Math.floor(maxMinutes() / step()));
  const maxPersons = () => Number(state.availability.max_persons || 3);
  const hourly = () => Number(state.availability.hourly_price || 150);
  const buffer = () => Number(state.availability.buffer_minutes || 15);

  const pad = (n) => String(n).padStart(2, "0");
  const money = (n) => Math.round(Number(n) || 0).toLocaleString("cs-CZ") + "\u00a0Kč";
  const hoursWord = (count) => {
    if (count === 1) return "1 hodina";
    if (count >= 2 && count <= 4) return count + " hodiny";
    return count + " hodin";
  };
  const parseDate = (value) => {
    const [y, m, d] = String(value || "").split("-").map((part) => parseInt(part, 10));
    return new Date(y, (m || 1) - 1, d || 1);
  };
  const isoDate = (date) => date.getFullYear() + "-" + pad(date.getMonth() + 1) + "-" + pad(date.getDate());
  const addDays = (value, days) => {
    const date = parseDate(value);
    date.setDate(date.getDate() + days);
    return isoDate(date);
  };
  const addMinutesToTime = (time, minutes) => {
    const [h, m] = String(time).split(":").map((part) => parseInt(part, 10));
    const total = h * 60 + m + minutes;
    return pad(Math.floor((total % (24 * 60)) / 60)) + ":" + pad(total % 60);
  };
  const dateLabel = (value) => {
    const date = parseDate(value);
    return DAYS[date.getDay()] + " " + date.getDate() + ". " + MONTHS_GEN[date.getMonth()];
  };
  const maxBookable = () => addDays(state.today, 56);

  const membershipCovers = !!payload.membership_covers;
  const availableFor = (slot) => (slot.available_for || []).map((item) => Number(item));
  const bookableRows = () => (state.availability.slots || []).filter((slot) => slot.kind !== "buffer");
  const rangeEnd = () => {
    const first = state.selected[0];
    if (!first) return "";
    return addMinutesToTime(first.start, state.hours * step());
  };
  const inRange = (row) => {
    const first = state.selected[0];
    if (!first || row.kind === "buffer") return false;
    return row.start >= first.start && row.end <= rangeEnd();
  };

  const setSelection = (rows, hours) => {
    state.selected = rows;
    state.hours = hours || (rows.length || 1);
    updateForm();
    renderHours();
    renderBar();
  };

  const clickHour = (row) => {
    if (!row.available || row.kind === "buffer") return;
    const first = state.selected[0];
    if (!first) {
      setSelection([row], 1);
      return;
    }
    if (row.start === first.start && state.hours === 1) {
      setSelection([], 1);
      return;
    }
    if (row.start === first.start) {
      setSelection([first], 1);
      return;
    }
    const end = rangeEnd();
    if (row.start === end && state.hours < maxHours() && availableFor(first).includes((state.hours + 1) * step())) {
      setSelection([first], state.hours + 1);
      return;
    }
    if (inRange(row)) {
      const nextHours = Math.max(1, Math.round((timeToMinutes(row.end) - timeToMinutes(first.start)) / step()));
      setSelection([first], nextHours);
      return;
    }
    setSelection([row], 1);
  };

  const timeToMinutes = (time) => {
    const [h, m] = String(time).split(":").map((part) => parseInt(part, 10));
    return h * 60 + m;
  };

  const setHours = (count) => {
    const first = state.selected[0];
    if (!first) return;
    if (!availableFor(first).includes(count * step())) return;
    setSelection([first], count);
  };

  const updateForm = () => {
    const first = state.selected[0];
    const duration = (first ? state.hours : 1) * step();
    if (startInput) startInput.value = first ? state.date + " " + first.start : "";
    if (durationInput) durationInput.value = String(duration || minMinutes());
    if (guestsInput) guestsInput.value = String(state.guests);
    if (confirmBtn) confirmBtn.disabled = !first;
  };

  const hourState = (row) => {
    if (row.kind === "buffer") return "buffer";
    if (row.past || row.kind === "past") return "past";
    if (!row.available || row.kind === "busy") return "busy";
    if (inRange(row)) return "selected";
    const first = state.selected[0];
    if (
      first &&
      row.start === rangeEnd() &&
      state.hours < maxHours() &&
      availableFor(first).includes((state.hours + 1) * step())
    ) {
      return "add";
    }
    return "free";
  };

  const hourMeta = (kind) => {
    if (kind === "past") return "Už bylo";
    if (kind === "busy") return "Obsazeno";
    if (kind === "selected") return "Vybrané";
    if (kind === "add") return "Přidat hodinu";
    if (kind === "buffer") return "Úklid";
    return "Volné";
  };

  const renderHours = () => {
    const closed = !!state.availability.closed;
    const loading = !!(loadingEl && !loadingEl.hidden);
    const rows = closed || loading ? [] : (state.availability.slots || []);
    const free = rows.filter((row) => row.available).length;
    if (closedEl) closedEl.hidden = !closed;
    if (emptyEl) {
      emptyEl.hidden = closed || loading || rows.length > 0;
      emptyEl.textContent = state.error || "Pro tento den teď není volná hodina.";
    }
    if (hoursEl) {
      hoursEl.hidden = closed || loading || rows.length === 0;
      hoursEl.innerHTML = rows.map((row) => {
        const kind = hourState(row);
        if (kind === "buffer") {
          return '<div class="hour-buffer">15 min úklid · ' + row.start + "–" + row.end + "</div>";
        }
        const selected = kind === "selected";
        const disabled = kind === "busy" || kind === "past";
        return (
          '<button type="button" class="hour-row is-' + kind + '"' +
          ' data-hour-start="' + row.start + '"' +
          (disabled ? " disabled" : "") +
          ' aria-pressed="' + (selected ? "true" : "false") + '">' +
          '<span class="hour-time">' + row.start + "<small>" + row.end + "</small></span>" +
          '<span class="hour-meta">' + hourMeta(kind) + (kind === "free" || kind === "selected" || kind === "add" ? " · " + money(hourly()) : "") + "</span>" +
          "</button>"
        );
      }).join("");
    }
    if (dateLabelEl) dateLabelEl.textContent = dateLabel(state.date);
    const focusStart = state.selected[0]?.start;
    if (focusStart) {
      hoursEl?.querySelector('[data-hour-start="' + focusStart + '"]')?.scrollIntoView({ block: "center", behavior: "smooth" });
    }
    if (hintEl && !closed) {
      if (loading) {
        hintEl.textContent = "Načítám volné hodiny…";
      } else {
        hintEl.textContent = free
          ? "Vyber začátek. Délku měníš tlačítky 1 / 2 / 3 h — další hodina se sama neoznačí. Mezi tebou a dalším člověkem je " + buffer() + " min na úklid."
          : "Na tenhle den už volná hodina nezbývá.";
      }
    }
  };

  const renderBar = () => {
    const on = state.selected.length > 0;
    if (!bar) return;
    bar.hidden = !on;
    bar.classList.toggle("is-on", on);
    if (!on) return;
    const first = state.selected[0];
    const end = rangeEnd();
    const count = state.hours;
    const price = hourly() * count;
    if (barTime) barTime.textContent = first.start + "–" + end;
    if (barMeta) {
      barMeta.textContent = membershipCovers
        ? hoursWord(count) + " · z členství"
        : hoursWord(count) + " · " + money(price);
    }
    if (guestCountEl) guestCountEl.textContent = String(state.guests);
    if (confirmBtn) confirmBtn.textContent = membershipCovers ? "Rezervovat" : "Zaplatit " + money(price);
    bar.querySelectorAll("[data-hours]").forEach((btn) => {
      const value = parseInt(btn.getAttribute("data-hours") || "1", 10);
      const allowed = availableFor(first).includes(value * step());
      btn.disabled = !allowed;
      btn.classList.toggle("is-on", value === count);
    });
  };

  const setLoading = (loading) => {
    if (loadingEl) loadingEl.hidden = !loading;
    if (hoursEl) hoursEl.hidden = loading || !!state.availability.closed;
  };

  const fetchJson = async (url) => {
    const response = await fetch(url, {
      headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
      credentials: "same-origin",
    });
    const body = await response.json();
    if (!response.ok || !body.success) {
      throw new Error(body.message || "Nepodařilo se načíst data.");
    }
    return body.data;
  };

  const loadDay = async (date, pushUrl) => {
    state.date = date;
    state.selected = [];
    state.hours = 1;
    state.error = "";
    setLoading(true);
    renderHours();
    try {
      const data = await fetchJson(root.getAttribute("data-availability-url") + "?date=" + encodeURIComponent(date));
      state.availability = data;
      if (pushUrl !== false) {
        const page = root.getAttribute("data-page-url") || "";
        history.replaceState({}, "", page + "?date=" + encodeURIComponent(date));
      }
    } catch (error) {
      state.availability = { closed: false, slots: [] };
      state.error = error.message || "Hodiny se nepodařilo načíst.";
    } finally {
      setLoading(false);
      updateForm();
      renderHours();
      renderBar();
    }
  };

  const loadCalendar = async () => {
    const key = state.calYear + "-" + state.calMonth;
    if (state.calCache.has(key)) {
      state.calDays = state.calCache.get(key);
      renderCalendar();
      return;
    }
    if (calGrid) calGrid.innerHTML = '<p class="muted">Načítám měsíc…</p>';
    try {
      const data = await fetchJson(root.getAttribute("data-calendar-url") + "?year=" + state.calYear + "&month=" + state.calMonth);
      state.calDays = data.days || [];
      state.calCache.set(key, state.calDays);
    } catch {
      state.calDays = [];
    }
    renderCalendar();
  };

  const renderCalendar = () => {
    if (calTitle) calTitle.textContent = MONTHS[state.calMonth - 1] + " " + state.calYear;
    if (!calGrid) return;
    const first = new Date(state.calYear, state.calMonth - 1, 1);
    const startPad = (first.getDay() + 6) % 7;
    const lastDate = new Date(state.calYear, state.calMonth, 0).getDate();
    const byDate = new Map((state.calDays || []).map((day) => [day.date, day]));
    const maxDate = maxBookable();
    let html = "";
    for (let i = 0; i < startPad; i += 1) html += '<span class="cal-day is-pad"></span>';
    for (let day = 1; day <= lastDate; day += 1) {
      const value = state.calYear + "-" + pad(state.calMonth) + "-" + pad(day);
      const info = byDate.get(value) || { closed: false, free: 0 };
      const past = value < state.today;
      const future = value > maxDate;
      const selected = value === state.date;
      const today = value === state.today;
      const disabled = past || future;
      const classes = [
        "cal-day",
        selected ? "is-selected" : "",
        today ? "is-today" : "",
        info.closed ? "is-closed" : "",
        info.free > 0 && !disabled ? "is-free" : "",
        disabled ? "is-disabled" : "",
      ].filter(Boolean).join(" ");
      html += '<button type="button" class="' + classes + '" data-cal-day="' + value + '"' + (disabled ? " disabled" : "") + ">" + day + "</button>";
    }
    calGrid.innerHTML = html;
  };

  const openCalendar = () => {
    const current = parseDate(state.date);
    state.calYear = current.getFullYear();
    state.calMonth = current.getMonth() + 1;
    if (typeof dialog?.showModal === "function") dialog.showModal();
    else dialog?.setAttribute("open", "open");
    loadCalendar();
  };

  const closeCalendar = () => {
    if (typeof dialog?.close === "function") dialog.close();
    else dialog?.removeAttribute("open");
  };

  hoursEl?.addEventListener("click", (event) => {
    const btn = event.target.closest("[data-hour-start]");
    if (!btn || btn.disabled) return;
    const start = btn.getAttribute("data-hour-start");
    const row = bookableRows().find((item) => item.start === start);
    if (row) clickHour(row);
  });

  root.querySelector("[data-open-calendar]")?.addEventListener("click", openCalendar);
  dialog?.querySelector("[data-cal-close]")?.addEventListener("click", closeCalendar);
  dialog?.querySelector("[data-cal-prev]")?.addEventListener("click", () => {
    state.calMonth -= 1;
    if (state.calMonth < 1) {
      state.calMonth = 12;
      state.calYear -= 1;
    }
    loadCalendar();
  });
  dialog?.querySelector("[data-cal-next]")?.addEventListener("click", () => {
    state.calMonth += 1;
    if (state.calMonth > 12) {
      state.calMonth = 1;
      state.calYear += 1;
    }
    loadCalendar();
  });
  calGrid?.addEventListener("click", (event) => {
    const btn = event.target.closest("[data-cal-day]");
    if (!btn || btn.disabled) return;
    closeCalendar();
    loadDay(btn.getAttribute("data-cal-day"));
  });

  bar?.querySelector("[data-clear]")?.addEventListener("click", () => setSelection([], 1));
  bar?.querySelectorAll("[data-hours]")?.forEach((btn) => {
    btn.addEventListener("click", () => setHours(parseInt(btn.getAttribute("data-hours") || "1", 10)));
  });
  bar?.querySelector("[data-guest-minus]")?.addEventListener("click", () => {
    state.guests = Math.max(1, state.guests - 1);
    updateForm();
    renderBar();
  });
  bar?.querySelector("[data-guest-plus]")?.addEventListener("click", () => {
    state.guests = Math.min(maxPersons(), state.guests + 1);
    updateForm();
    renderBar();
  });
  form?.addEventListener("submit", async (event) => {
    if (!startInput?.value) {
      event.preventDefault();
      return;
    }
    event.preventDefault();
    if (confirmBtn) confirmBtn.disabled = true;
    try {
      const response = await fetch(form.action, {
        method: "POST",
        body: new FormData(form),
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
        credentials: "same-origin",
      });
      const body = await response.json();
      const checkout = body.data?.checkout_url;
      const redirect = body.data?.redirect;
      if (checkout) {
        window.location.assign(checkout);
        return;
      }
      if (body.success && redirect) {
        window.location.assign(redirect);
        return;
      }
      throw new Error(body.message || "Rezervaci se nepodařilo dokončit.");
    } catch (error) {
      if (barMeta) barMeta.textContent = error.message || "Rezervaci se nepodařilo dokončit.";
      if (confirmBtn) confirmBtn.disabled = false;
    }
  });

  updateForm();
  renderHours();
  renderBar();
})();
