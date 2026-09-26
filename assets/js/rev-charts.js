(() => {
  const formatMoney = (value) => {
    const n = Math.round(Number(value) || 0);
    return n.toLocaleString("cs-CZ") + " Kč";
  };

  const parsePayload = (node) => {
    if (!node) return null;
    const raw = node.getAttribute("data-chart");
    if (!raw) return null;
    try {
      return JSON.parse(raw);
    } catch {
      return null;
    }
  };

  const fitCanvas = (canvas) => {
    const ratio = window.devicePixelRatio || 1;
    const rect = canvas.getBoundingClientRect();
    const w = Math.max(1, Math.floor(rect.width));
    const h = Math.max(1, Math.floor(rect.height));
    if (canvas.width !== w * ratio || canvas.height !== h * ratio) {
      canvas.width = w * ratio;
      canvas.height = h * ratio;
    }
    const ctx = canvas.getContext("2d");
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    return { ctx, w, h };
  };

  const drawLineChart = (canvas, days, opts = {}) => {
    if (!canvas || !Array.isArray(days) || days.length === 0) return null;
    const tip = opts.tipEl || null;
    const onSelect = typeof opts.onSelect === "function" ? opts.onSelect : null;
    const accent = opts.accent || "#c6f21a";
    const soft = opts.soft || "rgba(198, 242, 26, 0.16)";

    let hover = -1;
    let bars = [];

    const paint = () => {
      const { ctx, w, h } = fitCanvas(canvas);
      ctx.clearRect(0, 0, w, h);

      const pad = { t: 18, r: 12, b: 28, l: 8 };
      const plotW = w - pad.l - pad.r;
      const plotH = h - pad.t - pad.b;
      const max = Math.max(...days.map((d) => Number(d.amount) || 0), 1);
      const step = plotW / Math.max(days.length, 1);
      bars = [];

      ctx.strokeStyle = "rgba(232, 240, 228, 0.08)";
      ctx.lineWidth = 1;
      for (let i = 0; i < 4; i++) {
        const y = pad.t + (plotH * i) / 3;
        ctx.beginPath();
        ctx.moveTo(pad.l, y);
        ctx.lineTo(w - pad.r, y);
        ctx.stroke();
      }

      const points = days.map((day, i) => {
        const amount = Number(day.amount) || 0;
        const x = pad.l + step * i + step / 2;
        const y = pad.t + plotH - (amount / max) * plotH;
        bars.push({ i, x, y, left: pad.l + step * i, right: pad.l + step * (i + 1), day });
        return { x, y, amount };
      });

      const grad = ctx.createLinearGradient(0, pad.t, 0, pad.t + plotH);
      grad.addColorStop(0, soft);
      grad.addColorStop(1, "rgba(198, 242, 26, 0)");
      ctx.beginPath();
      points.forEach((p, i) => {
        if (i === 0) ctx.moveTo(p.x, p.y);
        else ctx.lineTo(p.x, p.y);
      });
      ctx.lineTo(points[points.length - 1].x, pad.t + plotH);
      ctx.lineTo(points[0].x, pad.t + plotH);
      ctx.closePath();
      ctx.fillStyle = grad;
      ctx.fill();

      ctx.beginPath();
      points.forEach((p, i) => {
        if (i === 0) ctx.moveTo(p.x, p.y);
        else ctx.lineTo(p.x, p.y);
      });
      ctx.strokeStyle = accent;
      ctx.lineWidth = 2.25;
      ctx.lineJoin = "round";
      ctx.stroke();

      const labelEvery = days.length > 20 ? 5 : days.length > 10 ? 2 : 1;
      ctx.fillStyle = "rgba(232, 240, 228, 0.45)";
      ctx.font = "600 11px Figtree, sans-serif";
      ctx.textAlign = "center";
      days.forEach((day, i) => {
        if (i % labelEvery !== 0 && i !== days.length - 1) return;
        const x = pad.l + step * i + step / 2;
        ctx.fillText(String(day.label || ""), x, h - 8);
      });

      if (hover >= 0 && bars[hover]) {
        const b = bars[hover];
        ctx.strokeStyle = "rgba(232, 240, 228, 0.2)";
        ctx.beginPath();
        ctx.moveTo(b.x, pad.t);
        ctx.lineTo(b.x, pad.t + plotH);
        ctx.stroke();
        ctx.beginPath();
        ctx.arc(b.x, b.y, 5, 0, Math.PI * 2);
        ctx.fillStyle = accent;
        ctx.fill();
        ctx.strokeStyle = "#0b1210";
        ctx.lineWidth = 2;
        ctx.stroke();
      }
    };

    const findIndex = (clientX) => {
      const rect = canvas.getBoundingClientRect();
      const x = clientX - rect.left;
      for (let i = 0; i < bars.length; i++) {
        if (x >= bars[i].left && x < bars[i].right) return i;
      }
      return -1;
    };

    const showTip = (i, clientX, clientY) => {
      if (!tip || i < 0 || !bars[i]) {
        if (tip) tip.hidden = true;
        return;
      }
      const day = bars[i].day;
      tip.hidden = false;
      tip.innerHTML =
        "<strong>" +
        formatMoney(day.amount) +
        "</strong><span>" +
        (day.label || day.date || "") +
        " · " +
        (Number(day.count) || 0) +
        " plat." +
        "</span>";
      const parent = tip.offsetParent || document.body;
      const prect = parent.getBoundingClientRect();
      tip.style.left = Math.min(prect.width - 160, Math.max(8, clientX - prect.left - 60)) + "px";
      tip.style.top = Math.max(8, clientY - prect.top - 58) + "px";
    };

    const onMove = (event) => {
      const i = findIndex(event.clientX);
      if (i !== hover) {
        hover = i;
        paint();
      }
      showTip(i, event.clientX, event.clientY);
    };

    const onLeave = () => {
      hover = -1;
      paint();
      if (tip) tip.hidden = true;
    };

    const onClick = (event) => {
      const i = findIndex(event.clientX);
      if (i >= 0 && bars[i] && onSelect) onSelect(bars[i].day);
    };

    canvas.addEventListener("pointermove", onMove);
    canvas.addEventListener("pointerleave", onLeave);
    canvas.addEventListener("click", onClick);
    window.addEventListener("resize", paint);
    paint();

    return { redraw: paint, destroy: () => window.removeEventListener("resize", paint) };
  };

  const drawDonut = (canvas, breakdown) => {
    if (!canvas || !breakdown) return;
    const parts = [
      { key: "reservations", label: "Rezervace", color: "#c6f21a", value: Number(breakdown.reservations) || 0 },
      { key: "memberships", label: "Členství", color: "#6ec8ff", value: Number(breakdown.memberships) || 0 },
      { key: "other", label: "Ostatní", color: "#9aa49c", value: Number(breakdown.other) || 0 },
    ].filter((p) => p.value > 0);
    const total = parts.reduce((sum, p) => sum + p.value, 0) || 1;
    const { ctx, w, h } = fitCanvas(canvas);
    ctx.clearRect(0, 0, w, h);
    const cx = w / 2;
    const cy = h / 2;
    const r = Math.min(w, h) * 0.38;
    const inner = r * 0.58;
    let angle = -Math.PI / 2;

    if (parts.length === 0) {
      ctx.beginPath();
      ctx.arc(cx, cy, r, 0, Math.PI * 2);
      ctx.strokeStyle = "rgba(232, 240, 228, 0.12)";
      ctx.lineWidth = r - inner;
      ctx.stroke();
      return;
    }

    parts.forEach((part) => {
      const slice = (part.value / total) * Math.PI * 2;
      ctx.beginPath();
      ctx.arc(cx, cy, r, angle, angle + slice);
      ctx.arc(cx, cy, inner, angle + slice, angle, true);
      ctx.closePath();
      ctx.fillStyle = part.color;
      ctx.fill();
      angle += slice;
    });
  };

  window.PrivofitCharts = { drawLineChart, drawDonut, formatMoney, parsePayload };
})();
