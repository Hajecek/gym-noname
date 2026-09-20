import * as THREE from './vendor/three.module.js';
import {EntryCanvasRenderer} from './entry-renderer.js';
const $=id=>document.getElementById(id),host=$('phone-stage'),status=$('phone-status'),enter=$('phone-enter');
const reduced=matchMedia('(prefers-reduced-motion: reduce)');
let quiet=reduced.matches||document.body.classList.contains('motion-paused'),visible=false,raf=0,last=0,t=0,age=0,phase='ready',mode='home',progress=0,doorAmount=0,raise=0,mx=0,my=0,day=0,slot=2,reserved=false;
let renderer;try{renderer=new THREE.WebGLRenderer({antialias:true,alpha:false,powerPreference:'low-power'})}catch{renderer=new EntryCanvasRenderer()}
renderer.setPixelRatio(Math.min(devicePixelRatio,1.6));renderer.setClearColor(0x17211e,1);renderer.outputColorSpace=THREE.SRGBColorSpace;renderer.toneMapping=THREE.ACESFilmicToneMapping;renderer.toneMappingExposure=1.05;host.appendChild(renderer.domElement);
const scene=new THREE.Scene();scene.background=new THREE.Color(0x17211e);scene.fog=new THREE.Fog(0x17211e,12,27);
const camera=new THREE.PerspectiveCamera(48,1,.1,40);scene.add(camera);
scene.add(new THREE.HemisphereLight(0xf0f4df,0x23372d,2.2));
for(const [color,power,x,y,z] of [[0xf5f4e4,5,-3,4,5],[0xc6f21a,2,3,3,-2],[0x9fc0d0,2,0,4,-5]]){const light=new THREE.DirectionalLight(color,power);light.position.set(x,y,z);scene.add(light)}
const mat=(c,r=.65,m=.05)=>new THREE.MeshStandardMaterial({color:c,roughness:r,metalness:m});
const wall=mat(0x354139),edge=mat(0x121c17,.4,.3),floor=mat(0x4c554d),lime=mat(0xc6f21a,.4),metal=mat(0x53615a,.32,.65),rubber=mat(0x17221d),skin=mat(0xd6a780),sleeve=mat(0x2b3930);
function box(parent,m,w,h,d,x,y,z){const mesh=new THREE.Mesh(new THREE.BoxGeometry(w,h,d),m);mesh.position.set(x,y,z);parent.add(mesh);return mesh}
function cylinder(parent,m,r,len,x,y,z,axis='y'){const mesh=new THREE.Mesh(new THREE.CylinderGeometry(r,r,len,20),m);mesh.position.set(x,y,z);if(axis==='x')mesh.rotation.z=Math.PI/2;if(axis==='z')mesh.rotation.x=Math.PI/2;parent.add(mesh);return mesh}
function label(text,w,h,bg='#17231d',fg='#e8f0e4',size=70){const c=document.createElement('canvas');c.width=1024;c.height=Math.round(1024*h/w);const ctx=c.getContext('2d');ctx.fillStyle=bg;ctx.fillRect(0,0,c.width,c.height);ctx.fillStyle=fg;ctx.font=`700 ${size}px Figtree, sans-serif`;ctx.textAlign='center';ctx.textBaseline='middle';ctx.fillText(text,512,c.height/2);const tex=new THREE.CanvasTexture(c);tex.colorSpace=THREE.SRGBColorSpace;const mesh=new THREE.Mesh(new THREE.PlaneGeometry(w,h),new THREE.MeshBasicMaterial({map:tex}));return mesh}
// Architectural shell; the doorway is a real opening, with a furnished room behind it.
box(scene,floor,16,.15,20,0,-.10,-2);box(scene,wall,5.7,5,.3,-4.15,2.5,0);box(scene,wall,5.7,5,.3,4.15,2.5,0);box(scene,wall,2.6,1.5,.3,0,4.25,0);
box(scene,edge,.13,3.54,.44,-1.35,1.76,.06);box(scene,edge,.13,3.54,.44,1.35,1.76,.06);box(scene,edge,2.82,.13,.44,0,3.52,.06);
box(scene,mat(0x879477),2.55,.025,.48,0,.005,.08);
for(const x of[-1.45,1.45])box(scene,lime,.023,3.6,.035,x,1.80,.23);
const title=label('privo fit',2.3,.43,'#354139','#e8f0e4',140);title.position.set(0,4.02,.17);scene.add(title);
const sideLabel=label('01 / TVŮJ PROSTOR',1.4,.22,'#354139','#b9c8af',74);sideLabel.position.set(2.35,2.85,.17);scene.add(sideLabel);
const reader=box(scene,edge,.23,.42,.12,1.74,1.5,.22),readerLight=box(scene,lime,.11,.035,.01,1.74,1.61,.289);
// Door hinge on the left: opens inward into the gym.
const hinge=new THREE.Group();hinge.name='entry-door-hinge';hinge.position.set(-1.29,0,0);scene.add(hinge);
const doorMat=mat(0x203128,.37,.25);box(hinge,doorMat,2.54,3.43,.12,1.27,1.73,.02);
box(hinge,metal,2.35,.018,.025,1.27,.18,.095);box(hinge,metal,.02,3.2,.025,.15,1.74,.095);
const doorSign=label('TVŮJ PROSTOR.',1.65,.25,'#203128','#e8f0e4',90);doorSign.position.set(1.27,2.27,.089);hinge.add(doorSign);
const doorSmall=label('TVOJE TEMPO. TVOJE PRAVIDLA.',1.60,.15,'#203128','#a8ba9a',48);doorSmall.position.set(1.27,1.98,.090);hinge.add(doorSmall);
cylinder(hinge,metal,.034,.53,2.24,1.47,.20);cylinder(hinge,metal,.028,.15,2.24,1.70,.13,'z');cylinder(hinge,metal,.028,.15,2.24,1.24,.13,'z');
// Room: floor seams, ceiling light rails, squat rack, bench and dumbbells.
box(scene,mat(0x26372e),9,4.6,.16,0,2.25,-7);box(scene,mat(0x445046),.15,4.6,7,-4.5,2.25,-3.5);box(scene,mat(0x445046),.15,4.6,7,4.5,2.25,-3.5);
for(let z=-8;z<6;z+=1.15)box(scene,edge,12,.008,.016,0,-.017,z);
for(let x=-6;x<=6;x+=1.15)box(scene,edge,.016,.008,16,x,-.016,-2);
box(scene,rubber,5,.025,4,0,.015,-3.7);
for(const x of[-2.7,2.7]){box(scene,lime,.055,.035,6,x,3.85,-3.5);box(scene,metal,.11,.09,6,x,3.93,-3.5)}
const backSign=label('MAKE ROOM FOR YOU.',5,.6,'#26372e','#c6f21a',85);backSign.position.set(0,3.0,-6.90);scene.add(backSign);
for(const x of[-1.05,1.05]){box(scene,metal,.11,2.65,.13,x,1.35,-4.6);box(scene,metal,.11,2.65,.13,x,1.35,-5.6);box(scene,metal,.16,.10,1.45,x,.10,-5.1)}
box(scene,metal,2.25,.1,.10,0,2.65,-4.6);cylinder(scene,metal,.035,2.9,0,1.85,-4.5,'x');
for(const x of[-1.25,-1.05,1.05,1.25])cylinder(scene,rubber,.30,.12,x,1.85,-4.5,'x');
box(scene,rubber,.7,.19,1.65,.15,.62,-3.3);for(const z of[-3.9,-2.75])box(scene,metal,.13,.57,.15,.15,.28,z);
for(let i=0;i<4;i++){const z=-2-i*.75;box(scene,metal,.8,.09,.17,3,.9,z);cylinder(scene,metal,.025,.55,3,1.03,z,'x');for(const x of[2.76,3.24])cylinder(scene,rubber,.14,.14,x,1.03,z,'x')}
// Phone and hand belong to the viewer's camera: the arm rises into the first-person view.
const handRig=new THREE.Group();handRig.name='entry-hand';camera.add(handRig);
const handset=new THREE.Group();handRig.add(handset);handset.rotation.set(-.055,-.13,-.09);
function rounded(w,h,r){const s=new THREE.Shape();s.moveTo(-w/2+r,-h/2);s.lineTo(w/2-r,-h/2);s.quadraticCurveTo(w/2,-h/2,w/2,-h/2+r);s.lineTo(w/2,h/2-r);s.quadraticCurveTo(w/2,h/2,w/2-r,h/2);s.lineTo(-w/2+r,h/2);s.quadraticCurveTo(-w/2,h/2,-w/2,h/2-r);s.lineTo(-w/2,-h/2+r);s.quadraticCurveTo(-w/2,-h/2,-w/2+r,-h/2);return s}
const phoneBody=new THREE.Mesh(new THREE.ExtrudeGeometry(rounded(.87,1.72,.12),{depth:.065,bevelEnabled:true,bevelThickness:.018,bevelSize:.018,bevelSegments:3,steps:1,curveSegments:10}),metal);handset.add(phoneBody);
const screenCanvas=document.createElement('canvas');screenCanvas.width=600;screenCanvas.height=1200;const ctx=screenCanvas.getContext('2d'),texture=new THREE.CanvasTexture(screenCanvas);texture.colorSpace=THREE.SRGBColorSpace;
const screen=new THREE.Mesh(new THREE.ShapeGeometry(rounded(.80,1.64,.095),16),new THREE.MeshBasicMaterial({map:texture}));
// ShapeGeometry UVs are in world coordinates; normalize them to the phone display.
const uv=screen.geometry.attributes.uv;for(let i=0;i<uv.count;i++)uv.setXY(i,(uv.getX(i)+.4)/.8,(uv.getY(i)+.82)/1.64);screen.position.z=.087;handset.add(screen);
const notch=new THREE.Mesh(new THREE.ShapeGeometry(rounded(.22,.055,.0275),16),new THREE.MeshBasicMaterial({color:0x080d0a}));notch.position.set(0,.751,.099);handset.add(notch);box(handset,metal,.025,.19,.025,.451,.35,.035);
// A continuous right hand: palm behind the phone, four fingers around its
// right edge, thumb resting on the left rim, wrist flowing into the forearm.
const grip=new THREE.Group();grip.name='phone-grip';handset.add(grip);
const jointGeometry=new THREE.SphereGeometry(1,24,16);
function oval(parent,m,p,scale){const mesh=new THREE.Mesh(jointGeometry,m);mesh.position.set(...p);mesh.scale.set(...scale);parent.add(mesh);return mesh}
function limb(parent,m,a,b,ra,rb){const start=new THREE.Vector3(...a),end=new THREE.Vector3(...b),delta=end.clone().sub(start);const mesh=new THREE.Mesh(new THREE.CylinderGeometry(rb,ra,delta.length(),20,1),m);mesh.position.copy(start).add(end).multiplyScalar(.5);mesh.quaternion.setFromUnitVectors(new THREE.Vector3(0,1,0),delta.normalize());parent.add(mesh);oval(parent,m,a,[ra,ra,ra]);oval(parent,m,b,[rb,rb,rb]);return mesh}
// Keep the complete palm behind the phone's back plane. Only fingertips
// and the thumb cross around the side rails; no skin intersects the display.
const palm=oval(grip,skin,[.05,-.55,-.24],[.33,.34,.135]);palm.name='holding-palm';
function taperedArm(){const curve=new THREE.CatmullRomCurve3([new THREE.Vector3(.055,-.75,-.24),new THREE.Vector3(.13,-1.00,-.25),new THREE.Vector3(.28,-1.4,-.23),new THREE.Vector3(.48,-1.95,-.22)]);const g=new THREE.TubeGeometry(curve,32,1,24,false),pos=g.attributes.position;
 for(let i=0;i<=32;i++){const u=i/32,center=curve.getPointAt(u),radius=.148+.080*u;for(let j=0;j<=24;j++){const k=i*25+j;pos.setXYZ(k,center.x+(pos.getX(k)-center.x)*radius,center.y+(pos.getY(k)-center.y)*radius,center.z+(pos.getZ(k)-center.z)*radius)}}g.computeVertexNormals();const mesh=new THREE.Mesh(g,skin);mesh.name='holding-forearm';grip.add(mesh)}
taperedArm();limb(grip,sleeve,[.45,-1.88,-.22],[.62,-2.3,-.22],.24,.27);
const nail=mat(0xe7bd9d,.58);
for(let i=0;i<4;i++){
 const y=-.18-i*.145,r=i===3?.044:.052;
 const points=[[.25,y-.03,-.24],[.435,y,-.16],[.49,y,-.035],[.45,y-.015,.112],[.37,y-.027,.142]];
 for(let j=0;j<points.length-1;j++)limb(grip,skin,points[j],points[j+1],r,r*(j===3?.9:1));
 oval(grip,nail,[points[4][0]+.007,points[4][1],.184],[.032,.025,.006]);
}
oval(grip,skin,[-.18,-.64,-.25],[.16,.22,.13]);
// Thumb travels outside the left frame before resting along its edge.
const thumbCurve=new THREE.CatmullRomCurve3([new THREE.Vector3(-.18,-.75,-.24),new THREE.Vector3(-.40,-.66,-.20),new THREE.Vector3(-.49,-.53,-.035),new THREE.Vector3(-.42,-.38,.16)]);
const thumb=new THREE.Mesh(new THREE.TubeGeometry(thumbCurve,24,.071,16,false),skin);grip.add(thumb);oval(grip,skin,[-.42,-.38,.16],[.07,.073,.07]);
const thumbnail=oval(grip,nail,[-.425,-.376,.225],[.043,.052,.007]);thumbnail.rotation.z=-.10;
const lids=document.createElement('div');lids.className='entry-eyelids';lids.setAttribute('aria-hidden','true');lids.innerHTML='<span></span><span></span>';host.appendChild(lids);
let nextBlink=3.8,blinkTimer=0;
function blink(){if(quiet||!visible||document.hidden)return;lids.classList.add('is-blinking');clearTimeout(blinkTimer);blinkTimer=setTimeout(()=>lids.classList.remove('is-blinking'),340);nextBlink=t+5.2+Math.random()*3.0}
function text(s,y,size=36,color='#101714',weight=600){ctx.fillStyle=color;ctx.font=`${weight} ${size}px Figtree, sans-serif`;ctx.textAlign='center';ctx.fillText(s,300,y)}
function circleIcon(open){ctx.strokeStyle='#101714';ctx.lineWidth=10;ctx.lineCap='round';ctx.beginPath();ctx.roundRect(246,416,108,88,16);ctx.stroke();ctx.beginPath();ctx.arc(open?320:300,411,36,Math.PI,0);ctx.stroke()}
let shownMinute='';
function localClock(){const date=new Date();return String(date.getHours()).padStart(2,'0')+':'+String(date.getMinutes()).padStart(2,'0')}
function paintStatusBar(){shownMinute=localClock();ctx.fillStyle='#101714';ctx.font='700 28px Figtree, sans-serif';ctx.textAlign='left';ctx.fillText(shownMinute,45,62);
 // Decorative signal/Wi-Fi/battery icons; no device permissions required.
 for(let i=0;i<4;i++){ctx.beginPath();ctx.roundRect(417+i*10,61-(i+1)*5,6,(i+1)*5,2);ctx.fill()}
 ctx.strokeStyle='#101714';ctx.lineWidth=4;ctx.lineCap='round';for(const r of[10,18]){ctx.beginPath();ctx.arc(478,58,r,-Math.PI*.78,-Math.PI*.22);ctx.stroke()}ctx.beginPath();ctx.arc(478,59,2.5,0,Math.PI*2);ctx.fill();
 ctx.lineWidth=2.5;ctx.beginPath();ctx.roundRect(509,40,42,21,5);ctx.stroke();ctx.fillRect(554,46,3,9);ctx.beginPath();ctx.roundRect(513,44,31,13,2);ctx.fill();
}
function paint(){ctx.fillStyle=mode==='home'?'#C6F21A':'#E8F0E4';ctx.fillRect(0,0,600,1200);paintStatusBar();text('privo fit',174,48,'#101714',800);text('TVŮJ SOUKROMÝ PROSTOR',220,18,'#46532c',600);
 if(mode==='home'){
 circleIcon(phase==='open');
 if(phase==='unlocking'){text('Otevíráme',625,61,'#101714',800);text('tvůj prostor.',695,53,'#101714',800);text(Math.floor(progress*100)+' %',805,30);ctx.fillStyle='#10171430';ctx.fillRect(95,854,410,7);ctx.fillStyle='#101714';ctx.fillRect(95,854,410*progress,7)}
 else if(phase==='open'){text('Vítej',625,72,'#101714',800);text('ve svém.',705,66,'#101714',800);text('DVEŘE JSOU OTEVŘENÉ',814,22);text('↺  Zkusit znovu',1000,30)}
 else{text('Otevřít',625,74,'#101714',800);text('dveře',708,82,'#101714',800);text('KLEPNI KAMKOLIV',811,22);text('Tvůj čas začíná tady.',1010,26)}
 }else if(mode==='profile'){text('Ahoj, Alexi.',400,54,'#101714',800);text('TVŮJ ČLENSKÝ PROFIL',464,22);text('PRIVOFIT / MEMBER',625,29);text('Prostor. Jen pro tebe.',705,28);text('Ukázkový profil',820,23);text('Otevřít dveře  ↗',1010,35)}
 else{text(reserved?'Máš vybráno.':'Tvůj další trénink',380,43,'#101714',800);text(day?'Zítra':'Dnes',530,40);text(['10:00','14:00','17:00','19:00'][slot],650,80,'#101714',800);text('60 MIN / JEN TVŮJ PROSTOR',725,20);text(reserved?'Ukázkový termín potvrzen':'Vyber čas pod scénou',850,26);text(reserved?'Otevřít dveře  ↗':'Potvrdit termín  ↗',1020,33)}
 ctx.fillStyle='#101714';ctx.beginPath();ctx.roundRect(215,1156,170,7,4);ctx.fill();texture.needsUpdate=true;
}
function sync(){for(const [id,m]of[['phone-home','home'],['phone-profile','profile'],['phone-reserve','reservation']])$(id).setAttribute('aria-pressed',String(mode===m));$('phone-booking-controls').hidden=mode!=='reservation';enter.disabled=phase==='unlocking';enter.textContent=mode==='reservation'&&!reserved?'Potvrdit ukázkový termín':phase==='unlocking'?'Otevírám…':phase==='open'?'Zkusit znovu ↺':'Otevřít dveře ↗';$('entry-scene-state').textContent=phase==='unlocking'?'ODEMYKÁNÍ':phase==='open'?'PROSTOR JE TVŮJ':'PŘED TVÝM PROSTOREM';host.dataset.phase=phase;paint()}
function reset(){phase='ready';mode='home';progress=0;age=0;doorAmount=0;raise=quiet?1:0;nextBlink=t+3.8;lids.classList.remove('is-blinking');status.textContent='Klepni na zelený displej. Tvůj prostor čeká.';sync();draw();start()}
function unlock(){if(phase==='unlocking')return;if(phase==='open'){reset();return}mode='home';phase='unlocking';age=0;progress=0;status.textContent='Ověřujeme ukázkový vstup…';sync();start()}
function activate(){if(mode==='reservation'&&!reserved){reserved=true;status.textContent='Ukázkový termín vybraný. Skutečná rezervace nevznikla.';sync();draw()}else unlock()}
function choose(m){if(phase==='unlocking')return;mode=m;status.textContent=m==='home'?'Klepni na zelený displej. Tvůj prostor čeká.':m==='profile'?'Ukázkový členský profil. Klepnutím na telefon otevřeš vstup.':'Vyber ukázkový den a čas pod scénou.';sync();draw()}
$('phone-home').addEventListener('click',()=>choose('home'));$('phone-profile').addEventListener('click',()=>choose('profile'));$('phone-reserve').addEventListener('click',()=>choose('reservation'));enter.addEventListener('click',activate);$('entry-replay').addEventListener('click',reset);
$('phone-day').addEventListener('change',e=>{day=+e.target.value;reserved=false;sync();draw()});$('phone-time').addEventListener('change',e=>{slot=+e.target.value;reserved=false;sync();draw()});
const ray=new THREE.Raycaster(),pointer=new THREE.Vector2();let down=null;
host.addEventListener('pointerdown',e=>{down={x:e.clientX,y:e.clientY}});host.addEventListener('pointermove',e=>{const r=host.getBoundingClientRect();mx=THREE.MathUtils.clamp((e.clientX-r.left)/r.width*2-1,-1,1);my=THREE.MathUtils.clamp((e.clientY-r.top)/r.height*2-1,-1,1);if(quiet)draw()},{passive:true});host.addEventListener('pointerleave',()=>{mx=0;my=0;down=null});host.addEventListener('pointerup',e=>{if(!down||Math.hypot(e.clientX-down.x,e.clientY-down.y)>12)return;down=null;const r=host.getBoundingClientRect();pointer.set((e.clientX-r.left)/r.width*2-1,1-(e.clientY-r.top)/r.height*2);scene.updateMatrixWorld(true);camera.updateMatrixWorld(true);ray.setFromCamera(pointer,camera);if(ray.intersectObject(screen).length)activate()});
host.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();activate()}else if(e.key==='ArrowLeft'||e.key==='ArrowRight'){e.preventDefault();mx=THREE.MathUtils.clamp(mx+(e.key==='ArrowLeft'?-.3:.3),-1,1);draw()}});
function draw(){if(localClock()!==shownMinute)paint();const portrait=camera.aspect<.9;const distance=portrait?7.8:6.2;camera.position.set(mx*(quiet?0:.12),1.72,distance-doorAmount*.40);camera.lookAt(-.06+mx*.17,1.76-my*.09,-1.5);hinge.rotation.y=doorAmount*1.48;readerLight.material=phase==='ready'?metal:lime;
 const tan=Math.tan(THREE.MathUtils.degToRad(camera.fov/2)),span=tan*2.6*camera.aspect;const scale=portrait?.70:.90;
 handRig.scale.setScalar(scale);handRig.position.set(portrait?span*.30:Math.min(span*.48,1.10),.08-(1-raise)*1.9-(phase==='open'?.1:0),-2.6);handRig.rotation.z=quiet?0:Math.sin(t*.8)*.008;handset.rotation.x=-.055+(1-raise)*.55;renderer.render(scene,camera)}
let lastPaint=-1;
function tick(now){raf=0;if(!visible||document.hidden)return;const dt=last?Math.min((now-last)/1000,.5):0;last=now;t+=dt;age+=dt;if(!quiet&&t>=nextBlink&&raise>.98)blink();
 raise=quiet?1:THREE.MathUtils.damp(raise,1,3.6,dt);
 if(phase==='unlocking'){progress=Math.min(age/1.8,1);if(Math.floor(progress*30)!==lastPaint){lastPaint=Math.floor(progress*30);paint()}if(progress>=1){phase='open';age=0;status.textContent='Dveře jsou otevřené. Vítej ve svém prostoru.';sync()}}
 doorAmount=quiet?(phase==='open'?1:0):THREE.MathUtils.damp(doorAmount,phase==='open'?1:0,2.6,dt);draw();if(!quiet||phase==='unlocking')raf=requestAnimationFrame(tick)}
function start(){if(!raf&&visible&&!document.hidden){last=0;raf=requestAnimationFrame(tick)}}function stop(){cancelAnimationFrame(raf);raf=0;last=0;clearTimeout(blinkTimer);lids.classList.remove('is-blinking');nextBlink=t+3.8}
function size(){if(!host.clientWidth||!host.clientHeight)return;renderer.setSize(host.clientWidth,host.clientHeight);camera.aspect=host.clientWidth/host.clientHeight;camera.updateProjectionMatrix();draw()}
new ResizeObserver(size).observe(host);new IntersectionObserver(entries=>{visible=entries[0].isIntersecting;if(visible)start();else stop()},{threshold:.08}).observe(host);
function motion(v){quiet=v;if(quiet)raise=1;stop();draw();start()}reduced.addEventListener('change',e=>motion(e.matches));window.addEventListener('privofit-motion',e=>motion(e.detail.paused));document.addEventListener('visibilitychange',()=>document.hidden?stop():start());window.addEventListener('pagehide',stop);window.addEventListener('pageshow',()=>{size();start()});renderer.domElement.addEventListener('webglcontextlost',e=>{e.preventDefault();stop();status.textContent='3D zobrazení bylo pozastaveno. Obnov stránku a zkus to znovu.'});renderer.domElement.addEventListener('webglcontextrestored',()=>{size();start()});
const clockTimer=setInterval(()=>{if(visible&&!document.hidden&&localClock()!==shownMinute){paint();if(quiet)draw()}},1000);
document.fonts?.ready.then(()=>{paint();draw()});sync();size();$('phone-loading').hidden=true;
