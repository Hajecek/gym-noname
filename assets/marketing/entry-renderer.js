import * as THREE from './vendor/three.module.js';
// Portable Canvas renderer for the same entrance scene when WebGL is unavailable.
// Near-plane clipping keeps the first-person floor and walls stable.
export class EntryCanvasRenderer {
 constructor(){this.domElement=document.createElement('canvas');this.ctx=this.domElement.getContext('2d');this.ratio=1;this.textures=new WeakMap();this.light=new THREE.Vector3(-.4,.85,1).normalize()}
 setPixelRatio(r){this.ratio=.75}setClearColor(){}
 setSize(w,h){this.w=Math.round(w*this.ratio);this.h=Math.round(h*this.ratio);this.domElement.width=this.w;this.domElement.height=this.h;this.pixels=this.ctx.createImageData(this.w,this.h);this.depth=new Float32Array(this.w*this.h);this.domElement.style.width=w+'px';this.domElement.style.height=h+'px'}
 render(scene,camera){if(!this.w)return;scene.updateMatrixWorld(true);camera.updateMatrixWorld(true);const faces=[],vp=new THREE.Matrix4().multiplyMatrices(camera.projectionMatrix,camera.matrixWorldInverse),planes=[v=>v.z+v.w,v=>v.w-v.z,v=>v.x+v.w,v=>v.w-v.x,v=>v.y+v.w,v=>v.w-v.y];
 scene.traverseVisible(mesh=>{if(!mesh.isMesh)return;const g=mesh.geometry,pos=g.attributes.position,uv=g.attributes.uv,index=g.index,m=mesh.material;if(Array.isArray(m))return;const matrix=new THREE.Matrix4().multiplyMatrices(vp,mesh.matrixWorld),nm=new THREE.Matrix3().getNormalMatrix(mesh.matrixWorld),vertices=[];
 for(let i=0;i<pos.count;i++){const v=new THREE.Vector4(pos.getX(i),pos.getY(i),pos.getZ(i),1).applyMatrix4(matrix);v.u=uv?.getX(i)||0;v.v=uv?.getY(i)||0;vertices.push(v)}
 const count=index?index.count:pos.count;for(let i=0;i<count;i+=3){const ids=index?[index.getX(i),index.getX(i+1),index.getX(i+2)]:[i,i+1,i+2];let poly=ids.map(j=>vertices[j]);
 for(const plane of planes){if(!poly.length)break;const next=[];for(let j=0;j<poly.length;j++){const a=poly[j],b=poly[(j+1)%poly.length],da=plane(a),db=plane(b);if(da>=0)next.push(a);if((da>=0)!==(db>=0)){const k=da/(da-db),v=a.clone().lerp(b,k);v.u=a.u+(b.u-a.u)*k;v.v=a.v+(b.v-a.v)*k;next.push(v)}}poly=next}
 if(poly.length<3)continue;const normal=new THREE.Vector3();for(const id of ids)normal.add(new THREE.Vector3().fromBufferAttribute(g.attributes.normal,id));normal.applyMatrix3(nm).normalize();const light=m.isMeshBasicMaterial?1:.48+.52*Math.max(0,normal.dot(this.light));const c=m.color.clone().multiplyScalar(light).convertLinearToSRGB();const color=`rgb(${Math.round(c.r*255)},${Math.round(c.g*255)},${Math.round(c.b*255)})`;
 const pts=poly.map(v=>({x:(v.x/v.w+1)*this.w/2,y:(1-v.y/v.w)*this.h/2,z:v.z/v.w,q:1/v.w,u:v.u,v:v.v}));for(let j=1;j<pts.length-1;j++){const p=[pts[0],pts[j],pts[j+1]];if((p[1].x-p[0].x)*(p[2].y-p[0].y)-(p[1].y-p[0].y)*(p[2].x-p[0].x)>=0)continue;faces.push({p,z:p.reduce((s,v)=>s+v.z,0)/3,color:[Math.round(c.r*255),Math.round(c.g*255),Math.round(c.b*255)],tex:m.map})}
 }});

 const data=this.pixels.data,depth=this.depth,W=this.w,H=this.h;depth.fill(Infinity);for(let i=0;i<data.length;i+=4){data[i]=23;data[i+1]=33;data[i+2]=30;data[i+3]=255}
 for(const f of faces){const [a,b,c]=f.p;const area=(b.x-a.x)*(c.y-a.y)-(b.y-a.y)*(c.x-a.x);if(Math.abs(area)<.001)continue;
 let texture=null;if(f.tex?.image){texture=this.textures.get(f.tex);if(!texture||texture.version!==f.tex.version){const im=f.tex.image;const pixels=im.getContext('2d').getImageData(0,0,im.width,im.height);texture={data:pixels.data,w:im.width,h:im.height,version:f.tex.version};this.textures.set(f.tex,texture)}}
 const x0=Math.max(0,Math.floor(Math.min(a.x,b.x,c.x))),x1=Math.min(W-1,Math.ceil(Math.max(a.x,b.x,c.x))),y0=Math.max(0,Math.floor(Math.min(a.y,b.y,c.y))),y1=Math.min(H-1,Math.ceil(Math.max(a.y,b.y,c.y)));
 for(let y=y0;y<=y1;y++)for(let x=x0;x<=x1;x++){const px=x+.5,py=y+.5;const wa=((b.x-px)*(c.y-py)-(b.y-py)*(c.x-px))/area,wb=((c.x-px)*(a.y-py)-(c.y-py)*(a.x-px))/area,wc=1-wa-wb;if(wa<-.00001||wb<-.00001||wc<-.00001)continue;const z=wa*a.z+wb*b.z+wc*c.z,k=y*W+x;if(z>=depth[k])continue;depth[k]=z;const out=k*4;
 if(texture){const q=wa*a.q+wb*b.q+wc*c.q,u=(wa*a.u*a.q+wb*b.u*b.q+wc*c.u*c.q)/q,v=(wa*a.v*a.q+wb*b.v*b.q+wc*c.v*c.q)/q;const tx=Math.min(texture.w-1,Math.max(0,Math.floor(u*texture.w))),ty=Math.min(texture.h-1,Math.max(0,Math.floor((1-v)*texture.h))),src=(ty*texture.w+tx)*4;data[out]=texture.data[src];data[out+1]=texture.data[src+1];data[out+2]=texture.data[src+2]}else{data[out]=f.color[0];data[out+1]=f.color[1];data[out+2]=f.color[2]}
 }
 }
 this.ctx.putImageData(this.pixels,0,0);
 }
}
