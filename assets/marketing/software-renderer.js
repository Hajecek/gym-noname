import * as THREE from './vendor/three.module.js';
// Canvas fallback for environments where WebGL is disabled. Uses the same rig and camera.
export class SoftwareRenderer {
 constructor(){this.domElement=document.createElement('canvas');this.ctx=this.domElement.getContext('2d');this.ratio=1;this.software=true;this.light=new THREE.Vector3(-.4,.8,1).normalize()}
 setPixelRatio(r){this.ratio=Math.min(r,1.3)}
 setClearColor(){}
 setSize(w,h){this.w=w;this.h=h;this.domElement.width=w*this.ratio;this.domElement.height=h*this.ratio;this.domElement.style.width=w+'px';this.domElement.style.height=h+'px'}
 render(scene,camera){
 scene.updateMatrixWorld();camera.updateMatrixWorld();const faces=[],p=new THREE.Vector3(),a=new THREE.Vector3(),b=new THREE.Vector3(),n=new THREE.Vector3();
 scene.traverse(mesh=>{if(!mesh.isMesh||!mesh.visible)return;const g=mesh.geometry,pos=g.attributes.position,index=g.index,pts=[],world=[],normals=[],nm=new THREE.Matrix3().getNormalMatrix(mesh.matrixWorld);
 for(let i=0;i<pos.count;i++){p.fromBufferAttribute(pos,i).applyMatrix4(mesh.matrixWorld);world.push(p.clone());normals.push(new THREE.Vector3().fromBufferAttribute(g.attributes.normal,i).applyMatrix3(nm).normalize());p.project(camera);pts.push([(p.x+1)*this.w/2,(1-p.y)*this.h/2,p.z])}
 const count=index?index.count:pos.count;const color=mesh.material.color;
 for(let i=0;i<count;i+=3){const ids=index?[index.getX(i),index.getX(i+1),index.getX(i+2)]:[i,i+1,i+2],v=ids.map(j=>pts[j]);
 if((v[1][0]-v[0][0])*(v[2][1]-v[0][1])-(v[1][1]-v[0][1])*(v[2][0]-v[0][0])>=0)continue;
 a.subVectors(world[ids[1]],world[ids[0]]);b.subVectors(world[ids[2]],world[ids[0]]);n.copy(normals[ids[0]]).add(normals[ids[1]]).add(normals[ids[2]]).normalize();const light=.54+.46*Math.max(0,n.dot(this.light));const c=color.clone().multiplyScalar(light).convertLinearToSRGB();faces.push({v,z:(v[0][2]+v[1][2]+v[2][2])/3,c:`rgb(${Math.round(c.r*255)},${Math.round(c.g*255)},${Math.round(c.b*255)})`});}
 });
 faces.sort((a,b)=>b.z-a.z);const ctx=this.ctx;ctx.setTransform(this.ratio,0,0,this.ratio,0,0);ctx.clearRect(0,0,this.w,this.h);ctx.lineWidth=.45;ctx.lineJoin='round';
 for(const f of faces){ctx.beginPath();ctx.moveTo(f.v[0][0],f.v[0][1]);ctx.lineTo(f.v[1][0],f.v[1][1]);ctx.lineTo(f.v[2][0],f.v[2][1]);ctx.closePath();ctx.fillStyle=ctx.strokeStyle=f.c;ctx.fill();ctx.stroke()}
 }
}
