import * as THREE from "./vendor/three.module.js";
const isAuth = /\/(registrace|prihlaseni)(?:\/(?:index\.html)?)?$/.test(
  location.pathname,
);
const host = document.getElementById(isAuth ? "auth-3d" : "peek-stage");
const reduced = matchMedia("(prefers-reduced-motion: reduce)");
let paused =
    reduced.matches || document.body.classList.contains("motion-paused"),
  visible = true,
  raf = 0,
  last = 0,
  time = 0,
  mx = 0.15,
  my = 0.05,
  dragging = false,
  lastX = 0,
  lastY = 0,
  targetX = -0.08,
  targetY = -0.23;
const renderer = new THREE.WebGLRenderer({
  alpha: true,
  antialias: true,
  powerPreference: "low-power",
});
renderer.setPixelRatio(Math.min(devicePixelRatio, 1.5));
renderer.setClearColor(0, 0);
renderer.outputColorSpace = THREE.SRGBColorSpace;
renderer.toneMapping = THREE.ACESFilmicToneMapping;
renderer.toneMappingExposure = 1.12;
host.appendChild(renderer.domElement);
const scene = new THREE.Scene(),
  camera = new THREE.PerspectiveCamera(35, 1, 0.1, 30);
camera.position.z = isAuth ? 6.2 : 4.8;
scene.add(new THREE.HemisphereLight(0xf7fff0, 0x263a25, 2.7));
for (const [c, i, p] of [
  [0xffffff, 3.5, [-3, 4, 5]],
  [0xc6f21a, 2, [3, 1, -2]],
]) {
  const light = new THREE.DirectionalLight(c, i);
  light.position.set(...p);
  scene.add(light);
}
const group = new THREE.Group();
scene.add(group);
const mat = (color, roughness = 0.55, metalness = 0.05) =>
  new THREE.MeshStandardMaterial({ color, roughness, metalness });
const sphere = new THREE.SphereGeometry(1, 32, 24);
function ellipsoid(parent, material, pos, scale) {
  const m = new THREE.Mesh(sphere, material);
  m.position.set(...pos);
  m.scale.set(...scale);
  parent.add(m);
  return m;
}
function rounded(w, h, r) {
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
const banner = isAuth ? null : document.querySelector(".last-cta .cta-surface");
const ctaButton = isAuth ? null : document.querySelector(".last-cta .button");
let peekTarget = 0,
  peekAmount = 0,
  lastScroll = window.scrollY;
const bannerClip = new THREE.Plane(new THREE.Vector3(0, 1, 0), 0);
let pointingArm, pointingForearm, pointingHand, pointingSkin;
let peekVelocity = 0,
  gestureAmount = 0,
  gestureClock = 0,
  hideDelay = 0;
let scrollTravel = 0,
  scrollDirection = 0;
const directionY = new THREE.Vector3(0, 1, 0);
let cardTexture,
  cardContext,
  head,
  eyes = [],
  pupils = [];
const brandLogo = new Image();
brandLogo.decoding = "async";
brandLogo.onload = () => paintCard();
{
  const base = document.documentElement.getAttribute("data-base") || "/";
  const prefix = base.endsWith("/") ? base : base + "/";
  brandLogo.src = `${prefix}assets/brand/logo-transparent-dark.png?v=2`;
}
function paintCard(detail = {}) {
  if (!cardContext) return;
  const ctx = cardContext;
  const c = ctx.createLinearGradient(0, 0, 1200, 730);
  c.addColorStop(0, "#d7fb64");
  c.addColorStop(0.6, "#C6F21A");
  c.addColorStop(1, "#8FBF00");
  ctx.fillStyle = c;
  ctx.fillRect(0, 0, 1200, 730);
  ctx.strokeStyle = "#10171413";
  ctx.lineWidth = 1.5;
  for (let i = 0; i < 8; i++) {
    ctx.beginPath();
    ctx.arc(1070, 380, 150 + i * 37, 0, Math.PI * 2);
    ctx.stroke();
  }
  if (brandLogo.complete && brandLogo.naturalWidth) {
    const h = 78;
    const w = brandLogo.naturalWidth * (h / brandLogo.naturalHeight);
    ctx.drawImage(brandLogo, 72, 48, w, h);
  } else {
    ctx.fillStyle = "#101714";
    ctx.font = "800 73px Syne,sans-serif";
    ctx.fillText("privofit", 72, 125);
  }
  ctx.fillStyle = "#101714";
  ctx.font = "600 21px Figtree,sans-serif";
  ctx.fillText("TVŮJ PROSTOR. TVOJE TEMPO.", 76, 179);
  ctx.font = "500 19px Figtree,sans-serif";
  ctx.fillText("ČLENSKÁ KARTA · UKÁZKA", 76, 406);
  ctx.font = "600 63px Syne,sans-serif";
  const name = (
    detail.name ||
    document.querySelector('[name="firstName"]')?.value ||
    "Tvoje jméno"
  )
    .trim()
    .slice(0, 22);
  ctx.fillText(name, 72, 494);
  ctx.font = "500 24px Figtree,sans-serif";
  ctx.fillText(
    detail.complete
      ? "PŘIPRAVENO NA NOVÝ ZAČÁTEK"
      : `KROK ${Math.min(3, (detail.step || 0) + 1)} ZE 3`,
    76,
    650,
  );
  for (let i = 0; i < 3; i++) {
    ctx.fillStyle = i <= (detail.step || 0) ? "#101714" : "#1017142b";
    ctx.beginPath();
    ctx.roundRect(900 + i * 61, 623, 45, 9, 5);
    ctx.fill();
  }
  ctx.fillStyle = "#101714";
  ctx.font = "600 83px Figtree,sans-serif";
  ctx.fillText("↗", 1040, 136);
  cardTexture.needsUpdate = true;
}
if (isAuth) {
  const body = new THREE.Mesh(
    new THREE.ExtrudeGeometry(rounded(3.6, 2.2, 0.19), {
      depth: 0.07,
      bevelEnabled: true,
      bevelSize: 0.025,
      bevelThickness: 0.025,
      bevelSegments: 4,
      curveSegments: 18,
    }),
    mat(0x96b92b, 0.3, 0.55),
  );
  body.position.z = -0.05;
  group.add(body);
  const canvas = document.createElement("canvas");
  canvas.width = 1200;
  canvas.height = 730;
  cardContext = canvas.getContext("2d");
  cardTexture = new THREE.CanvasTexture(canvas);
  cardTexture.colorSpace = THREE.SRGBColorSpace;
  const face = new THREE.Mesh(
    new THREE.ShapeGeometry(rounded(3.57, 2.17, 0.18), 24),
    new THREE.MeshBasicMaterial({ map: cardTexture, toneMapped: false }),
  );
  const p = face.geometry.attributes.position,
    uv = face.geometry.attributes.uv;
  for (let i = 0; i < uv.count; i++)
    uv.setXY(i, (p.getX(i) + 1.785) / 3.57, (p.getY(i) + 1.085) / 2.17);
  face.position.z = 0.046;
  group.add(face);
  group.rotation.set(targetX, targetY, -0.09);
  paintCard();
  document.fonts.ready.then(() => {
    paintCard();
    render();
  });
  window.addEventListener("privofit-registration", (e) => {
    paintCard(e.detail);
    targetY = -0.23 + (e.detail.step || 0) * 0.16;
    render();
  });
  document.getElementById("auth-3d-loading").hidden = true;
  host.addEventListener("pointerdown", (e) => {
    if (e.pointerType === "mouse" && e.button !== 0) return;
    dragging = true;
    lastX = e.clientX;
    lastY = e.clientY;
    host.setPointerCapture(e.pointerId);
  });
  host.addEventListener("pointermove", (e) => {
    if (!dragging) return;
    targetY = THREE.MathUtils.clamp(
      targetY + (e.clientX - lastX) * 0.009,
      -0.75,
      0.75,
    );
    targetX = THREE.MathUtils.clamp(
      targetX + (e.clientY - lastY) * 0.006,
      -0.35,
      0.35,
    );
    lastX = e.clientX;
    lastY = e.clientY;
    group.rotation.set(targetX, targetY, -0.09);
    render();
  });
  for (const name of ["pointerup", "pointercancel", "lostpointercapture"])
    host.addEventListener(name, () => (dragging = false));
} else {
  const skin = mat(0xdcb99a),
    hair = mat(0x283225),
    lime = mat(0xc6f21a),
    white = mat(0xf8fbee),
    dark = mat(0x121a11);
  renderer.localClippingEnabled = true;
  for (const material of [skin, hair, lime, white, dark])
    material.clippingPlanes = [bannerClip];
  ellipsoid(group, lime, [0, -0.64, 0], [0.8, 0.7, 0.38]);
  ellipsoid(group, skin, [0, -0.09, 0.02], [0.22, 0.29, 0.21]);
  head = new THREE.Group();
  head.position.set(0, 0.48, 0.03);
  group.add(head);
  ellipsoid(head, skin, [0, 0, 0], [0.5, 0.59, 0.43]);
  ellipsoid(head, skin, [0, -0.25, 0.04], [0.41, 0.31, 0.38]);
  for (const sign of [-1, 1])
    ellipsoid(head, skin, [sign * 0.48, -0.03, -0.02], [0.105, 0.16, 0.095]);
  const cap = new THREE.Mesh(
    new THREE.SphereGeometry(1, 36, 24, 0, Math.PI * 2, 0, Math.PI * 0.56),
    hair,
  );
  cap.scale.set(0.52, 0.61, 0.44);
  cap.position.set(0, 0.07, -0.03);
  head.add(cap);
  const quiff = ellipsoid(head, hair, [-0.08, 0.5, 0.15], [0.43, 0.16, 0.3]);
  quiff.rotation.z = -0.18;
  for (const sign of [-1, 1]) {
    const eye = new THREE.Group();
    eye.position.set(sign * 0.18, 0.055, 0.397);
    head.add(eye);
    ellipsoid(eye, white, [0, 0, 0], [0.115, 0.11, 0.047]);
    const pupil = ellipsoid(eye, dark, [0, 0, 0.043], [0.052, 0.062, 0.018]);
    ellipsoid(pupil, white, [-0.22, 0.27, 0.9], [0.24, 0.22, 0.25]);
    eyes.push(eye);
    pupils.push(pupil);
    const brow = new THREE.Mesh(
      new THREE.CapsuleGeometry(0.025, 0.16, 4, 10),
      hair,
    );
    brow.rotation.z = Math.PI / 2 + sign * 0.13;
    brow.position.set(sign * 0.18, 0.25, 0.39);
    head.add(brow);
  }
  ellipsoid(head, skin, [0, -0.08, 0.46], [0.08, 0.1, 0.075]);
  const smile = new THREE.Mesh(
    new THREE.TorusGeometry(0.12, 0.016, 8, 32, Math.PI),
    dark,
  );
  smile.rotation.z = Math.PI;
  smile.position.set(0, -0.22, 0.42);
  head.add(smile);
  // The resting hand stays behind the banner. The pointing arm reaches over it.
  ellipsoid(group, skin, [-0.72, -0.5, 0.3], [0.17, 0.24, 0.16]);
  for (let i = 0; i < 3; i++)
    ellipsoid(
      group,
      skin,
      [-0.63 - i * 0.065, -0.51, 0.43],
      [0.041, 0.14, 0.048],
    );
  pointingSkin = skin.clone();
  pointingSkin.transparent = true;
  pointingSkin.clippingPlanes = [];
  const armGeometry = new THREE.CapsuleGeometry(1, 2, 6, 16);
  pointingArm = new THREE.Mesh(armGeometry, pointingSkin);
  pointingForearm = new THREE.Mesh(armGeometry, pointingSkin);
  group.add(pointingArm, pointingForearm);
  pointingHand = new THREE.Group();
  pointingHand.name = "pointing-hand";
  group.add(pointingHand);
  ellipsoid(pointingHand, pointingSkin, [0, 0, 0], [0.13, 0.16, 0.09]);
  // An extended index finger; the remaining fingers curl into the palm.
  ellipsoid(
    pointingHand,
    pointingSkin,
    [-0.07, 0.235, 0.01],
    [0.044, 0.2, 0.045],
  );
  for (let i = 0; i < 3; i++)
    ellipsoid(
      pointingHand,
      pointingSkin,
      [i * 0.065 - 0.015, 0.09, 0.065],
      [0.04, 0.09, 0.048],
    );
  ellipsoid(
    pointingHand,
    pointingSkin,
    [-0.125, -0.005, 0.07],
    [0.06, 0.08, 0.045],
  );
  group.rotation.y = -0.08;
  window.addEventListener(
    "pointermove",
    (e) => {
      mx = THREE.MathUtils.clamp((e.clientX / innerWidth - 0.5) * 2, -1, 1);
      my = THREE.MathUtils.clamp((0.5 - e.clientY / innerHeight) * 2, -1, 1);
    },
    { passive: true },
  );
}
function screenToWorld(x, y, z = 0) {
  const rect = host.getBoundingClientRect();
  const point = new THREE.Vector3(
    ((x - rect.left) / rect.width) * 2 - 1,
    1 - ((y - rect.top) / rect.height) * 2,
    0.5,
  ).unproject(camera);
  const direction = point.sub(camera.position).normalize();
  return camera.position
    .clone()
    .addScaledVector(direction, (z - camera.position.z) / direction.z);
}
function positionPointer() {
  if (isAuth || !pointingHand || !ctaButton) return;
  scene.updateMatrixWorld(true);
  const button = ctaButton.getBoundingClientRect();
  const target = group.worldToLocal(
    screenToWorld(
      button.left + button.width * 0.5,
      button.top + button.height * 0.5,
      0.35,
    ),
  );
  const shoulder = new THREE.Vector3(0.61, -0.27, 0.12);
  const restingWrist = new THREE.Vector3(0.72, -0.34, 0.43);
  const aim = target.clone().sub(shoulder).normalize();
  const extendedWrist = shoulder.clone().addScaledVector(aim, 0.8);
  extendedWrist.x += 0.13;
  extendedWrist.z += 0.1;
  const wrist = restingWrist.clone().lerp(extendedWrist, gestureAmount);
  const upperLength = 0.48,
    foreLength = 0.43;
  const axis = wrist.clone().sub(shoulder);
  const distance = THREE.MathUtils.clamp(
    axis.length(),
    0.2,
    upperLength + foreLength - 0.025,
  );
  axis.normalize();
  wrist.copy(shoulder).addScaledVector(axis, distance);
  const bend = new THREE.Vector3(1, 0.12, 0.3)
    .addScaledVector(axis, -new THREE.Vector3(1, 0.12, 0.3).dot(axis))
    .normalize();
  const along =
    (upperLength ** 2 - foreLength ** 2 + distance ** 2) / (2 * distance);
  const height = Math.sqrt(Math.max(0, upperLength ** 2 - along ** 2));
  const elbow = shoulder
    .clone()
    .addScaledVector(axis, along)
    .addScaledVector(bend, height);
  function limb(mesh, a, b, r) {
    mesh.position.copy(a).add(b).multiplyScalar(0.5);
    mesh.quaternion.setFromUnitVectors(
      directionY,
      b.clone().sub(a).normalize(),
    );
    mesh.scale.set(r, a.distanceTo(b) / 4, r);
  }
  limb(pointingArm, shoulder, elbow, 0.13);
  limb(pointingForearm, elbow, wrist, 0.105);
  pointingHand.position.copy(wrist);
  const fingerDirection = new THREE.Vector3(-0.12, 0.95, 0.22)
    .lerp(target.sub(wrist).normalize(), gestureAmount)
    .normalize();
  pointingHand.quaternion.setFromUnitVectors(directionY, fingerDirection);
  pointingSkin.opacity = THREE.MathUtils.smoothstep(peekAmount, 0.45, 0.93);
  pointingArm.visible =
    pointingForearm.visible =
    pointingHand.visible =
      pointingSkin.opacity > 0.005;
}
function syncBannerGeometry() {
  if (isAuth || !banner) return;
  camera.updateMatrixWorld(true);
  const edge = banner.getBoundingClientRect();
  bannerClip.constant = -screenToWorld(edge.left, edge.top).y;
  positionPointer();
}
function setPeekTarget(next) {
  if (next === peekTarget) return;
  peekTarget = next;
  if (next) {
    gestureClock = 0;
    hideDelay = 0;
  } else hideDelay = 0.18;
}
function updatePeek() {
  if (isAuth || !banner) return;
  const current = Math.max(0, window.scrollY),
    delta = current - lastScroll;
  lastScroll = current;
  if (Math.abs(delta) < 0.5) return;
  const direction = Math.sign(delta);
  scrollTravel =
    direction === scrollDirection
      ? scrollTravel + Math.abs(delta)
      : Math.abs(delta);
  scrollDirection = direction;
  const rect = banner.getBoundingClientRect();
  const nearby = rect.top < innerHeight * 0.95 && rect.bottom > 0;
  if (!nearby) setPeekTarget(0);
  else if (scrollTravel >= (direction > 0 ? 14 : 24))
    setPeekTarget(direction > 0 ? 1 : 0);
  if (paused) {
    peekAmount = peekTarget;
    peekVelocity = 0;
    gestureAmount = peekTarget;
    group.position.y = -1.9 * (1 - peekAmount);
    syncBannerGeometry();
    render();
  } else start();
}
if (!isAuth) {
  window.addEventListener("scroll", updatePeek, { passive: true });
  const rect = banner.getBoundingClientRect();
  peekTarget = rect.top < innerHeight * 0.97 && rect.bottom > 0 ? 1 : 0;
  peekAmount = paused ? peekTarget : 0;
  group.position.y = -1.9 * (1 - peekAmount);
}
function render() {
  renderer.render(scene, camera);
}
function tick(now) {
  raf = 0;
  if (!visible || document.hidden || paused) return;
  const dt = last ? Math.min(0.04, (now - last) / 1000) : 0;
  last = now;
  time += dt;
  if (isAuth) {
    if (!dragging) {
      group.rotation.x = THREE.MathUtils.lerp(
        group.rotation.x,
        targetX + Math.sin(time * 0.65) * 0.025,
        0.06,
      );
      group.rotation.y = THREE.MathUtils.lerp(group.rotation.y, targetY, 0.08);
    }
    group.position.y = Math.sin(time * 0.8) * 0.055;
  } else {
    const reading = gestureAmount > 0.55;
    const gx = reading ? -0.75 : mx * 0.65,
      gy = reading ? -0.72 : my * 0.4;
    head.rotation.y = THREE.MathUtils.lerp(head.rotation.y, gx * 0.35, 0.055);
    head.rotation.x = THREE.MathUtils.lerp(head.rotation.x, -gy * 0.22, 0.055);
    pupils.forEach((p) => {
      p.position.x = THREE.MathUtils.lerp(p.position.x, gx * 0.038, 0.1);
      p.position.y = THREE.MathUtils.lerp(p.position.y, gy * 0.028, 0.1);
    });
    const blink = time % 4.1;
    eyes.forEach(
      (e) =>
        (e.scale.y =
          blink < 0.14 ? Math.max(0.08, Math.abs(blink - 0.07) / 0.07) : 1),
    );
    hideDelay = Math.max(0, hideDelay - dt);
    const bodyTarget = peekTarget === 0 && hideDelay > 0 ? 1 : peekTarget;
    // Damped body motion keeps velocity continuous if scroll direction changes.
    peekVelocity += (bodyTarget - peekAmount) * 72 * dt;
    peekVelocity *= Math.exp(-15 * dt);
    peekAmount = THREE.MathUtils.clamp(
      peekAmount + peekVelocity * dt,
      0,
      1.015,
    );
    if (peekTarget && peekAmount > 0.88) gestureClock += dt;
    const cycle = gestureClock % 5.2;
    let wantedGesture = 0;
    if (peekTarget && peekAmount > 0.9) {
      if (cycle > 0.45 && cycle < 1.35)
        wantedGesture = THREE.MathUtils.smootherstep(cycle, 0.45, 1.35);
      else if (cycle >= 1.35 && cycle < 2.75)
        wantedGesture = 0.96 + Math.sin((cycle - 1.35) * 5) * 0.04;
      else if (cycle >= 2.75 && cycle < 3.55)
        wantedGesture = 1 - THREE.MathUtils.smootherstep(cycle, 2.75, 3.55);
    }
    gestureAmount = THREE.MathUtils.damp(
      gestureAmount,
      wantedGesture,
      peekTarget ? 8 : 15,
      dt,
    );
    group.position.y =
      -1.9 * (1 - peekAmount) + Math.sin(time * 1.5) * 0.012 * peekAmount;
    group.position.x = 0.09 * (1 - peekAmount);
    group.rotation.z = -0.045 * (1 - peekAmount) - gestureAmount * 0.022;
    group.rotation.x = 0.13 * (1 - peekAmount) + gestureAmount * 0.035;
    syncBannerGeometry();
  }
  render();
  raf = requestAnimationFrame(tick);
}
function start() {
  if (!raf && visible && !paused && !document.hidden) {
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
  camera.position.z = isAuth ? Math.max(4.6, 6.1 / camera.aspect) : 4.8;
  camera.updateProjectionMatrix();
  syncBannerGeometry();
  render();
}
new ResizeObserver(size).observe(host);
new IntersectionObserver(
  (e) => {
    visible = e[0].isIntersecting;
    visible ? start() : stop();
  },
  { threshold: 0.04 },
).observe(host);
function setPaused(value) {
  paused = value;
  paused ? stop() : start();
  if (!isAuth && paused) {
    peekAmount = peekTarget;
    peekVelocity = 0;
    gestureAmount = peekTarget;
    group.position.y = -1.9 * (1 - peekAmount);
    syncBannerGeometry();
  }
  render();
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
});
renderer.domElement.addEventListener("webglcontextrestored", () => {
  size();
  start();
});
size();
start();
