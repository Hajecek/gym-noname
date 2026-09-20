import * as THREE from "./vendor/three.module.js";
const host = document.getElementById("phone-stage"),
  status = document.getElementById("phone-status"),
  enterButton = document.getElementById("phone-enter");
const reduced = matchMedia("(prefers-reduced-motion: reduce)");
let paused =
    reduced.matches || document.body.classList.contains("motion-paused"),
  visible = true,
  raf = 0,
  last = 0,
  time = 0,
  screenMode = "home",
  unlockTime = 0,
  unlockStart = 0,
  dragging = false,
  moved = false,
  pointer = null,
  startX = 0,
  startY = 0,
  lastX = 0,
  lastY = 0,
  rx = 0.015,
  ry = -0.2,
  selectedDay = 0,
  selectedSlot = 2,
  reserved = false;
const slots = ["10:00", "14:00", "17:00", "19:00"];
const renderer = new THREE.WebGLRenderer({
  alpha: true,
  antialias: true,
  powerPreference: "low-power",
});
renderer.setPixelRatio(Math.min(devicePixelRatio, 1.6));
renderer.setClearColor(0, 0);
renderer.outputColorSpace = THREE.SRGBColorSpace;
renderer.toneMapping = THREE.ACESFilmicToneMapping;
renderer.toneMappingExposure = 1.1;
host.appendChild(renderer.domElement);
const scene = new THREE.Scene(),
  camera = new THREE.PerspectiveCamera(34, 1, 0.1, 50);
camera.position.set(0, 0, 7.3);
scene.add(new THREE.HemisphereLight(0xffffff, 0x263725, 2.7));
for (const [color, power, pos] of [
  [0xffffff, 4, [-3, 5, 4]],
  [0xc6f21a, 3, [3, 1, -3]],
]) {
  const light = new THREE.DirectionalLight(color, power);
  light.position.set(...pos);
  scene.add(light);
}
const phone = new THREE.Group();
scene.add(phone);
phone.rotation.set(rx, ry, -0.055);
function roundShape(w, h, r) {
  const s = new THREE.Shape();
  s.moveTo(-w / 2 + r, -h / 2);
  s.lineTo(w / 2 - r, -h / 2);
  s.quadraticCurveTo(w / 2, -h / 2, w / 2, -h / 2 + r);
  s.lineTo(w / 2, h / 2 - r);
  s.quadraticCurveTo(w / 2, h / 2, w / 2 - r, h / 2);
  s.lineTo(-w / 2 + r, h / 2);
  s.quadraticCurveTo(-w / 2, h / 2, -w / 2, h / 2 - r);
  s.lineTo(-w / 2, -h / 2 + r);
  s.quadraticCurveTo(-w / 2, -h / 2, -w / 2 + r, -h / 2);
  return s;
}
const frameMat = new THREE.MeshStandardMaterial({
  color: 0x66735d,
  metalness: 0.88,
  roughness: 0.28,
});
const chassis = new THREE.Mesh(
  new THREE.ExtrudeGeometry(roundShape(2.08, 4.26, 0.28), {
    depth: 0.17,
    bevelEnabled: true,
    bevelSize: 0.038,
    bevelThickness: 0.038,
    bevelSegments: 5,
    steps: 1,
    curveSegments: 20,
  }),
  frameMat,
);
chassis.position.z = -0.09;
phone.add(chassis);
const glassMat = new THREE.MeshStandardMaterial({
  color: 0x0b100c,
  metalness: 0.2,
  roughness: 0.19,
});
const bezel = new THREE.Mesh(
  new THREE.ShapeGeometry(roundShape(2.025, 4.205, 0.255), 24),
  glassMat,
);
bezel.position.z = 0.123;
phone.add(bezel);
const canvas = document.createElement("canvas");
canvas.width = 600;
canvas.height = 1250;
const ctx = canvas.getContext("2d");
const texture = new THREE.CanvasTexture(canvas);
texture.colorSpace = THREE.SRGBColorSpace;
texture.anisotropy = Math.min(8, renderer.capabilities.getMaxAnisotropy());
const screen = new THREE.Mesh(
  new THREE.ShapeGeometry(roundShape(1.9, 4.08, 0.21), 24),
  new THREE.MeshBasicMaterial({ map: texture, toneMapped: false }),
);
screen.name = "phone-screen";
screen.position.z = 0.128;
// ShapeGeometry uses position-based UVs; map them into the display rectangle.
const positions = screen.geometry.attributes.position,
  uv = screen.geometry.attributes.uv;
for (let i = 0; i < uv.count; i++)
  uv.setXY(
    i,
    (positions.getX(i) + 0.95) / 1.9,
    (positions.getY(i) + 2.04) / 4.08,
  );
uv.needsUpdate = true;
phone.add(screen);
const island = new THREE.Mesh(
  new THREE.ShapeGeometry(roundShape(0.55, 0.14, 0.07), 18),
  new THREE.MeshBasicMaterial({ color: 0x040705 }),
);
island.position.set(0, 1.91, 0.135);
phone.add(island);
const lens = new THREE.Mesh(
  new THREE.CircleGeometry(0.022, 24),
  new THREE.MeshStandardMaterial({
    color: 0x193234,
    metalness: 0.7,
    roughness: 0.1,
  }),
);
lens.position.set(0.18, 1.91, 0.139);
phone.add(lens);
for (const [x, y, length] of [
  [-1.071, 0.96, 0.26],
  [-1.071, 0.49, 0.4],
  [1.071, 0.6, 0.57],
]) {
  const key = new THREE.Mesh(
    new THREE.BoxGeometry(0.033, length, 0.075),
    frameMat,
  );
  key.position.set(x, y, -0.01);
  phone.add(key);
}
const cameraBump = new THREE.Mesh(
  new THREE.ExtrudeGeometry(roundShape(0.76, 0.81, 0.17), {
    depth: 0.07,
    bevelEnabled: true,
    bevelSize: 0.02,
    bevelThickness: 0.02,
    bevelSegments: 3,
    curveSegments: 12,
  }),
  frameMat,
);
cameraBump.position.set(-0.45, 1.52, -0.145);
cameraBump.rotation.y = Math.PI;
phone.add(cameraBump);
function round(x, y, w, h, r, fill, stroke) {
  ctx.beginPath();
  ctx.roundRect(x, y, w, h, r);
  if (fill) {
    ctx.fillStyle = fill;
    ctx.fill();
  }
  if (stroke) {
    ctx.strokeStyle = stroke;
    ctx.lineWidth = 2;
    ctx.stroke();
  }
}
function text(
  value,
  x,
  y,
  size = 24,
  color = "#E8F0E4",
  weight = 500,
  font = "Figtree",
) {
  ctx.fillStyle = color;
  ctx.font = `${weight} ${size}px ${font},sans-serif`;
  ctx.fillText(value, x, y);
}
function lock(cx, cy, open = false, color = "#101714", scale = 1) {
  ctx.save();
  ctx.translate(cx, cy);
  ctx.scale(scale, scale);
  ctx.strokeStyle = color;
  ctx.lineWidth = 7;
  ctx.lineCap = "round";
  ctx.beginPath();
  if (open) {
    ctx.moveTo(-23, -7);
    ctx.lineTo(-23, -27);
    ctx.bezierCurveTo(-23, -61, 26, -61, 26, -27);
  } else {
    ctx.moveTo(-23, 0);
    ctx.lineTo(-23, -24);
    ctx.bezierCurveTo(-23, -57, 23, -57, 23, -24);
    ctx.lineTo(23, 0);
  }
  ctx.stroke();
  round(-36, -7, 72, 59, 14, null, color);
  ctx.beginPath();
  ctx.moveTo(0, 16);
  ctx.lineTo(0, 29);
  ctx.stroke();
  ctx.restore();
}
function footer() {
  round(215, 1216, 170, 6, 3, "#E8F0E4");
}
function drawScreen() {
  ctx.clearRect(0, 0, 600, 1250);
  round(0, 0, 600, 1250, 65, "#0b1210");
  text("9:41", 40, 57, 24, "#E8F0E4", 700);
  round(508, 39, 40, 17, 4, null, "#E8F0E4");
  round(511, 42, 29, 11, 2, "#C6F21A");
  text("●", 476, 55, 16, "#E8F0E4");
  if (screenMode === "home") {
    text("privofit", 40, 148, 42, "#E8F0E4", 800, "Syne");
    round(494, 108, 64, 64, 32, "#25301c");
    text("M", 513, 151, 29, "#C6F21A", 700);
    text("TVŮJ OSOBNÍ PROSTOR", 40, 221, 16, "#88977a", 600);
    text("Ahoj, Michale.", 40, 275, 37, "#E8F0E4", 600, "Syne");
    text(
      reserved
        ? `${selectedDay ? "Zítra" : "Dnes"} · ${slots[selectedSlot]} · ukázkový termín`
        : "Dnes je dobrý den začít.",
      40,
      318,
      24,
      "#95a58a",
    );
    round(35, 370, 530, 691, 38, "#C6F21A");
    text("JEDEN DOTYK. TVŮJ PROSTOR.", 66, 426, 16, "#425217", 700);
    lock(300, 597, false, "#101714", 1.4);
    text("Vstoupit", 67, 793, 60, "#101714", 700, "Syne");
    text("do fitka", 67, 857, 60, "#101714", 700, "Syne");
    text("Klepni a vyzkoušej otevření.", 67, 921, 24, "#3b4b13");
    round(450, 958, 66, 66, 33, "#101714");
    text("↗", 466, 1003, 37, "#C6F21A", 600);
    text("UKÁZKA VSTUPU", 40, 1114, 15, "#8e9f7e", 600);
    text("Vstup", 54, 1178, 23, "#C6F21A", 600);
    text("Rezervace", 237, 1178, 23, "#97a68b", 500);
    text("Profil", 461, 1178, 23, "#97a68b", 500);
  } else if (screenMode === "reserve") {
    text("‹", 38, 150, 51, "#C6F21A");
    text("Tvůj čas.", 95, 147, 38, "#E8F0E4", 600, "Syne");
    text("UKÁZKOVÁ REZERVACE", 40, 222, 16, "#8fa27d", 600);
    text("Vyber si chvíli pro sebe.", 40, 270, 28, "#E8F0E4", 500);
    for (let i = 0; i < 2; i++) {
      round(
        40 + i * 265,
        320,
        250,
        96,
        20,
        selectedDay === i ? "#C6F21A" : "#192318",
      );
      text(
        i ? "Zítra" : "Dnes",
        118 + i * 265,
        381,
        28,
        selectedDay === i ? "#101714" : "#E8F0E4",
        600,
      );
    }
    text("VYBER ČAS", 40, 478, 17, "#8fa27d", 600);
    slots.forEach((slot, i) => {
      const x = 40 + (i % 2) * 265,
        y = 516 + Math.floor(i / 2) * 113;
      round(x, y, 250, 93, 18, selectedSlot === i ? "#C6F21A" : "#192318");
      text(
        slot,
        x + 75,
        y + 59,
        30,
        selectedSlot === i ? "#101714" : "#E8F0E4",
        600,
      );
    });
    text("60 minut jen pro tebe.", 40, 809, 27, "#E8F0E4", 500);
    text("Termíny jsou pouze ilustrační.", 40, 857, 21, "#8fa27d");
    round(40, 933, 520, 121, 23, "#C6F21A");
    text("Vybrat tento čas  ↗", 100, 1010, 31, "#101714", 700);
    text("Tato ukázka nevytváří rezervaci.", 79, 1120, 21, "#8fa27d");
  } else if (screenMode === "booked") {
    text("TVŮJ ČAS, TVOJE TEMPO", 70, 249, 22, "#C6F21A", 600);
    round(210, 363, 180, 180, 90, "#C6F21A");
    text("✓", 250, 485, 95, "#101714", 600);
    text("Čas vybraný.", 74, 670, 48, "#E8F0E4", 600, "Syne");
    text(
      `${selectedDay ? "Zítra" : "Dnes"} v ${slots[selectedSlot]}`,
      163,
      743,
      35,
      "#C6F21A",
      600,
    );
    text("60 minut pro tvoje lepší já.", 114, 800, 25, "#8fa27d");
    round(40, 966, 520, 122, 23, "#C6F21A");
    text("Přejít na vstup  ↗", 122, 1045, 30, "#101714", 700);
    text("UKÁZKA · REZERVACE NEBYLA VYTVOŘENA", 45, 1160, 15, "#8fa27d", 600);
  } else if (screenMode === "profile") {
    text("‹", 38, 150, 51, "#C6F21A");
    text("Můj profil", 95, 147, 36, "#E8F0E4", 600, "Syne");
    round(230, 213, 140, 140, 70, "#C6F21A");
    text("MH", 252, 303, 50, "#101714", 700, "Syne");
    text("Michal Hájek", 151, 421, 38, "#E8F0E4", 600, "Syne");
    text("Ukázkový profil", 211, 468, 23, "#8f9d83");
    round(40, 527, 520, 118, 22, "#172016");
    text("MŮJ PROSTOR", 64, 568, 16, "#8f9d83", 600);
    text("PRIVOFIT", 64, 611, 32, "#E8F0E4", 700, "Syne");
    round(40, 674, 520, 134, 22, "#172016");
    text("ČLENSTVÍ", 64, 715, 16, "#8f9d83", 600);
    text(
      reserved
        ? `${selectedDay ? "Zítra" : "Dnes"} · ${slots[selectedSlot]} · ukázka`
        : "Připraveno na tvůj první krok",
      64,
      766,
      25,
      "#E8F0E4",
      500,
    );
    round(40, 930, 520, 126, 24, "#C6F21A");
    text("Přejít na vstup   ↗", 91, 1008, 32, "#101714", 700);
    text("Žádné skutečné údaje se neukládají.", 73, 1111, 20, "#8f9d83");
  } else if (screenMode === "opening") {
    text("TVŮJ PROSTOR SE OTEVÍRÁ", 90, 272, 20, "#C6F21A", 600);
    ctx.strokeStyle = "#26311b";
    ctx.lineWidth = 7;
    ctx.beginPath();
    ctx.arc(300, 575, 122, 0, Math.PI * 2);
    ctx.stroke();
    ctx.strokeStyle = "#C6F21A";
    ctx.beginPath();
    ctx.arc(
      300,
      575,
      122,
      -Math.PI / 2,
      -Math.PI / 2 + Math.PI * 2 * Math.min(1, unlockTime / 1.25),
    );
    ctx.stroke();
    lock(300, 575, unlockTime > 0.65, "#C6F21A", 1.35);
    text("Otevíráme…", 126, 820, 44, "#E8F0E4", 600, "Syne");
    text("Chvilka jen pro tebe.", 173, 873, 26, "#92a37f");
  } else {
    round(0, 0, 600, 1250, 65, "#C6F21A");
    text("9:41", 40, 57, 24, "#101714", 700);
    text("PRIVOFIT", 40, 159, 32, "#101714", 800, "Syne");
    text("TVŮJ ČAS ZAČÍNÁ", 40, 218, 17, "#425517", 600);
    lock(300, 470, true, "#101714", 1.8);
    text("Vítej", 47, 710, 80, "#101714", 700, "Syne");
    text("ve svém.", 47, 795, 80, "#101714", 700, "Syne");
    text("Vypni svět. Zapni sebe.", 49, 866, 28, "#35480e");
    round(40, 1007, 520, 109, 23, "#101714");
    text("Zkusit znovu  ↗", 154, 1077, 29, "#E8F0E4", 700);
    text("UKÁZKA · DVEŘE SE REÁLNĚ NEOTEVŘELY", 52, 1165, 15, "#41520f", 600);
  }
  footer();
  texture.needsUpdate = true;
}
function render() {
  renderer.render(scene, camera);
}
function goHome() {
  document.getElementById("phone-booking-controls").hidden = true;
  screenMode = "home";
  unlockTime = 0;
  enterButton.innerHTML = "Vyzkoušet vstup <span>↗</span>";
  status.textContent = "Klepni na displej. Nebo telefon jemně otoč.";
  drawScreen();
  render();
}
function showProfile() {
  document.getElementById("phone-booking-controls").hidden = true;
  screenMode = "profile";
  status.textContent = "Ukázkový profil. Klepnutím na vstup se vrátíš zpět.";
  drawScreen();
  render();
}
function showReservation() {
  screenMode = "reserve";
  document.getElementById("phone-booking-controls").hidden = false;
  enterButton.innerHTML = "Vybrat čas <span>↗</span>";
  status.textContent =
    "Vyber den a čas na displeji. Jde pouze o ukázkové termíny.";
  drawScreen();
  render();
}
function book() {
  reserved = true;
  screenMode = "booked";
  document.getElementById("phone-booking-controls").hidden = true;
  status.textContent = `Ukázkový čas ${selectedDay ? "zítra" : "dnes"} v ${slots[selectedSlot]}. Skutečná rezervace nebyla vytvořena.`;
  enterButton.innerHTML = "Přejít na vstup <span>↗</span>";
  drawScreen();
  render();
}
function success() {
  screenMode = "success";
  status.textContent =
    "Vítej ve svém. Ukázka otevření je hotová — skutečné dveře se neotevřely.";
  enterButton.innerHTML = "Zkusit znovu <span>↗</span>";
  drawScreen();
  render();
}
function unlock() {
  if (screenMode === "reserve") {
    book();
    return;
  }
  if (screenMode === "booked") {
    goHome();
    return;
  }
  if (screenMode === "opening") return;
  if (screenMode === "success") {
    goHome();
    return;
  }
  document.getElementById("phone-booking-controls").hidden = true;
  screenMode = "opening";
  unlockTime = 0;
  unlockStart = time;
  status.textContent = "Ukázka: otevíráme tvůj prostor…";
  if (paused || reduced.matches) {
    success();
    return;
  }
  drawScreen();
  render();
  start();
}
function tick(now) {
  raf = 0;
  if (!visible || document.hidden || paused) return;
  const dt = last ? Math.min(0.045, (now - last) / 1000) : 0;
  last = now;
  time += dt;
  phone.rotation.x = THREE.MathUtils.lerp(phone.rotation.x, rx, 0.12);
  phone.rotation.y = THREE.MathUtils.lerp(phone.rotation.y, ry, 0.12);
  phone.position.y = Math.sin(time * 0.7) * 0.045;
  if (screenMode === "opening") {
    unlockTime = time - unlockStart;
    if (unlockTime >= 1.25) success();
    else drawScreen();
  }
  render();
  raf = requestAnimationFrame(tick);
}
function start() {
  if (!raf && visible && !document.hidden && !paused) {
    last = 0;
    raf = requestAnimationFrame(tick);
  }
}
function stop() {
  cancelAnimationFrame(raf);
  raf = 0;
  last = 0;
}
function size() {
  const w = host.clientWidth,
    h = host.clientHeight;
  if (!w || !h) return;
  renderer.setSize(w, h);
  camera.aspect = w / h;
  camera.position.z = Math.max(7.5, 4.0 / camera.aspect);
  camera.updateProjectionMatrix();
  render();
}
const ray = new THREE.Raycaster(),
  cursor = new THREE.Vector2();
function tap(e) {
  const rect = host.getBoundingClientRect();
  cursor.set(
    ((e.clientX - rect.left) / rect.width) * 2 - 1,
    (-(e.clientY - rect.top) / rect.height) * 2 + 1,
  );
  ray.setFromCamera(cursor, camera);
  const hit = ray.intersectObject(screen)[0];
  if (!hit) return;
  const x = hit.uv.x * 600,
    y = (1 - hit.uv.y) * 1250;
  if (screenMode === "home") {
    if ((x > 460 && y < 195) || (y > 1120 && x > 420)) showProfile();
    else if (y > 1120 && x > 190 && x < 420) showReservation();
    else if (y > 365 && y < 1070) unlock();
  } else if (screenMode === "reserve") {
    if (y < 185) {
      goHome();
      return;
    }
    if (y >= 320 && y <= 416) {
      selectedDay = x > 300 ? 1 : 0;
      document.getElementById("phone-day").value = String(selectedDay);
    } else if (y >= 516 && y <= 722) {
      selectedSlot = Math.min(3, (y >= 629 ? 2 : 0) + (x > 300 ? 1 : 0));
      document.getElementById("phone-time").value = String(selectedSlot);
    } else if (y >= 933 && y <= 1054) {
      book();
      return;
    }
    drawScreen();
    render();
  } else if (screenMode === "booked") {
    if (y > 960) goHome();
  } else if (screenMode === "profile") {
    if (y < 185 || y > 920) goHome();
  } else if (screenMode === "success") {
    goHome();
  }
}
host.addEventListener("pointerdown", (e) => {
  if (e.pointerType === "mouse" && e.button !== 0) return;
  pointer = e.pointerId;
  dragging = true;
  moved = false;
  startX = lastX = e.clientX;
  startY = lastY = e.clientY;
  host.setPointerCapture(pointer);
});
host.addEventListener("pointermove", (e) => {
  if (!dragging || e.pointerId !== pointer) return;
  if (Math.hypot(e.clientX - startX, e.clientY - startY) > 7) moved = true;
  if (moved) {
    ry = THREE.MathUtils.clamp(ry + (e.clientX - lastX) * 0.009, -0.65, 0.65);
    rx = THREE.MathUtils.clamp(rx + (e.clientY - lastY) * 0.005, -0.18, 0.18);
    phone.rotation.set(rx, ry, -0.055);
    render();
  }
  lastX = e.clientX;
  lastY = e.clientY;
});
function release() {
  dragging = false;
  pointer = null;
}
host.addEventListener("pointerup", (e) => {
  if (e.pointerId === pointer && !moved) tap(e);
  release();
});
host.addEventListener("pointercancel", release);
host.addEventListener("lostpointercapture", release);
host.addEventListener("keydown", (e) => {
  if (["Enter", " ", "ArrowLeft", "ArrowRight", "Escape"].includes(e.key)) {
    e.preventDefault();
    if (e.key === "Escape") goHome();
    else if (e.key === "Enter" || e.key === " ") unlock();
    else {
      ry = THREE.MathUtils.clamp(
        ry + (e.key === "ArrowLeft" ? -0.12 : 0.12),
        -0.65,
        0.65,
      );
      phone.rotation.y = ry;
      render();
    }
  }
});
document.getElementById("phone-home").addEventListener("click", goHome);
document
  .getElementById("phone-reserve")
  .addEventListener("click", showReservation);
document.getElementById("phone-day").addEventListener("change", (e) => {
  selectedDay = Number(e.target.value);
  drawScreen();
  render();
});
document.getElementById("phone-time").addEventListener("change", (e) => {
  selectedSlot = Number(e.target.value);
  drawScreen();
  render();
});
document
  .getElementById("phone-profile")
  .addEventListener("click", () =>
    screenMode === "profile" ? goHome() : showProfile(),
  );
enterButton.addEventListener("click", unlock);
new ResizeObserver(size).observe(host);
new IntersectionObserver(
  (e) => {
    visible = e[0].isIntersecting;
    visible ? start() : stop();
  },
  { threshold: 0.05 },
).observe(host);
function setPaused(value) {
  paused = value;
  if (paused) {
    stop();
    if (screenMode === "opening") success();
    phone.position.y = 0;
    render();
  } else start();
}
window.addEventListener("privofit-motion", (e) => setPaused(e.detail.paused));
reduced.addEventListener("change", (e) => setPaused(e.matches));
document.addEventListener("visibilitychange", () =>
  document.hidden ? stop() : start(),
);
window.addEventListener("pagehide", stop);
window.addEventListener("pageshow", () => {
  size();
  start();
});
renderer.domElement.addEventListener("webglcontextlost", (e) => {
  e.preventDefault();
  stop();
  status.textContent = "3D telefon je dočasně nedostupný.";
  enterButton.disabled = true;
  document.getElementById("phone-profile").disabled = true;
});
renderer.domElement.addEventListener("webglcontextrestored", () => {
  enterButton.disabled = false;
  document.getElementById("phone-profile").disabled = false;
  goHome();
  size();
  start();
});
drawScreen();
size();
document.getElementById("phone-loading").hidden = true;
document.fonts.ready.then(() => {
  drawScreen();
  render();
});
start();
