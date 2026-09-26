(() => {
  const Charts = window.PrivofitCharts;
  if (!Charts) return;

  const root = document.querySelector("[data-dash-chart]");
  if (!root) return;

  const payload = Charts.parsePayload(root);
  if (!payload) return;

  const line = root.querySelector("[data-dash-line]");
  const donut = root.querySelector("[data-dash-donut]");
  const tip = root.querySelector("[data-chart-tip]");
  const dayUrl = root.getAttribute("data-day-url") || "";

  if (line) {
    Charts.drawLineChart(line, payload.days || [], {
      tipEl: tip,
      onSelect: (day) => {
        if (!day?.date || !dayUrl) return;
        window.location.href = dayUrl + encodeURIComponent(day.date);
      },
    });
  }

  if (donut) {
    Charts.drawDonut(donut, payload.breakdown || {});
    window.addEventListener("resize", () => Charts.drawDonut(donut, payload.breakdown || {}));
  }
})();
