import * as THREE from "./vendor/three.module.js";
const host = document.getElementById("three-stage");
const reduced = matchMedia("(prefers-reduced-motion: reduce)");
let paused =
    reduced.matches || document.body.classList.contains("motion-paused"),
  visible = true,
  frame = 0,
  lastTime = 0,
  time = 0;
let exercise = "curl",
  reps = 0,
  growth = 0,
  targetGrowth = 0,
  queue = 0,
  phase = -1,
  yaw = 0.12,
  targetYaw = 0.12,
  dragging = false,
  moved = false,
  pointerId = null,
  startX = 0,
  startY = 0,
  oldX = 0;
const renderer = new THREE.WebGLRenderer({
  alpha: true,
  antialias: true,
  powerPreference: "low-power",
});
renderer.setPixelRatio(Math.min(devicePixelRatio, 1.6));
renderer.setClearColor(0x0b1210, 0);
renderer.outputColorSpace = THREE.SRGBColorSpace;
renderer.toneMapping = THREE.ACESFilmicToneMapping;
renderer.toneMappingExposure = 1.05;
const scene = new THREE.Scene(),
  camera = new THREE.PerspectiveCamera(35, 1, 0.1, 40);
camera.position.set(0, 2.12, 8.4);
camera.lookAt(0, 1.82, 0);
scene.add(new THREE.HemisphereLight(0xf7ffe9, 0x263326, 2.3));
for (const [color, power, pos] of [
  [0xffffff, 3.5, [-3, 5, 5]],
  [0xc6f21a, 2.4, [3, 3, -3]],
  [0xd9e6d4, 2, [3, 1, 4]],
]) {
  const l = new THREE.DirectionalLight(color, power);
  l.position.set(...pos);
  scene.add(l);
}
const world = new THREE.Group();
scene.add(world);
const character = new THREE.Group();
world.add(character);
const mat = (color, roughness = 0.65, metalness = 0.05) =>
  new THREE.MeshStandardMaterial({ color, roughness, metalness });
const skin = mat(0xdcb99a, 0.58),
  shirt = mat(0xc6f21a, 0.46),
  shorts = mat(0x263228),
  sole = mat(0xe8f0e4),
  dark = mat(0x152019),
  hairMat = mat(0x2a3024),
  white = mat(0xf8fbef),
  black = mat(0x111913),
  metal = mat(0xa0ad98, 0.27, 0.75),
  plate = mat(0x1c2820, 0.4, 0.3);
const sphereGeo = new THREE.SphereGeometry(1, 36, 24),
  unitY = new THREE.Vector3(0, 1, 0);
const limbProfile = [
  [-0.5, 0.6],
  [-0.46, 0.78],
  [-0.32, 0.94],
  [-0.1, 1.06],
  [0.13, 1.04],
  [0.34, 0.9],
  [0.46, 0.75],
  [0.5, 0.6],
];
const cylinderGeo = new THREE.LatheGeometry(
  limbProfile.map(([y, r]) => new THREE.Vector2(r, y)),
  32,
);
const bodyProfile = [
  [0, 0.28],
  [0.09, 0.37],
  [0.3, 0.38],
  [0.52, 0.43],
  [0.74, 0.53],
  [0.9, 0.54],
  [1.01, 0.42],
  [1.07, 0.2],
];

function ellipsoid(parent, material, x, y, z, sx, sy, sz) {
  const m = new THREE.Mesh(sphereGeo, material);
  m.position.set(x, y, z);
  m.scale.set(sx, sy, sz);
  parent.add(m);
  return m;
}
function segment(parent, material, radius) {
  const m = new THREE.Mesh(cylinderGeo, material);
  parent.add(m);
  return { mesh: m, radius };
}
function connect(part, a, b, r = part.radius) {
  const av = new THREE.Vector3(...a),
    bv = new THREE.Vector3(...b);
  part.mesh.position.copy(av).add(bv).multiplyScalar(0.5);
  part.mesh.quaternion.setFromUnitVectors(unitY, bv.sub(av).normalize());
  part.mesh.scale.set(
    r,
    new THREE.Vector3(...a).distanceTo(new THREE.Vector3(...b)),
    r,
  );
}
function cylinder(parent, r, len, material, x, y, z) {
  const m = new THREE.Mesh(new THREE.CylinderGeometry(r, r, len, 32), material);
  m.rotation.z = Math.PI / 2;
  m.position.set(x, y, z);
  parent.add(m);
  return m;
}
function weight(isBar) {
  const g = new THREE.Group(),
    span = isBar ? 2.8 : 0.68,
    r = isBar ? 0.38 : 0.19;
  cylinder(g, 0.045, span + 0.25, metal, 0, 0, 0);
  for (const s of [-1, 1]) {
    cylinder(g, r, isBar ? 0.2 : 0.15, plate, (s * span) / 2, 0, 0);
    cylinder(g, r * 1.02, 0.035, shirt, s * (span / 2 + 0.07), 0, 0);
    cylinder(g, r * 0.75, 0.03, dark, s * (span / 2 + 0.12), 0, 0);
  }
  return g;
}
const dumbbells = [weight(false), weight(false)];
dumbbells.forEach((d) => character.add(d));
const barbell = weight(true);
barbell.name = "barbell";
character.add(barbell);
const torso = new THREE.Group();
character.add(torso);
const bodyMesh = new THREE.Mesh(
  new THREE.LatheGeometry(
    bodyProfile.map(([y, r]) => new THREE.Vector2(r, y)),
    48,
  ),
  shirt,
);
bodyMesh.scale.z = 0.61;
torso.add(bodyMesh);
const waist = new THREE.Object3D(),
  rib = new THREE.Object3D();
const pecs = [-1, 1].map((s) =>
  ellipsoid(torso, shirt, s * 0.25, 0.77, 0.165, 0.245, 0.235, 0.115),
);
const neck = ellipsoid(torso, skin, 0, 1.12, 0, 0.14, 0.21, 0.14);
const logo = new THREE.Mesh(new THREE.BoxGeometry(0.1, 0.25, 0.025), dark);
logo.position.set(-0.12, 0.75, 0.333);
torso.add(logo);
const logoTop = new THREE.Mesh(new THREE.BoxGeometry(0.19, 0.08, 0.025), dark);
logoTop.position.set(-0.065, 0.84, 0.333);
torso.add(logoTop);
const pelvis = ellipsoid(character, shorts, 0, 1.6, 0, 0.4, 0.29, 0.28);
const head = new THREE.Group();
head.name = "head";
torso.add(head);
head.position.set(0, 1.39, 0.015);
head.scale.setScalar(1.1);
ellipsoid(head, skin, 0, 0.015, 0, 0.315, 0.36, 0.28);
ellipsoid(head, skin, 0, -0.15, 0.02, 0.263, 0.21, 0.248);
for (const side of [-1, 1])
  ellipsoid(head, skin, side * 0.18, -0.07, 0.15, 0.125, 0.13, 0.11);
const hairCap = new THREE.Mesh(
  new THREE.SphereGeometry(1, 36, 20, 0, Math.PI * 2, 0, Math.PI * 0.57),
  hairMat,
);
hairCap.scale.set(0.327, 0.38, 0.286);
hairCap.position.set(0, 0.06, -0.025);
head.add(hairCap);
const quiff = ellipsoid(head, hairMat, -0.055, 0.31, 0.105, 0.265, 0.125, 0.22);
quiff.rotation.z = -0.19;
const sidecut = ellipsoid(head, hairMat, -0.275, 0.1, -0.01, 0.055, 0.14, 0.18);
for (let i = 0; i < 3; i++) {
  const strand = ellipsoid(
    head,
    mat(0x394032),
    -0.14 + i * 0.08,
    0.365,
    0.135,
    0.027,
    0.018,
    0.135,
  );
  strand.rotation.y = -0.25;
}
for (const s of [-1, 1])
  ellipsoid(head, skin, s * 0.31, -0.025, 0, 0.07, 0.11, 0.07);
const eyeGroups = [];
for (const s of [-1, 1]) {
  const eye = new THREE.Group();
  eye.position.set(s * 0.115, 0.035, 0.258);
  eye.name = "eye-" + s;
  head.add(eye);
  ellipsoid(eye, white, 0, 0, 0, 0.072, 0.07, 0.031);
  ellipsoid(eye, black, s * 0.004, 0, 0.029, 0.033, 0.044, 0.014);
  ellipsoid(eye, white, -0.009, 0.017, 0.041, 0.01, 0.012, 0.006);
  eyeGroups.push(eye);
  const brow = new THREE.Mesh(
    new THREE.CapsuleGeometry(0.015, 0.1, 4, 8),
    hairMat,
  );
  brow.rotation.z = Math.PI / 2 + s * 0.1;
  brow.position.set(s * 0.115, 0.155, 0.253);
  head.add(brow);
}
ellipsoid(head, skin, 0, -0.055, 0.295, 0.055, 0.07, 0.054);
const smile = new THREE.Mesh(
  new THREE.TorusGeometry(0.078, 0.009, 8, 32, Math.PI),
  dark,
);
smile.rotation.z = Math.PI;
smile.position.set(0, -0.135, 0.263);
head.add(smile);
const arms = [],
  legs = [];
for (const s of [-1, 1]) {
  arms.push({
    s,
    upper: segment(character, skin, 0.145),
    fore: segment(character, skin, 0.12),
    shoulder: ellipsoid(character, skin, 0, 0, 0, 0.2, 0.22, 0.2),
    elbow: ellipsoid(character, skin, 0, 0, 0, 0.14, 0.14, 0.14),
    bicep: ellipsoid(character, skin, 0, 0, 0, 0.18, 0.24, 0.18),
    hand: ellipsoid(character, skin, 0, 0, 0, 0.105, 0.13, 0.105),
  });
  legs.push({
    s,
    thigh: segment(character, shorts, 0.2),
    calf: segment(character, skin, 0.135),
    knee: ellipsoid(character, skin, 0, 0, 0, 0.15, 0.15, 0.15),
    shoe: ellipsoid(character, dark, s * 0.27, 0.14, 0.11, 0.19, 0.13, 0.34),
    shoeSole: ellipsoid(
      character,
      sole,
      s * 0.27,
      0.061,
      0.12,
      0.2,
      0.055,
      0.35,
    ),
  });
}
for (const side of [-1, 1]) {
  const cuff = ellipsoid(
    character,
    sole,
    side * 0.28,
    0.32,
    0.025,
    0.142,
    0.09,
    0.14,
  );
  for (let j = 0; j < 3; j++) {
    const lace = new THREE.Mesh(
      new THREE.CapsuleGeometry(0.011, 0.17, 3, 8),
      sole,
    );
    lace.rotation.z = Math.PI / 2;
    lace.rotation.y = side * 0.08;
    lace.position.set(side * 0.27, 0.243 - j * 0.012, 0.13 + j * 0.055);
    character.add(lace);
  }
  const shoePatch = ellipsoid(
    character,
    shirt,
    side * 0.41,
    0.145,
    0.09,
    0.035,
    0.035,
    0.14,
  );
}
const floor = new THREE.Mesh(
  new THREE.CylinderGeometry(1.32, 1.35, 0.028, 64),
  mat(0x142016, 0.8),
);
floor.position.y = -0.018;
world.add(floor);
const ring = new THREE.Mesh(new THREE.TorusGeometry(1.32, 0.007, 8, 80), shirt);
ring.rotation.x = Math.PI / 2;
ring.position.y = 0.013;
world.add(ring);
// Sweat droplets are independent meshes, emitted only as the workout builds.
const sweatMat = new THREE.MeshStandardMaterial({
  color: 0x87cbe5,
  transparent: true,
  opacity: 0.75,
  roughness: 0.18,
});
const drops = Array.from({ length: 10 }, (_, i) => {
  const m = ellipsoid(character, sweatMat, 0, 0, 0, 0.027, 0.065, 0.027);
  m.visible = false;
  return { mesh: m, offset: i * 0.43, side: i % 2 ? 1 : -1 };
});
const count = document.getElementById("rep-count"),
  status = document.getElementById("trainer-status");
function updateUI() {
  count.textContent = reps;
}
function requestRep() {
  reps++;
  targetGrowth = Math.min(1, reps / 28);
  updateUI();
  status.textContent = `${reps} kliknutí. Svaly rostou.`;
  if (paused || reduced.matches) {
    growth = targetGrowth;
    pose(0);
    render();
    return;
  }
  if (phase < 0) {
    phase = 0;
    start();
  } else queue = Math.min(queue + 1, 3);
}
function pose(effort) {
  const dead = exercise === "deadlift";
  const rise = dead ? effort : 1;
  const breath = paused ? 0 : Math.sin(time * 1.8) * 0.009;
  const hipY = dead ? 1.27 + rise * 0.4 : 1.67;
  const hipZ = dead ? -0.31 * (1 - rise) : 0;
  const lean = dead ? 0.58 * (1 - rise) : 0;
  torso.position.set(0, hipY + breath, hipZ);
  torso.rotation.x = lean;
  pelvis.position.set(0, hipY - 0.04, hipZ);
  pelvis.scale.x = 0.4 + growth * 0.025;
  bodyMesh.scale.set(1 + growth * 0.22, 1, 0.61 + growth * 0.09);
  pecs.forEach((p, i) => {
    p.position.x = (i ? 1 : -1) * (0.25 + growth * 0.04);
    p.scale.set(
      0.245 + growth * 0.065,
      0.235 + growth * 0.04,
      0.115 + growth * 0.055,
    );
  });
  torso.updateMatrixWorld(true);
  const shoulderY = hipY + Math.cos(lean) * 1.05 + breath,
    shoulderZ = hipZ + Math.sin(lean) * 1.05;
  const barY = 0.59 + rise * 0.68,
    barZ = 0.59;
  arms.forEach((a, i) => {
    const shoulder = [a.s * (0.55 + growth * 0.1), shoulderY, shoulderZ];
    let elbow, wrist;
    if (dead) {
      wrist = [a.s * 0.6, barY, barZ];
      const mid = shoulder.map((v, j) => (v + wrist[j]) / 2);
      elbow = [mid[0] + a.s * 0.035, mid[1], mid[2] - 0.035];
    } else {
      const angle = effort * 2.12;
      elbow = [
        a.s * (0.59 + growth * 0.1),
        shoulderY - 0.62,
        shoulderZ + 0.045,
      ];
      wrist = [
        elbow[0],
        elbow[1] - 0.63 * Math.cos(angle),
        elbow[2] + 0.63 * Math.sin(angle),
      ];
    }
    connect(a.upper, shoulder, elbow, 0.145 + growth * 0.1);
    connect(a.fore, elbow, wrist, 0.12 + growth * 0.045);
    a.shoulder.position.set(...shoulder);
    a.shoulder.scale.setScalar(0.2 + growth * 0.11);
    a.elbow.position.set(...elbow);
    a.bicep.position.set(
      (shoulder[0] + elbow[0]) / 2,
      (shoulder[1] + elbow[1]) / 2,
      (shoulder[2] + elbow[2]) / 2 + 0.015,
    );
    a.bicep.scale.set(
      0.17 + growth * 0.15 + effort * 0.025,
      0.25,
      0.17 + growth * 0.12 + effort * 0.025,
    );
    a.bicep.quaternion.copy(a.upper.mesh.quaternion);
    a.hand.position.set(...wrist);
    dumbbells[i].visible = !dead;
    dumbbells[i].position.set(...wrist);
    dumbbells[i].rotation.x = dead ? 0 : -effort * 0.5;
  });
  legs.forEach((l) => {
    const hip = [l.s * 0.25, hipY - 0.1, hipZ],
      knee = [
        l.s * 0.28,
        0.88 - (dead ? (1 - rise) * 0.06 : 0),
        dead ? 0.19 * (1 - rise) : 0.025,
      ],
      ankle = [l.s * 0.28, 0.2, 0.025];
    connect(l.thigh, hip, knee, 0.2 + growth * 0.055);
    connect(l.calf, knee, ankle, 0.135 + growth * 0.047);
    l.knee.position.set(...knee);
  });
  barbell.visible = dead;
  barbell.position.set(0, barY, barZ);
  const blink = time % 4.3;
  const lid = paused
    ? 1
    : blink < 0.14
      ? Math.max(0.08, Math.abs(blink - 0.07) / 0.07)
      : 1;
  eyeGroups.forEach((e) => (e.scale.y = lid));
  head.rotation.z = paused ? 0 : Math.sin(time * 0.65) * 0.022;
  head.rotation.x = -lean * 0.55;
  const headWorld = new THREE.Vector3();
  head.getWorldPosition(headWorld);
  character.worldToLocal(headWorld);
  drops.forEach((d, i) => {
    const t = (time * 0.8 + d.offset) % 2.2;
    d.mesh.visible =
      !paused &&
      reps >= 3 &&
      i < Math.min(10, 2 + Math.floor(reps / 3)) &&
      t < 1.4;
    if (d.mesh.visible) {
      d.mesh.position.set(
        headWorld.x + d.side * (0.3 + t * 0.1),
        headWorld.y + 0.12 - t * 0.66,
        headWorld.z + 0.16 + Math.sin(i) * 0.05,
      );
      d.mesh.scale.y = 0.065 * (1 - t * 0.3);
    }
  });
}
let entranceTime = paused ? 2 : 0;
function render() {
  const progress = paused
    ? 1
    : THREE.MathUtils.clamp((entranceTime - 0.1) / 1.25, 0, 1);
  const eased = 1 - Math.pow(1 - progress, 3);
  world.position.set((1 - eased) * 0.24, -(1 - eased) * 0.32, 0);
  world.scale.setScalar(0.86 + 0.14 * eased);
  world.rotation.y = yaw - (1 - eased) * 0.55;
  renderer.domElement.style.opacity = String(
    THREE.MathUtils.smoothstep(progress, 0, 0.5),
  );
  renderer.render(scene, camera);
}
function tick(now) {
  frame = 0;
  if (document.hidden || !visible) return;
  const dt = lastTime ? Math.min((now - lastTime) / 1000, 0.04) : 0;
  lastTime = now;
  if (!paused) {
    time += dt;
    entranceTime += dt;
    growth = THREE.MathUtils.lerp(growth, targetGrowth, Math.min(1, dt * 3));
    yaw = THREE.MathUtils.lerp(yaw, targetYaw, Math.min(1, dt * 9));
    if (phase >= 0) {
      phase += dt / (exercise === "deadlift" ? 1.65 : 1.25);
      if (phase >= 1) {
        phase = queue > 0 ? 0 : -1;
        if (queue > 0) queue--;
        updateUI();
      }
    }
  }
  world.rotation.y = yaw;
  const effort = phase < 0 ? 0 : Math.sin(Math.PI * Math.min(1, phase)) ** 2;
  pose(effort);
  render();
  if (!paused) frame = requestAnimationFrame(tick);
}
function start() {
  if (!frame && visible && !document.hidden && !paused) {
    lastTime = 0;
    frame = requestAnimationFrame(tick);
  }
}
function stop() {
  cancelAnimationFrame(frame);
  frame = 0;
  lastTime = 0;
}
function resize() {
  const w = host.clientWidth,
    h = host.clientHeight;
  if (!w || !h) return;
  renderer.setSize(w, h);
  camera.aspect = w / h;
  camera.position.z = Math.max(6.15, 3.1 / camera.aspect);
  camera.updateProjectionMatrix();
  pose(phase < 0 ? 0 : Math.sin(Math.PI * phase) ** 2);
  render();
}
host.appendChild(renderer.domElement);
pose(0);
resize();
document.getElementById("scene-fallback").hidden = true;
document.getElementById("scene-loading").hidden = true;
updateUI();
host.addEventListener("pointerdown", (e) => {
  if (e.pointerType === "mouse" && e.button !== 0) return;
  dragging = true;
  moved = false;
  pointerId = e.pointerId;
  startX = oldX = e.clientX;
  startY = e.clientY;
  host.setPointerCapture(e.pointerId);
  host.classList.add("dragging");
});
host.addEventListener("pointermove", (e) => {
  if (!dragging || e.pointerId !== pointerId) return;
  if (Math.hypot(e.clientX - startX, e.clientY - startY) > 7) moved = true;
  if (moved) {
    targetYaw += (e.clientX - oldX) * 0.012;
    yaw = targetYaw;
    world.rotation.y = yaw;
    render();
    document.querySelectorAll("[data-view]").forEach((b) => {
      b.classList.remove("active");
      b.setAttribute("aria-pressed", "false");
    });
  }
  oldX = e.clientX;
});
const release = () => {
  dragging = false;
  pointerId = null;
  host.classList.remove("dragging");
};
host.addEventListener("pointerup", (e) => {
  if (e.pointerId === pointerId && !moved) requestRep();
  release();
});
host.addEventListener("pointercancel", release);
host.addEventListener("lostpointercapture", release);
host.addEventListener("keydown", (e) => {
  if ([" ", "Enter", "ArrowLeft", "ArrowRight"].includes(e.key)) {
    e.preventDefault();
    if (e.key === " " || e.key === "Enter") requestRep();
    else {
      targetYaw += e.key === "ArrowLeft" ? -0.2 : 0.2;
      yaw = targetYaw;
      world.rotation.y = yaw;
      render();
    }
  }
});
new ResizeObserver(resize).observe(host);
new IntersectionObserver(
  (entries) => {
    visible = entries[0].isIntersecting;
    visible ? start() : stop();
  },
  { threshold: 0.04 },
).observe(host);
function setPaused(value) {
  paused = value;
  if (paused) {
    phase = -1;
    queue = 0;
    updateUI();
    stop();
    pose(phase < 0 ? 0 : Math.sin(Math.PI * phase) ** 2);
    render();
  } else start();
}
window.addEventListener("privofit-motion", (e) => setPaused(e.detail.paused));
reduced.addEventListener("change", (e) => setPaused(e.matches));
document.addEventListener("visibilitychange", () =>
  document.hidden ? stop() : start(),
);
renderer.domElement.addEventListener("webglcontextlost", (e) => {
  e.preventDefault();
  stop();
  const fallback = document.getElementById("scene-fallback");
  if (fallback) fallback.hidden = true;
  document.getElementById("scene-loading").hidden = false;
  document.getElementById("scene-loading").textContent =
    "Tvůj parťák se znovu rozcvičuje…";
  host.setAttribute("aria-disabled", "true");
});
renderer.domElement.addEventListener("webglcontextrestored", () => {
  document.getElementById("scene-fallback").hidden = true;
  document.getElementById("scene-loading").hidden = true;
  host.removeAttribute("aria-disabled");
  resize();
  start();
});
window.addEventListener("pagehide", stop);
window.addEventListener("pageshow", () => {
  resize();
  start();
});
start();
