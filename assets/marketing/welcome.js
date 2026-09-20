import * as THREE from './vendor/three.module.js';
import {SoftwareRenderer} from './software-renderer.js';
const host=document.getElementById('auth-3d');
const reduced=matchMedia('(prefers-reduced-motion: reduce)');
let paused=reduced.matches,visible=true,frame=0,last=0,time=0,waveStart=0,waveAmount=0,mx=0,my=0,passwordFocus=false;
let renderer;try{renderer=new THREE.WebGLRenderer({alpha:true,antialias:true,powerPreference:'low-power'})}catch{renderer=new SoftwareRenderer()}
renderer.setPixelRatio(Math.min(devicePixelRatio,1.7));renderer.setClearColor(0,0);renderer.outputColorSpace=THREE.SRGBColorSpace;renderer.toneMapping=THREE.ACESFilmicToneMapping;renderer.toneMappingExposure=1.05;host.appendChild(renderer.domElement);
const scene=new THREE.Scene(),camera=new THREE.PerspectiveCamera(32,1,.1,30);
scene.add(new THREE.HemisphereLight(0xfff9ee,0x263427,2.25));
for(const [color,power,p]of[[0xffffff,3.4,[-3,5,5]],[0xc6f21a,1.8,[3,3,-3]],[0xbad2ee,1.1,[4,1,3]]]){const l=new THREE.DirectionalLight(color,power);l.position.set(...p);scene.add(l)}
const character=new THREE.Group();character.name='welcome-companion';scene.add(character);
const material=(color,roughness=.62,metalness=.03)=>new THREE.MeshStandardMaterial({color,roughness,metalness});
const skin=material(0xd9aa83),shirt=material(0xc6f21a,.5),shorts=material(0x27342c),hair=material(0x282620),white=material(0xf3f1df),black=material(0x182019),sole=material(0xdce5d2);
const sphere=new THREE.SphereGeometry(1,24,16);
function oval(parent,mat,x,y,z,sx,sy,sz){const m=new THREE.Mesh(sphere,mat);m.position.set(x,y,z);m.scale.set(sx,sy,sz);parent.add(m);return m}
function capsule(parent,mat,r,length,x,y,z){const m=new THREE.Mesh(new THREE.CapsuleGeometry(r,length,6,14),mat);m.position.set(x,y,z);parent.add(m);return m}
// One continuous silhouette: athletic torso, shorts, legs and sneakers.
const torso=new THREE.Group();torso.position.y=1.55;character.add(torso);
const profile=[[0,.29],[.12,.35],[.35,.34],[.60,.42],[.80,.48],[.92,.43],[1.00,.20]];
const body=new THREE.Mesh(new THREE.LatheGeometry(profile.map(([y,r])=>new THREE.Vector2(r,y)),48),shirt);body.scale.z=.64;torso.add(body);
oval(torso,skin,0,1.04,0,.135,.20,.13);
oval(character,shorts,0,1.53,0,.35,.25,.25);
for(const side of[-1,1]){
 const x=side*.23;
 capsule(character,shorts,.185,.35,x,1.22,0);
 capsule(character,skin,.12,.57,x,.66,.018);
 capsule(character,white,.128,.15,x,.32,.022);
 oval(character,black,x,.15,.095,.17,.135,.30);
 oval(character,sole,x,.063,.105,.18,.054,.31);
 oval(character,shirt,x+side*.13,.155,.10,.03,.035,.125);
 for(let i=0;i<3;i++){const lace=capsule(character,white,.011,.14,x,.25-i*.012,.09+i*.055);lace.rotation.z=Math.PI/2}
}
const mark=new THREE.Mesh(new THREE.BoxGeometry(.075,.20,.018),black);mark.position.set(-.15,.68,.291);torso.add(mark);const markTop=new THREE.Mesh(new THREE.BoxGeometry(.15,.06,.018),black);markTop.position.set(-.107,.745,.291);torso.add(markTop);
// Connected shoulder → elbow → wrist chains keep the wave intact at every pose.
const arms=[];
for(const side of[-1,1]){
 const shoulder=new THREE.Group();shoulder.position.set(side*.47,2.40,0);character.add(shoulder);
 oval(shoulder,shirt,0,-.06,0,.17,.21,.19);
 capsule(shoulder,skin,.115,.37,0,-.29,.004);
 const elbow=new THREE.Group();elbow.position.y=-.56;shoulder.add(elbow);
 oval(elbow,skin,0,0,0,.12,.12,.12);
 capsule(elbow,skin,.102,.35,0,-.245,.01);
 const wrist=new THREE.Group();wrist.position.set(0,-.51,.01);elbow.add(wrist);
 oval(wrist,skin,0,-.08,0,.12,.15,.075);
 const lengths=[.115,.15,.14,.095];
 for(let i=0;i<4;i++){const finger=capsule(wrist,skin,.027,lengths[i],-.085+i*.057,-.21-lengths[i]/2,.006);finger.rotation.z=(i-1.5)*.04;}
 const thumb=capsule(wrist,skin,.034,.105,side*.135,-.095,.02);thumb.rotation.z=-side*.6;
 arms.push({side,shoulder,elbow,wrist});
}
const head=new THREE.Group();head.name='welcome-head';head.position.set(0,2.93,.025);character.add(head);
oval(head,skin,0,.025,0,.31,.36,.275);oval(head,skin,0,-.145,.02,.255,.20,.24);
for(const side of[-1,1])oval(head,skin,side*.30,-.01,0,.062,.10,.066);
const cap=new THREE.Mesh(new THREE.SphereGeometry(1,36,24,0,Math.PI*2,0,Math.PI*.53),hair);cap.position.set(0,.09,-.025);cap.scale.set(.319,.345,.282);head.add(cap);
const quiff=oval(head,hair,-.055,.31,.12,.26,.12,.18);quiff.rotation.z=-.16;
const eyes=[],pupils=[];
for(const side of[-1,1]){
 const eye=new THREE.Group();eye.position.set(side*.108,.04,.255);head.add(eye);
 oval(eye,white,0,0,0,.066,.077,.027);
 const pupil=oval(eye,black,0,0,.025,.032,.044,.014);pupils.push(pupil);eyes.push(eye);
 oval(eye,white,-.009,.018,.038,.010,.012,.004);
 const brow=capsule(head,hair,.014,.087,side*.11,.163,.25);brow.rotation.z=Math.PI/2+side*.09;
}
oval(head,skin,0,-.052,.288,.052,.067,.047);
const smile=new THREE.Mesh(new THREE.TorusGeometry(.075,.009,8,32,Math.PI),black);smile.rotation.z=Math.PI;smile.position.set(0,-.132,.265);head.add(smile);
const base=new THREE.Mesh(new THREE.CylinderGeometry(1.05,1.08,.025,64),material(0x192419,.9));base.position.y=-.016;scene.add(base);
const circle=new THREE.Mesh(new THREE.TorusGeometry(1.05,.007,8,64),shirt);circle.rotation.x=Math.PI/2;circle.position.y=.001;scene.add(circle);
const waving=arms[1];waving.wrist.name='waving-hand';
function pose(dt=0){
 const cycle=(time-waveStart)%8;
 const wanted=paused?1:cycle<.7?THREE.MathUtils.smootherstep(cycle,0,.7):cycle<3.2?1:cycle<4?1-THREE.MathUtils.smootherstep(cycle,3.2,4):0;
 waveAmount=paused?1:THREE.MathUtils.damp(waveAmount,wanted,10,dt);
 const sway=paused?0:Math.sin(time*1.3)*.01;
 arms[0].shoulder.rotation.z=-.10+sway;arms[0].elbow.rotation.x=-.10;
 waving.shoulder.rotation.z=THREE.MathUtils.lerp(.10,1.72,waveAmount);
 waving.shoulder.rotation.x=THREE.MathUtils.lerp(0,-.12,waveAmount);
 waving.elbow.rotation.z=THREE.MathUtils.lerp(.07,1.40,waveAmount)+(paused?0:Math.sin(cycle*7)*.09*waveAmount);
 waving.wrist.rotation.z=(paused?0:Math.sin(cycle*7)*.28)*waveAmount;
 const yaw=passwordFocus?.64:mx*.18;
 head.rotation.y=paused?yaw:THREE.MathUtils.damp(head.rotation.y,yaw,5,dt);
 head.rotation.x=paused?0:THREE.MathUtils.damp(head.rotation.x,-my*.10,5,dt);
 const blink=time%4.4;eyes.forEach(e=>e.scale.y=passwordFocus?.18:paused?1:blink<.14?Math.max(.08,Math.abs(blink-.07)/.07):1);
 pupils.forEach(p=>{p.position.x=passwordFocus?.015:mx*.02;p.position.y=my*.015});
 torso.scale.x=1+(paused?0:Math.sin(time*1.6)*.006);
 character.rotation.y=-.05+(paused?0:Math.sin(time*.5)*.012);
}
function render(){renderer.render(scene,camera)}
function tick(now){frame=0;if(!visible||document.hidden||paused)return;const dt=last?Math.min(.1,(now-last)/1000):0;last=now;time+=dt;pose(dt);render();frame=requestAnimationFrame(tick)}
function start(){if(!frame&&visible&&!document.hidden&&!paused){last=0;frame=requestAnimationFrame(tick)}}
function stop(){cancelAnimationFrame(frame);frame=0;last=0}
function wave(){waveStart=time;if(paused){pose();render()}else start()}
host.addEventListener('click',wave);host.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();wave()}});
window.addEventListener('pointermove',e=>{const r=host.getBoundingClientRect();mx=THREE.MathUtils.clamp((e.clientX-r.left)/r.width*2-1,-1,1);my=THREE.MathUtils.clamp(1-(e.clientY-r.top)/r.height*2,-1,1)},{passive:true});
const password=document.getElementById('password');
if(password){password.addEventListener('focus',()=>{passwordFocus=true;if(paused){pose();render()}});password.addEventListener('blur',()=>{passwordFocus=false;if(paused){pose();render()}});}
function size(){const w=host.clientWidth,h=host.clientHeight;if(!w||!h)return;renderer.setSize(w,h);camera.aspect=w/h;
 // Fixed envelope includes the full stance, raised fingers and comfortable edge space.
 const halfHeight=1.88,halfWidth=1.9,tan=Math.tan(THREE.MathUtils.degToRad(camera.fov/2));
 const distance=Math.max(halfHeight/tan,halfWidth/(tan*camera.aspect))+1.0;
 camera.position.set(.13,1.70,distance);camera.lookAt(.13,1.70,0);camera.updateProjectionMatrix();pose();render();}
new ResizeObserver(size).observe(host);new IntersectionObserver(e=>{visible=e[0].isIntersecting;visible?start():stop()},{threshold:.04}).observe(host);
function setPaused(value){paused=value;paused?stop():start();pose();render()}reduced.addEventListener('change',e=>setPaused(e.matches));window.addEventListener('privofit-motion',e=>setPaused(e.detail.paused));document.addEventListener('visibilitychange',()=>document.hidden?stop():start());window.addEventListener('pagehide',stop);window.addEventListener('pageshow',()=>{size();start()});renderer.domElement.addEventListener('webglcontextlost',e=>{e.preventDefault();stop();document.getElementById('auth-3d-loading').hidden=false});renderer.domElement.addEventListener('webglcontextrestored',()=>{document.getElementById('auth-3d-loading').hidden=true;size();start()});size();document.getElementById('auth-3d-loading').hidden=true;start();
