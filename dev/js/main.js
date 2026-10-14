try{
(()=>{
const reduce=false;/* site ambience stays on; large motions are calmed in CSS */

/* starfield */
const c=document.getElementById('stars'),x=c.getContext('2d');let W,H,S=[],mx=0,my=0,sy=0;window.boost=0;
function size(){const d=Math.min(devicePixelRatio||1,2);W=innerWidth;H=innerHeight;c.width=W*d;c.height=H*d;x.setTransform(d,0,0,d,0,0);
  const n=Math.round(W*H/5200);S=[];for(let i=0;i<n;i++)S.push({x:Math.random()*W,y:Math.random()*H,r:Math.random()*1.3+.2,z:Math.random()*.9+.1,p:Math.random()*6.28,s:Math.random()*.02+.004,h:Math.random()<.18});}
function draw(t){x.clearRect(0,0,W,H);
  for(const s of S){const a=Math.min(1,(.35+.65*Math.abs(Math.sin(s.p+t*s.s*.06)))*(1+boost*.6+(window.bbE||0)*1.6));
    let px=(s.x+mx*s.z*14)%W,py=((s.y-sy*s.z*.15+mx*0)%H+H)%H;py+=my*s.z*10;
    x.globalAlpha=a;x.fillStyle=s.h?'#c9a4ff':'#ffffff';x.beginPath();x.arc(px,py,s.r*s.z+.2,0,6.28);x.fill();
    if(s.r>1.25&&a>.85){x.globalAlpha=a*.5;x.fillRect(px-4,py-.3,8,.6);x.fillRect(px-.3,py-4,.6,8);}}
  x.globalAlpha=1;if(!reduce)requestAnimationFrame(draw);}
size();addEventListener('resize',size);requestAnimationFrame(draw);
addEventListener('mousemove',e=>{mx=e.clientX/W-.5;my=e.clientY/H-.5;});
addEventListener('scroll',()=>{sy=scrollY;nav.classList.toggle('solid',scrollY>40);},{passive:true});
const nav=document.getElementById('nav'),heroImg=document.getElementById('heroImg');

/* v2: scroll-driven intro */
(function(){const H=document.getElementById('hero');
  const ss=(a,b,x)=>{const k=Math.max(0,Math.min(1,(x-a)/(b-a)));return k*k*(3-2*k);};
  window.heroP=1;H.style.setProperty('--ia','0');H.style.setProperty('--ha','1');H.classList.add('live');
  window.bbReveal=()=>{};
  
  document.querySelectorAll('a[href="#top"]').forEach(a=>a.addEventListener('click',e=>{e.preventDefault();scrollTo({top:0,behavior:'smooth'});}));
})();
/* v2: loader */
(function(){const L=document.getElementById('loader'),T0=performance.now();let loaded=document.readyState==='complete',fonts=false,gone=false;
  addEventListener('load',()=>{loaded=true;});(document.fonts&&document.fonts.ready?document.fonts.ready:Promise.resolve()).then(()=>{fonts=true;});
  const go=()=>{if(gone)return;gone=true;L.style.setProperty('--lp',1);setTimeout(()=>{window.bbGo=performance.now();document.body.classList.add('go');L.classList.add('done');setTimeout(()=>L.classList.add('gone'),1500);},320);};
  (function chk(){if(gone)return;const g3=window.bb3d||!window.THREE||window.bbNo3d,el=performance.now()-T0;const p=.15+(loaded?.3:0)+(fonts?.2:0)+(g3?.3:0);L.style.setProperty('--lp',Math.min(.95,Math.max(p,Math.min(.9,el/5000))));
    if((loaded&&fonts&&g3&&el>600)||el>7000)go();else setTimeout(chk,100);})();})();
/* v2: progress, side nav, mobile bar */
(function(){const bar=document.getElementById('sprog'),side=document.getElementById('sidenav'),mb=document.getElementById('mbar'),H=document.getElementById('hero'),R=document.getElementById('rezervace');
  const links=[...side.querySelectorAll('a')],secs=links.map(a=>document.querySelector(a.getAttribute('href')));
  function tick(){const max=document.documentElement.scrollHeight-innerHeight;bar.style.transform=`scaleX(${max>0?scrollY/max:0})`;
    const past=H.getBoundingClientRect().bottom<innerHeight*.25;side.classList.toggle('show',past);
    const rr=R.getBoundingClientRect(),inR=rr.top<innerHeight*.9&&rr.bottom>0,ft=document.querySelector('footer').getBoundingClientRect().top<innerHeight;mb.classList.toggle('show',past&&!inR&&!ft);
    let on=-1;secs.forEach((s,i)=>{if(s&&s.getBoundingClientRect().top<innerHeight*.45)on=i;});links.forEach((a,i)=>a.classList.toggle('on',i===on));}
  addEventListener('scroll',tick,{passive:true});addEventListener('resize',tick);tick();
  const HR={wk:[11,14,17],we:[10,13,16,19,22]};const DS=['ne','po','út','st','čt','pá','so'];
  (function next(){const n=new Date();for(let d=0;d<8;d++){const day=new Date(n.getFullYear(),n.getMonth(),n.getDate()+d),g=day.getDay(),list=(g===5||g===6)?HR.we:HR.wk;
    for(const h of list){if(d>0||h>n.getHours()){document.getElementById('mbNext').textContent=(d===0?'dnes':d===1?'zítra':DS[g]+' '+day.getDate()+'. '+(day.getMonth()+1)+'.')+' '+h+':00';return;}}}})();
})();
/* hero glow follows cursor */
const hero=document.getElementById('hero'),glow=document.getElementById('heroGlow');
hero.addEventListener('mousemove',e=>{const r=hero.getBoundingClientRect();glow.style.left=(e.clientX-r.left)+'px';glow.style.top=(e.clientY-r.top)+'px';});

/* clock */
const ck=document.getElementById('clock');const tick=()=>{const d=new Date();ck.textContent=String(d.getHours()).padStart(2,'0')+':'+String(d.getMinutes()).padStart(2,'0');};tick();setInterval(tick,15000);

/* features + hotspots */
const I={
 star:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/><circle cx="19" cy="4" r=".8" fill="currentColor"/><circle cx="5" cy="19" r=".8" fill="currentColor"/></svg>',
 audio:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="6" y="2.5" width="12" height="19" rx="2"/><circle cx="12" cy="14.5" r="3.5"/><circle cx="12" cy="7" r="1.5"/></svg>',
 proj:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="2.5" y="4.5" width="19" height="12" rx="1.5"/><path d="M8 20h8M12 16.5V20"/><path d="M7 12l3-3 2 2 3-3 2 2" /></svg>',
 bath:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 12h18v2a6 6 0 01-6 6H9a6 6 0 01-6-6z"/><circle cx="9" cy="7" r="1.2"/><circle cx="13" cy="5" r="1"/><circle cx="15" cy="8.5" r="1.4"/></svg>',
 bed:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 18V7M21 18v-5a3 3 0 00-3-3H10v5M3 14h18"/><circle cx="6.5" cy="11" r="1.6"/></svg>'};
const F=[
 {k:'Strop',ic:'star',t:'Hvězdné nebe nad hlavou',p:'Lehněte si a dívejte se do vesmíru. Stovky světelných hvězd dotvářejí atmosféru celé místnosti.'},
 {k:'Zvuk',ic:'audio',t:'Špičkový audiosystém',p:'Čistý prostorový zvuk a hluboké basy, které ucítíte v těle. YouTube Music je připravené, pusťte si cokoliv.'},
 {k:'Plátno',ic:'proj',t:'Velkoplošná projekce',p:'Podmanivé vizuální světy, které dýchají v rytmu prostoru. Běží výhradně přes designovou AI aplikaci.'},
 {k:'Relax',ic:'bath',t:'Masážní vířivá vana',p:'Designová vířivka pro dva. Hřejivá voda, miliony bublinek a světelné efekty pro dokonalé uvolnění.'},
 {k:'Pohodlí',ic:'bed',t:'Královská postel King Size',p:'Dominanta prostoru. Absolutní pohodlí a intimní chvíle pod hvězdným nebem.'}];
const list=document.getElementById('featList');
list.innerHTML=F.map((f,i)=>`<li><button class="feat" data-i="${i}">${I[f.ic]}<div><h3>${f.t}</h3><p>${f.p}</p></div></button></li>`).join('');
const spots=[...document.querySelectorAll('.spot')],feats=[...list.querySelectorAll('.feat')],scene=document.getElementById('scene'),tip=document.getElementById('tip');
let cur=-1,auto=true,timer;
let show=function(i){if(i===cur)return;cur=i;const f=F[i],s=spots[i];
  spots.forEach((b,j)=>b.classList.toggle('on',j===i));feats.forEach((b,j)=>b.classList.toggle('on',j===i));
  tip.classList.add('hide');
  setTimeout(()=>{document.getElementById('tipK').textContent=f.k;document.getElementById('tipH').textContent=f.t;document.getElementById('tipP').textContent=f.p;
    const L=parseFloat(s.style.left),T=parseFloat(s.style.top);
    tip.style.left=L>55?'auto':`calc(${L}% + 30px)`;tip.style.right=L>55?`calc(${100-L}% + 30px)`:'auto';
    tip.style.top=T>55?'auto':`calc(${T}% + 10px)`;tip.style.bottom=T>55?`calc(${100-T}% + 10px)`:'auto';
    tip.classList.remove('hide');},cur===i&&tip.textContent?180:0);};
function stopAuto(){auto=false;clearInterval(timer);scene.classList.add('dim');}
const dots=document.getElementById('dots');dots.innerHTML=F.map(()=>'<i></i>').join('');const dotEls=[...dots.children];
const mob=()=>innerWidth<=760;let progScroll=false,psT;
const _show=show;show=function(i){_show(i);dotEls.forEach((d,j)=>d.classList.toggle('on',j===i));
  if(mob()){const li=feats[i].parentElement;progScroll=true;clearTimeout(psT);list.scrollTo({left:li.offsetLeft-(list.clientWidth-li.clientWidth)/2,behavior:'smooth'});psT=setTimeout(()=>progScroll=false,700);}};
let lsT;list.addEventListener('scroll',()=>{if(progScroll||!mob())return;clearTimeout(lsT);lsT=setTimeout(()=>{const c=list.scrollLeft+list.clientWidth/2;let best=0,bd=1e9;feats.forEach((f,j)=>{const li=f.parentElement,d=Math.abs(li.offsetLeft+li.clientWidth/2-c);if(d<bd){bd=d;best=j;}});
  if(best!==cur){auto=false;clearInterval(timer);_show(best);dotEls.forEach((d,j)=>d.classList.toggle('on',j===best));}},90);},{passive:true});
[...spots,...feats].forEach(b=>{const i=+b.dataset.i;
  b.addEventListener('mouseenter',()=>{stopAuto();show(i);});
  b.addEventListener('focus',()=>{stopAuto();show(i);});
  b.addEventListener('click',()=>{stopAuto();show(i);});});
show(0);
if(!reduce)timer=setInterval(()=>{if(auto)show((cur+1)%F.length);},3800);

/* hero: size image box so hotspots stay aligned, parallax, ceiling stars */
const stage=document.querySelector('.hero-stage'),hImg=heroImg,par=document.getElementById('heroPar');
function fitHero(){const w=stage.clientWidth,h=stage.clientHeight,s=Math.max(w/1600,h/951)*1.05,iw=1600*s,ih=951*s;
  hImg.style.width=iw+'px';hImg.style.height=ih+'px';hImg.style.left=(w-iw)/2+'px';hImg.style.top=(h-ih)*.55+'px';sizeCeil();}
const cv=document.getElementById('ceiling'),cx=cv.getContext('2d');let CS=[],shoot=null;
function sizeCeil(){const d=Math.min(devicePixelRatio||1,2),w=hImg.clientWidth,h=hImg.clientHeight;cv.width=w*d;cv.height=h*d;cx.setTransform(d,0,0,d,0,0);
  CS=[];for(let i=0;i<140;i++)CS.push({x:(.33+Math.random()*.67)*w,y:Math.random()*.33*h,r:Math.random()*1.4+.3,p:Math.random()*6.28,s:Math.random()*1.5+.4});}
function ceil(t){if(stage.classList.contains('gl'))return;const w=hImg.clientWidth,h=hImg.clientHeight;cx.clearRect(0,0,w,h);
  for(const s of CS){const a=Math.pow(Math.max(0,Math.sin(s.p+t*.001*s.s)),3);if(a<.05)continue;
    cx.globalAlpha=a;cx.fillStyle='#fff';cx.beginPath();cx.arc(s.x,s.y,s.r,0,6.28);cx.fill();
    if(a>.7&&s.r>1.2){cx.globalAlpha=a*.6;cx.fillRect(s.x-5*s.r,s.y-.35,10*s.r,.7);cx.fillRect(s.x-.35,s.y-5*s.r,.7,10*s.r);}}
  if(!shoot&&Math.random()<.006)shoot={x:(.6+Math.random()*.35)*w,y:Math.random()*.12*h,l:0};
  if(shoot){shoot.l+=1;const k=shoot.l/45,x=shoot.x-k*w*.28,y=shoot.y+k*h*.12;
    const g=cx.createLinearGradient(x,y,x+90,y-38);g.addColorStop(0,'rgba(255,255,255,'+(1-k)+')');g.addColorStop(1,'rgba(176,102,255,0)');
    cx.globalAlpha=1;cx.strokeStyle=g;cx.lineWidth=1.6;cx.beginPath();cx.moveTo(x,y);cx.lineTo(x+90,y-38);cx.stroke();if(k>=1)shoot=null;}
  cx.globalAlpha=1;if(!reduce)requestAnimationFrame(ceil);}
fitHero();addEventListener('resize',()=>fitHero());requestAnimationFrame(ceil);
/* 2D pan hero: used on phones/tablets and whenever WebGL is unavailable */
window.enablePan=function(){if(window.panOn)return;window.panOn=true;window.bbNo3d=true;stage.classList.remove('gl');hero.classList.add('pan-on');
  const HP=[[.74,.22],[.524,.70],[.45,.48],[.85,.67],[.66,.81]];let off=0,toff=0,vx=0,drag=false,maxOff=0,iw=0,w=0,baseOff=0,last=performance.now();
  const clampO=v=>Math.max(-24,Math.min(maxOff+24,v)),NATIVE=matchMedia('(pointer:coarse)').matches;
  if(NATIVE){par.classList.add('native');stage.classList.add('native');hImg.style.left='0px';}
  const sh=document.getElementById('swipeHint'),shGone=()=>sh&&sh.classList.add('gone');stage.addEventListener('touchstart',shGone,{passive:true,once:true});par.addEventListener('scroll',()=>{if(Math.abs(par.scrollLeft-baseOff)>12)shGone();},{passive:true});
  const moveTo=x=>{x=Math.max(0,Math.min(maxOff,x));if(NATIVE)par.scrollTo({left:x,behavior:'smooth'});else toff=x;vx=0;last=performance.now();},curOff=()=>NATIVE?par.scrollLeft:toff;
  fitHero=function(){w=stage.clientWidth;const h=stage.clientHeight,s=Math.max(h/951,w/1600);iw=1600*s;const ih=951*s;hImg.style.width=iw+'px';hImg.style.height=ih+'px';hImg.style.top=(h-ih)/2+'px';maxOff=Math.max(0,iw-w);
    baseOff=Math.min(maxOff,Math.max(0,iw*.452-w*(w<=760?.7:.5)));if(!fitHero.d){toff=off=baseOff;fitHero.d=1;if(NATIVE)requestAnimationFrame(()=>{par.scrollLeft=baseOff;});}sizeCeil();};
  fitHero();
  const HS=HP.map(([u,v],i)=>{const b=document.createElement('button');b.type='button';b.className='hs'+(u>.62?' l':'')+(i===0?' hs-top':'');b.setAttribute('aria-label',F[i].t);b.style.left=(u*100)+'%';b.style.top=(v*100)+'%';
    b.innerHTML=`<em class="hs-lbl">${['Hvězdné nebe','Zvuk','Projekce','Vířivka','King Size postel'][i]}</em><span class="hs-tip"><b>${F[i].k}</b><strong>${F[i].t}</strong><span>${F[i].p}</span></span>`;(NATIVE?hImg:document.getElementById('hspots')).appendChild(b);return b;});
  const inf=document.getElementById('hsInfo'),chips=document.getElementById('hsChips');
  chips.innerHTML=F.map((f,i)=>`<button type="button" data-i="${i}"><i></i>${['Hvězdy','Zvuk','Plátno','Vířivka','Postel'][i]}</button>`).join('');const CB=[...chips.children];
  function info(i,on){HS.forEach((x,j)=>x.classList.toggle('open',on&&j===i));CB.forEach((c,j)=>c.classList.toggle('on',on&&j===i));
    if(on){document.getElementById('hsK').textContent=F[i].k;document.getElementById('hsT').textContent=F[i].t;document.getElementById('hsP').textContent=F[i].p;}if(innerWidth<=760)inf.hidden=!on;}
  function focus(i){moveTo(HP[i][0]*iw-w*(w<=760?.74:.5));info(i,true);}
  HS.forEach((b,i)=>{b.addEventListener('pointerdown',e=>e.stopPropagation());b.addEventListener('touchstart',e=>e.stopPropagation(),{passive:true});
    b.addEventListener('mouseenter',()=>{HS.forEach(x=>{if(x!==b)x.classList.remove('open');});last=performance.now();});
    b.addEventListener('click',e=>{e.stopPropagation();const o=b.classList.contains('open');last=performance.now();info(i,!o);});});
  CB.forEach(c=>c.addEventListener('click',()=>focus(+c.dataset.i)));
  if(innerWidth>760){(function tv(){if(!window.bbGo)return setTimeout(tv,200);setTimeout(()=>{if(!HS.some(x=>x.classList.contains('open')))HS[2].classList.add('open');},1400);})();}
  document.getElementById('hsX').addEventListener('click',()=>info(0,false));
  document.getElementById('lookL').addEventListener('click',()=>moveTo(curOff()-w*.55));
  document.getElementById('lookR').addEventListener('click',()=>moveTo(curOff()+w*.55));
  let tS=null;
  if(!NATIVE)stage.addEventListener('touchstart',e=>{if(e.touches.length!==1)return;const p=e.touches[0];tS={x:p.clientX,y:p.clientY,lx:p.clientX,lock:0};},{passive:true});
  stage.addEventListener('touchmove',e=>{if(!tS)return;const p=e.touches[0],dx=p.clientX-tS.x,dy=p.clientY-tS.y;
    if(!tS.lock){if(Math.hypot(dx,dy)<7)return;tS.lock=Math.abs(dx)>Math.abs(dy)*.8?1:2;if(tS.lock===1){drag=true;vx=0;}}
    if(tS.lock!==1)return;e.preventDefault();const d=p.clientX-tS.lx;toff=clampO(toff-d);vx=-d;tS.lx=p.clientX;last=performance.now();},{passive:false});
  const tEnd=()=>{drag=false;tS=null;last=performance.now();};stage.addEventListener('touchend',tEnd);stage.addEventListener('touchcancel',tEnd);
  let mD=null;stage.addEventListener('pointerdown',e=>{if(e.pointerType!=='mouse'||e.button>0)return;mD=e.clientX;drag=true;vx=0;try{stage.setPointerCapture(e.pointerId);}catch(_){}});
  stage.addEventListener('pointermove',e=>{if(mD==null)return;const d=e.clientX-mD;toff=clampO(toff-d);vx=-d;mD=e.clientX;last=performance.now();});
  const mEnd=()=>{mD=null;drag=false;};stage.addEventListener('pointerup',mEnd);stage.addEventListener('pointercancel',mEnd);
  addEventListener('resize',()=>fitHero());
  if(!NATIVE)(function pan(n){requestAnimationFrame(pan);if(!drag){toff+=vx;vx*=.9;if(toff<0)toff+=(0-toff)*.2;if(toff>maxOff)toff+=(maxOff-toff)*.2;
      if(n-last>7000){const q=(n-last-7000)*.00016;toff+=((baseOff+Math.sin(q)*w*.12)-toff)*.01;}}
    off+=(toff-off)*(drag?.5:.12);hImg.style.left=(-off).toFixed(1)+'px';
    const hT=parseFloat(hImg.style.top)||0,ih=hImg.clientHeight,sh=stage.clientHeight;HS.forEach((b,i)=>{const x=HP[i][0]*iw-off,y=hT+HP[i][1]*ih;b.style.left=x.toFixed(1)+'px';b.style.top=y.toFixed(1)+'px';b.classList.toggle('l',x>w*.62);b.classList.toggle('up',y>sh*.56);b.classList.toggle('off',x<18||x>w-18||y<90||y>sh-70);});})(performance.now());
};
if(matchMedia('(pointer:fine)').matches&&!reduce){hero.addEventListener('mousemove',e=>{if(stage.classList.contains('gl'))return;const r=hero.getBoundingClientRect(),px=(e.clientX-r.left)/r.width-.5,py=(e.clientY-r.top)/r.height-.5;
  par.style.transform=`translate3d(${-px*22}px,${-py*14}px,0)`;});hero.addEventListener('mouseleave',()=>par.style.transform='');}

/* 3D look-around hero (three.js) */
(function(){
  if(!window.THREE||matchMedia('(max-width:760px), (pointer:coarse)').matches){window.enablePan();return;}const cvs=document.getElementById('gl');let R3;
  const MOB=matchMedia('(max-width:760px), (pointer:coarse)').matches;let frameN=0;
  try{R3=new THREE.WebGLRenderer({canvas:cvs,antialias:!MOB,powerPreference:'high-performance'});}catch(e){window.enablePan();return;}
  R3.outputEncoding=THREE.sRGBEncoding;R3.setPixelRatio(Math.min(devicePixelRatio||1,MOB?1.5:2));
  const scn=new THREE.Scene(),cam=new THREE.PerspectiveCamera(50,1,.1,100);cam.rotation.order='YXZ';
  const img=new Image();img.src=getComputedStyle(heroImg).backgroundImage.slice(5,-2);
  img.onerror=()=>window.enablePan();
  img.onload=()=>{try{
    const W=img.width,H=img.height,tc=document.createElement('canvas');const TS=MOB?.5:1;tc.width=Math.round(W*TS);tc.height=Math.round(H*TS);const g=tc.getContext('2d');
    const tex=new THREE.CanvasTexture(tc);tex.encoding=THREE.sRGBEncoding;tex.wrapS=THREE.RepeatWrapping;tex.repeat.x=-1;tex.offset.x=1;
    tex.generateMipmaps=false;tex.minFilter=THREE.LinearFilter;tex.anisotropy=R3.capabilities.getMaxAnisotropy();
    const TH=1.95,RAD=10,HH=RAD*TH*H/W,VA=2*Math.atan(HH/2/RAD);
    const mat=new THREE.MeshBasicMaterial({map:tex,side:THREE.BackSide,color:0x000000});
    scn.add(new THREE.Mesh(new THREE.CylinderGeometry(RAD,RAD,HH,128,1,true,Math.PI-TH/2,TH),mat));
    const SCR=[[.354,.366],[.55,.372],[.548,.595],[.356,.616]].map(([x,y])=>[x*W,y*H]);
    const sb=[.35*W,.36*H,.205*W,.26*H];
    const ST=[];for(let i=0;i<170;i++){const x=.34+Math.random()*.64,y=Math.random()*.31;if(y>.255+(x-.33)*.27&&x<.6)continue;ST.push({x:x*W,y:y*H,r:Math.random()*1.6+.4,p:Math.random()*6.28,s:Math.random()*1.4+.4});}
    let shoot=null,maxY=0,maxP=0,yaw=0,pitch=0,ty=0,tp=0,drag=false,lx=0,ly=0,vx=0,vy=0,hover=false,hx=0,hy=0,last=performance.now()-6000,vis=true,t0=performance.now(),copyR=0,topL=18,botL=18,base=0,vf0=.8;
    function fit(){const w=stage.clientWidth,h=stage.clientHeight,A=w/h;R3.setSize(w,h,false);
      let hf=Math.min(72*Math.PI/180,TH-.5),vf=2*Math.atan(Math.tan(hf/2)/A);if(vf>VA-.1){vf=VA-.1;hf=2*Math.atan(Math.tan(vf/2)*A);}
      cam.fov=vf*180/Math.PI;cam.aspect=A;cam.updateProjectionMatrix();maxY=Math.max(0,(TH-hf)/2-.015);base=innerWidth>760?maxY*.45:0;if(!fit.done){ty=yaw=base;fit.done=1;}maxP=Math.max(0,(VA-vf)/2-.01);
      const cr=document.querySelector('.hero-copy').getBoundingClientRect(),sr=stage.getBoundingClientRect();copyR=(cr.top<sr.bottom&&cr.bottom>sr.top&&innerWidth>760)?cr.right-sr.left+30:0;
      topL=innerWidth<=760?18:100;botL=innerWidth<=760?18:90;vf0=vf;}
    fit();addEventListener('resize',fit);
    /* intro: neon cube + deep space */
    scn.add(cam);const cube=new THREE.Group();cam.add(cube);
    const lmA=new THREE.LineBasicMaterial({color:0xb066ff,transparent:true,opacity:0}),lmB=new THREE.LineBasicMaterial({color:0xe7e8f2,transparent:true,opacity:0});
    cube.add(new THREE.LineSegments(new THREE.EdgesGeometry(new THREE.BoxGeometry(1.3,1.3,1.3)),lmA));
    const inner=new THREE.LineSegments(new THREE.EdgesGeometry(new THREE.BoxGeometry(.78,.78,.78)),lmB);cube.add(inner);
    const gc=document.createElement('canvas');gc.width=gc.height=128;const gx=gc.getContext('2d'),gg=gx.createRadialGradient(64,64,0,64,64,64);gg.addColorStop(0,'rgba(190,140,255,.9)');gg.addColorStop(.35,'rgba(140,80,255,.35)');gg.addColorStop(1,'rgba(90,40,255,0)');gx.fillStyle=gg;gx.fillRect(0,0,128,128);
    const glowM=new THREE.SpriteMaterial({map:new THREE.CanvasTexture(gc),transparent:true,opacity:0,blending:THREE.AdditiveBlending,depthWrite:false});const glowS=new THREE.Sprite(glowM);glowS.scale.set(4.2,4.2,1);cube.add(glowS);
    cube.position.set(0,0,-4.2);
    const sp=new Float32Array(1500*3);for(let i=0;i<1500;i++){const r=3+Math.random()*6,th=Math.random()*6.283,ph=Math.acos(Math.random()*2-1);sp[i*3]=r*Math.sin(ph)*Math.cos(th);sp[i*3+1]=r*Math.cos(ph)*.8;sp[i*3+2]=r*Math.sin(ph)*Math.sin(th);}
    const sg=new THREE.BufferGeometry();sg.setAttribute('position',new THREE.BufferAttribute(sp,3));const sm=new THREE.PointsMaterial({color:0xdcd2ff,size:.035,transparent:true,opacity:0,depthWrite:false});const space=new THREE.Points(sg,sm);scn.add(space);
    const ss=(a,b,x)=>{const k=Math.max(0,Math.min(1,(x-a)/(b-a)));return k*k*(3-2*k);};
    /* hotspots in 3D */
    const HP=[[.74,.22],[.524,.70],[.45,.48],[.85,.67],[.66,.81]],hsWrap=document.getElementById('hspots');
    hsWrap.innerHTML=HP.map((p,i)=>`<button class="hs" type="button" aria-label="${F[i].t}"><span class="hs-tip"><b>${F[i].k}</b><strong>${F[i].t}</strong><span>${F[i].p}</span></span></button>`).join('');
    const hsK=document.getElementById('hsK'),hsT=document.getElementById('hsT'),hsP=document.getElementById('hsP');
    document.getElementById('hsX').addEventListener('click',()=>{document.getElementById('hsInfo').hidden=true;HS.forEach(x=>x.classList.remove('open'));});
    hsWrap.addEventListener('mouseover',e=>{const b=e.target.closest('.hs');if(!b)return;[...hsWrap.children].forEach(x=>{if(x!==b)x.classList.remove('open');});});
    const HS=[...hsWrap.children],HV=HP.map(([u,v])=>{const th=Math.PI-TH/2+(1-u)*TH;return new THREE.Vector3(RAD*Math.sin(th),HH*(.5-v),RAD*Math.cos(th));}),pv=new THREE.Vector3();
    HS.forEach(b=>{b.addEventListener('pointerdown',e=>e.stopPropagation());b.addEventListener('click',e=>{e.stopPropagation();const o=b.classList.contains('open');HS.forEach(x=>x.classList.remove('open'));if(!o)b.classList.add('open');used();
        const i=HS.indexOf(b),inf=document.getElementById('hsInfo');if(innerWidth<=760){if(o){inf.hidden=true;}else{hsK.textContent=F[i].k;hsT.textContent=F[i].t;hsP.textContent=F[i].p;inf.hidden=false;}}});
      b.addEventListener('mouseenter',()=>{hover=true;used();HS.forEach(x=>{if(x!==b)x.classList.remove('open');});});});
    function placeSpots(){const w=stage.clientWidth,h=stage.clientHeight;HS.forEach((b,i)=>{pv.copy(HV[i]).project(cam);const x=(pv.x+1)/2*w,y=(1-pv.y)/2*h;
      const off=pv.z>1||x<18||x>w-18||y<topL||y>h-botL||x<copyR;b.classList.toggle('off',off);b.classList.toggle('l',x>w*.62);b.classList.toggle('up',y>h*.56);b.style.transform=`translate(${x.toFixed(1)}px,${y.toFixed(1)}px)`;});}
    function paint(t){g.setTransform(TS,0,0,TS,0,0);g.globalCompositeOperation='source-over';g.globalAlpha=1;g.filter='none';g.drawImage(img,0,0);
      g.save();g.beginPath();SCR.forEach(([x,y],i)=>i?g.lineTo(x,y):g.moveTo(x,y));g.closePath();g.clip();
      const k=(Math.sin(t*.0007)+1)/2;g.globalAlpha=.85*k;g.filter=`hue-rotate(${Math.sin(t*.0003)*70}deg) saturate(1.35)`;g.drawImage(img,sb[0],sb[1],sb[2],sb[3],sb[0],sb[1],sb[2],sb[3]);g.filter='none';
      const sx=sb[0]+((t*.00018)%1.6-.3)*sb[2],gr=g.createLinearGradient(sx-60,0,sx+60,0);gr.addColorStop(0,'rgba(255,255,255,0)');gr.addColorStop(.5,'rgba(255,255,255,.22)');gr.addColorStop(1,'rgba(255,255,255,0)');
      g.globalAlpha=1;g.globalCompositeOperation='lighter';g.fillStyle=gr;g.fillRect(sb[0],sb[1],sb[2],sb[3]);g.restore();
      g.globalCompositeOperation='lighter';const bl=g.createRadialGradient(.452*W,.49*H,20,.452*W,.49*H,.26*W);bl.addColorStop(0,`rgba(90,190,255,${.12+.1*k+(window.bbE||0)*.35})`);bl.addColorStop(1,'rgba(90,120,255,0)');g.fillStyle=bl;g.fillRect(0,0,W,H);
      g.fillStyle='#fff';for(const s of ST){const a=Math.pow(Math.max(0,Math.sin(s.p+t*.001*s.s)),3);if(a<.05)continue;g.globalAlpha=a;g.beginPath();g.arc(s.x,s.y,s.r,0,6.28);g.fill();
        if(a>.7&&s.r>1.3){g.globalAlpha=a*.55;g.fillRect(s.x-6*s.r,s.y-.4,12*s.r,.8);g.fillRect(s.x-.4,s.y-6*s.r,.8,12*s.r);}}
      if(!shoot&&Math.random()<.007)shoot={x:(.62+Math.random()*.33)*W,y:Math.random()*.1*H,l:0};
      if(shoot){shoot.l++;const q=shoot.l/50,x=shoot.x-q*W*.25,y=shoot.y+q*H*.1,sg=g.createLinearGradient(x,y,x+120,y-48);sg.addColorStop(0,`rgba(255,255,255,${1-q})`);sg.addColorStop(1,'rgba(176,102,255,0)');
        g.globalAlpha=1;g.strokeStyle=sg;g.lineWidth=2.2;g.beginPath();g.moveTo(x,y);g.lineTo(x+120,y-48);g.stroke();if(q>=1)shoot=null;}
      g.globalAlpha=1;g.globalCompositeOperation='source-over';tex.needsUpdate=true;}
    function lights(t){if(!window.bbGo)return 0;const e=Math.max(0,(t-window.bbGo-150)/3400);if(e>=1)return 1;return e<.5?4*e*e*e:1-Math.pow(-2*e+2,3)/2;}
    const clamp=(v,m)=>Math.max(-m,Math.min(m,v));
    function loop(t){requestAnimationFrame(loop);if(!vis)return;const P=1,live=!!window.bbGo&&t-window.bbGo>450,E=window.bbE||0;
      if(!drag){ty+=vx;tp+=vy;vx*=.92;vy*=.92;ty+=(clamp(ty,maxY)-ty)*.15;tp+=(clamp(tp,maxP)-tp)*.15;}
      if(!live){ty+=(base-ty)*.04;tp+=(-maxP*.2*(1-P)-tp)*.04;}
      else if(!drag&&!hover&&t-last>6000){const q=(t-last-6000)*.00018;ty+=(base+Math.sin(q)*maxY*.5-ty)*.02;tp+=(Math.sin(q*.8)*maxP*.4-tp)*.02;}
      const gy=ty+(hover&&!drag&&live?-hx*maxY*.18:0),gp=tp+(hover&&!drag&&live?-hy*maxP*.2:0);
      yaw+=(gy-yaw)*(drag?.35:.09);pitch+=(gp-pitch)*(drag?.35:.09);cam.rotation.y=yaw;cam.rotation.x=pitch;
      const e=ss(.06,.62,P),fin=Math.min(1,(t-t0)/1400),cf=(1-ss(.34,.6,P))*fin;
      const nf=(vf0*(.74+.26*e))*180/Math.PI;if(Math.abs(cam.fov-nf)>.01){cam.fov=nf;cam.updateProjectionMatrix();}
      cube.position.z=-4.2+e*4.9;cube.rotation.y=t*.00035+P*4;cube.rotation.x=.55+P*1.4+Math.sin(t*.0005)*.1;inner.rotation.y=-t*.0006;
      lmA.opacity=cf;lmB.opacity=cf*.8;glowM.opacity=cf*(.55+.25*Math.sin(t*.0017)+E*.5);cube.visible=cf>.003;
      sm.opacity=(1-ss(.25,.62,P))*fin*.9;space.visible=sm.opacity>.003;space.rotation.y=t*.00002;
      frameN++;if((!reduce||t-t0<100)&&(!MOB||frameN%2===0))paint(t);const L=reduce?1:lights(t);mat.color.setScalar(Math.min(1,L*(1+E*.12)));
      stage.classList.toggle('ready',live);R3.render(scn,cam);if(live)placeSpots();
      if(live&&!loop.tv&&innerWidth>760){if(HS.some(x=>x.classList.contains('open'))||t-window.bbGo>9000)loop.tv=1;else if(!HS[2].classList.contains('off')){HS[2].classList.add('open');loop.tv=1;}}}
    const look=document.getElementById('look');
    function used(){last=performance.now();look.classList.add('used');}
    const rad=()=>cam.fov/stage.clientHeight*Math.PI/180*1.35;
    stage.addEventListener('pointerdown',e=>{if(e.button>0||e.pointerType==='touch')return;drag=true;lx=e.clientX;ly=e.clientY;vx=vy=0;stage.classList.add('drag');if(e.pointerType==='mouse'){try{stage.setPointerCapture(e.pointerId);}catch(_){}}used();HS.forEach(x=>x.classList.remove('open'));});
    stage.addEventListener('pointermove',e=>{const r=stage.getBoundingClientRect();hx=(e.clientX-r.left)/r.width-.5;hy=(e.clientY-r.top)/r.height-.5;
      if(!drag)return;const k=rad(),dx=(e.clientX-lx)*k,dy=(e.clientY-ly)*k;
      const sy=Math.abs(ty)>maxY?.35:1,sp=Math.abs(tp)>maxP?.35:1;ty=clamp(ty+dx*sy,maxY+.06);tp=clamp(tp+dy*sp,maxP+.04);vx=dx*.9;vy=dy*.9;lx=e.clientX;ly=e.clientY;used();});
    let tS=null;
    stage.addEventListener('touchstart',e=>{if(e.touches.length!==1||e.target.closest('.hs')){tS=null;return;}const p=e.touches[0];tS={x:p.clientX,y:p.clientY,lx:p.clientX,ly:p.clientY,lock:0};},{passive:true});
    stage.addEventListener('touchmove',e=>{if(!tS)return;const p=e.touches[0],dx=p.clientX-tS.x,dy=p.clientY-tS.y;
      if(!tS.lock){if(Math.hypot(dx,dy)<7)return;tS.lock=Math.abs(dx)>Math.abs(dy)*.8?1:2;if(tS.lock===1){drag=true;vx=vy=0;}}
      if(tS.lock!==1)return;e.preventDefault();const k=rad()*1.25,ddx=(p.clientX-tS.lx)*k,ddy=(p.clientY-tS.ly)*k*.6;
      const s1=Math.abs(ty)>maxY?.35:1;ty=clamp(ty+ddx*s1,maxY+.06);tp=clamp(tp+ddy,maxP);vx=ddx*.9;tS.lx=p.clientX;tS.ly=p.clientY;used();},{passive:false});
    const tEnd=()=>{if(tS&&tS.lock===1){drag=false;used();}tS=null;};stage.addEventListener('touchend',tEnd);stage.addEventListener('touchcancel',tEnd);
    const chips=document.getElementById('hsChips');chips.innerHTML=F.map((f,i)=>`<button type="button" data-i="${i}"><i></i>${['Hvězdy','Zvuk','Plátno','Vířivka','Postel'][i]}</button>`).join('');
    const CB=[...chips.children];
    function focusSpot(i){const v=HV[i];ty=clamp(Math.atan2(-v.x,-v.z),maxY);tp=clamp(Math.atan2(v.y,Math.hypot(v.x,v.z)),maxP);vx=vy=0;used();
      HS.forEach((x,j)=>x.classList.toggle('open',j===i));CB.forEach((c,j)=>c.classList.toggle('on',j===i));
      hsK.textContent=F[i].k;hsT.textContent=F[i].t;hsP.textContent=F[i].p;if(innerWidth<=760)document.getElementById('hsInfo').hidden=false;}
    CB.forEach(c=>c.addEventListener('click',()=>focusSpot(+c.dataset.i)));
    HS.forEach((b,i)=>b.addEventListener('click',()=>CB.forEach((c,j)=>c.classList.toggle('on',j===i&&b.classList.contains('open')))));
    const end=()=>{if(!drag)return;drag=false;stage.classList.remove('drag');used();};
    stage.addEventListener('pointerup',end);stage.addEventListener('pointercancel',end);
    stage.addEventListener('pointerenter',e=>{if(e.pointerType==='mouse')hover=true;});stage.addEventListener('pointerleave',()=>{hover=false;});
    stage.addEventListener('keydown',e=>{const s=maxY*.25;if(e.key==='ArrowLeft'){ty=clamp(ty+s,maxY);used();e.preventDefault();}if(e.key==='ArrowRight'){ty=clamp(ty-s,maxY);used();e.preventDefault();}
      if(e.key==='ArrowUp'){tp=clamp(tp+maxP*.4,maxP);used();e.preventDefault();}if(e.key==='ArrowDown'){tp=clamp(tp-maxP*.4,maxP);used();e.preventDefault();}});
    document.getElementById('lookL').addEventListener('click',()=>{ty=clamp(ty+maxY*.55,maxY);used();});
    document.getElementById('lookR').addEventListener('click',()=>{ty=clamp(ty-maxY*.55,maxY);used();});
    document.getElementById('look').addEventListener('pointerdown',e=>e.stopPropagation());
    new IntersectionObserver(es=>{vis=es[0].isIntersecting;}).observe(stage);
    stage.classList.add('gl');hero.classList.add('gl-on');requestAnimationFrame(loop);requestAnimationFrame(()=>requestAnimationFrame(()=>{window.bb3d=true;}));}catch(err){window.enablePan();}
  };
})();

/* 20s story */
(function(){
  const B=[0,5.2,10.6,16,21.4,26.8,32],D=32;
  const CAP=[['Zavoláte si o termín','Jeden telefonát a blok je váš.'],['Osobně vás přivítáme','Uvedeme vás dovnitř. Diskrétně a bez spěchu.'],['Rozsvítí se hvězdy','Tma, luxus a vesmír nad hlavou.'],['Vířivka pro dva','Hřejivá voda a miliony bublinek.'],['Hudba a obraz','Pusťte si cokoliv. Basy ucítíte v těle.'],['Čas se zastaví','Dvě hodiny jen pro vás.']];
  const LBL=['Rezervace','Uvítání','Hvězdy','Vířivka','Zvuk a obraz','Váš čas'];
  const SRC={pano:getComputedStyle(heroImg).backgroundImage.slice(5,-2),bath:document.querySelector('.g2 img').src,proj:document.querySelector('.g5 img').src,bed:(document.querySelector('img[alt^="King Size postel"]')||document.querySelector('.concept-img img')).src};
  document.querySelectorAll('#stStage .ph').forEach(el=>el.style.backgroundImage=`url(${SRC[el.dataset.ph]})`);
  const sc=[...document.querySelectorAll('#stStage .sc')],bar=document.getElementById('stBar');
  bar.innerHTML=LBL.map((l,i)=>`<button class="seg" type="button" data-i="${i}" aria-label="Krok ${i+1}: ${l}" style="flex:${B[i+1]-B[i]}"><span class="tr"><i></i></span><span>${l}</span></button>`).join('');
  bar.style.gridTemplateColumns=B.slice(1).map((b,i)=>(b-B[i])+'fr').join(' ');
  const segs=[...bar.querySelectorAll('.seg')];
  const s3=sc[2];for(let i=0;i<70;i++){const e=document.createElement('i');e.style.left=Math.random()*100+'%';e.style.top=Math.random()*38+'%';e.style.setProperty('--th',(Math.random()*.8).toFixed(2));e.style.animationDelay=(-Math.random()*2)+'s';s3.appendChild(e);}
  const s4=sc[3];for(let i=0;i<34;i++){const e=document.createElement('b'),z=6+Math.random()*22;e.style.width=e.style.height=z+'px';e.style.left=(20+Math.random()*60)+'%';e.style.setProperty('--du',(3+Math.random()*4).toFixed(2)+'s');e.style.animationDelay=(-Math.random()*6).toFixed(2)+'s';e.style.setProperty('--dx',((Math.random()-.5)*120).toFixed(0)+'px');s4.appendChild(e);}
  const wv=sc[4].querySelector('.wave');for(let i=0;i<16;i++){const s=document.createElement('span');s.style.animationDelay=(-Math.random())+'s';s.style.animationDuration=(.6+Math.random()*.8)+'s';wv.appendChild(s);}
  const tk=document.getElementById('ticks');let th='';for(let i=0;i<12;i++){const a=i*30*Math.PI/180;th+=`<line x1="${Math.sin(a)*80}" y1="${-Math.cos(a)*80}" x2="${Math.sin(a)*88}" y2="${-Math.cos(a)*88}" stroke="#e7e8f2" stroke-width="${i%3?1.5:3}" stroke-linecap="round"/>`;}tk.innerHTML=th;
  const cap=document.getElementById('stCap'),N=document.getElementById('stN'),Hh=document.getElementById('stH'),P=document.getElementById('stP'),tm=document.getElementById('stTime');
  const play=document.getElementById('stPlay'),ico=document.getElementById('stIco'),sl=document.getElementById('slPick'),ok=document.getElementById('phOk'),tap=document.getElementById('tap'),phone=document.getElementById('phone');
  const hS=document.getElementById('hS'),hM=document.getElementById('hM'),hH=document.getElementById('hH');
  let t=0,cur=-1,playing=false,lt=0,seen=false;
  const ease=x=>1-Math.pow(1-Math.max(0,Math.min(1,x)),3);
  function render(){const i=Math.min(5,B.findIndex((b,k)=>t<B[k+1])<0?5:B.findIndex((b,k)=>t<B[k+1]));const p=(t-B[i])/(B[i+1]-B[i]);
    if(i!==cur){sc.forEach((s,k)=>s.classList.toggle('on',k===i));cap.classList.add('sw');setTimeout(()=>{N.textContent=`0${i+1} / 06`;Hh.textContent=CAP[i][0];P.textContent=CAP[i][1];cap.classList.remove('sw');},cur<0?0:250);cur=i;}
    sc[i].style.setProperty('--p',p.toFixed(4));
    segs.forEach((s,k)=>{s.classList.toggle('on',k===i);s.style.setProperty('--f',k<i?1:k>i?0:p);});
    if(i===0){sl.classList.toggle('sel',p>.42);ok.classList.toggle('show',p>.68);
      if(p>.34&&p<.46&&!tap.classList.contains('show')){const r=sl.getBoundingClientRect(),s=sc[0].getBoundingClientRect();tap.style.left=(r.left-s.left+r.width/2)+'px';tap.style.top=(r.top-s.top+r.height/2)+'px';tap.classList.add('show');setTimeout(()=>tap.classList.remove('show'),650);}}
    if(i===1)sc[1].style.setProperty('--o',ease((p-.12)/.7).toFixed(4));
    if(i===5){const k=ease(p/.62);hS.setAttribute('transform',`rotate(${k*540})`);hM.setAttribute('transform',`rotate(${120+k*40})`);hH.setAttribute('transform',`rotate(${-60+k*6})`);sc[5].classList.toggle('cta-on',p>.45);}
    const sec=Math.floor(t);tm.innerHTML=`<b>0:${String(sec).padStart(2,'0')}</b> / 0:${D}`;}
  function setPlay(v){playing=v;ico.innerHTML=v?'<path d="M7 5h4v14H7zM13 5h4v14h-4z"/>':'<path d="M7 5v14l12-7z"/>';play.setAttribute('aria-label',v?'Pozastavit':'Přehrát');if(v){lt=performance.now();requestAnimationFrame(step);}}
  function step(n){if(!playing)return;t+=(n-lt)/1000;lt=n;if(t>=D){t=D-.001;render();setPlay(false);setTimeout(()=>{if(!playing&&t>=D-.01&&inView){t=0;setPlay(true);}},2500);return;}render();requestAnimationFrame(step);}
  play.addEventListener('click',()=>{if(t>=D-.01)t=0;setPlay(!playing);});
  let scrub=false;
  function at(e){const r=bar.getBoundingClientRect();t=Math.max(0,Math.min(D-.001,(e.clientX-r.left)/r.width*D));render();}
  bar.addEventListener('pointerdown',e=>{scrub=true;bar.setPointerCapture(e.pointerId);at(e);});
  bar.addEventListener('pointermove',e=>{if(scrub)at(e);});
  bar.addEventListener('pointerup',()=>scrub=false);
  segs.forEach(s=>s.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();t=B[+s.dataset.i];render();}}));
  let inView=false;new IntersectionObserver(es=>{inView=es[0].isIntersecting;if(inView&&!playing&&!window.bbRM){if(!seen||t>=D-.01){t=0;render();}seen=true;setPlay(true);}if(!inView&&playing)setPlay(false);},{threshold:.35}).observe(document.getElementById('stStage'));
  render();
})();

/* custom cursor */
const curEl=document.getElementById('cur'),dot=document.getElementById('curDot');let cxp=-100,cyp=-100,rx=-100,ry=-100;
if(matchMedia('(pointer:fine)').matches){addEventListener('mousemove',e=>{document.body.classList.add('mv');cxp=e.clientX;cyp=e.clientY;dot.style.transform=`translate(${cxp}px,${cyp}px)`;
  curEl.classList.toggle('big',!!e.target.closest('a,button,.spot,input'));});
  (function loop(){rx+=(cxp-rx)*.16;ry+=(cyp-ry)*.16;curEl.style.transform=`translate(${rx}px,${ry}px)`;requestAnimationFrame(loop);})();}

/* tilt */
if(matchMedia('(pointer:fine)').matches&&!reduce)document.querySelectorAll('.tilt,.gal button').forEach(el=>{
  el.addEventListener('mousemove',e=>{const r=el.getBoundingClientRect(),x=(e.clientX-r.left)/r.width-.5,y=(e.clientY-r.top)/r.height-.5;el.style.transform=`perspective(900px) rotateY(${x*8}deg) rotateX(${-y*8}deg) translateZ(0)`;});
  el.addEventListener('mouseleave',()=>el.style.transform='');});

/* lights out */
const dark=document.getElementById('dark'),tcv=document.getElementById('torch'),tg=tcv.getContext('2d');
const tImg=new Image();tImg.src=getComputedStyle(heroImg).backgroundImage.slice(5,-2);
let exitT=0,ex=0,ey=0,dnX=0,dnY=0,dnT=0,tOn=false,tW=0,tH=0,brC,dkC,mkC,tmC,px=0,py=0,gx=0,gy=0,lastMove=0,TS=[],tStart=0;
const mk=()=>document.createElement('canvas');
let tKey='';function tSize(){tKey=innerWidth+'x'+innerHeight;const d=Math.min(devicePixelRatio||1,1.5);tW=Math.round(innerWidth*d);tH=Math.round(innerHeight*d);tcv.width=tW;tcv.height=tH;
  [brC,dkC,mkC,tmC]=[mk(),mk(),mk(),mk()];[brC,dkC,mkC,tmC].forEach(c=>{c.width=tW;c.height=tH;});
  const por=tW<tH*.9,s=por?Math.max(tW/tImg.width,tH/tImg.height*.53):Math.max(tW/tImg.width,tH/tImg.height),iw=tImg.width*s,ih=tImg.height*s,uc=por?.645:.5,ox=Math.min(0,Math.max(tW-iw,tW*.5-uc*iw)),oy=(tH-ih)*(por?.52:.45);HS.forEach(h=>{h.x=ox+h.u*iw;h.y=oy+h.v*ih;{const hw=(h.el.offsetWidth||120)/2+12;h.el.style.left=Math.min(innerWidth-hw,Math.max(hw,h.x/d))+'px';}h.el.style.top=(h.y/d)+'px';h.on=h.x>20*d&&h.x<tW-20*d&&h.y>20*d&&h.y<tH-20*d;});TUB.x=ox+.865*iw;TUB.y=oy+.69*ih;TUB.rx=.05*iw;TUB.ry=.03*ih;
  const b=brC.getContext('2d');b.drawImage(tImg,ox,oy,iw,ih);b.globalCompositeOperation='lighter';b.globalAlpha=.12;b.drawImage(tImg,ox,oy,iw,ih);
  const k=dkC.getContext('2d');k.drawImage(tImg,ox,oy,iw,ih);
  if(ih<tH-2){[b,k].forEach(c=>{c.globalAlpha=1;c.globalCompositeOperation='destination-in';const fg=c.createLinearGradient(0,oy,0,oy+ih);fg.addColorStop(0,'rgba(0,0,0,0)');fg.addColorStop(.14,'#000');fg.addColorStop(.86,'#000');fg.addColorStop(1,'rgba(0,0,0,0)');c.fillStyle=fg;c.fillRect(0,0,tW,tH);c.globalCompositeOperation='source-over';});}k.fillStyle='rgba(2,1,10,.93)';k.fillRect(0,0,tW,tH);
  TS=[];for(let i=0;i<180;i++){const u=.34+Math.random()*.64,v=Math.random()*.3;if(v>.255+(u-.33)*.27&&u<.6)continue;TS.push({x:ox+u*iw,y:oy+v*ih,r:(Math.random()*1.5+.5)*d,p:Math.random()*6.28,s:Math.random()*1.3+.4});}}
function tLoop(t){if(!tOn)return;requestAnimationFrame(tLoop);
  if(t-lastMove>2600){const q=(t-tStart)*.00035;gx=tW*(.5+.34*Math.sin(q*1.3));gy=tH*(.5+.26*Math.sin(q*2.1+1));}
  px+=(gx-px)*.12;py+=(gy-py)*.12;
  if(exitT){const k=Math.min(1,(t-exitT)/1100),e=k*k*(3-2*k),D=Math.hypot(tW,tH)*1.1,m2=mkC.getContext('2d'),rr=Math.max(1,e*D),g2=m2.createRadialGradient(ex,ey,rr*.6,ex,ey,rr);g2.addColorStop(0,'#fff');g2.addColorStop(1,'rgba(255,255,255,0)');m2.globalCompositeOperation='source-over';m2.fillStyle=g2;m2.fillRect(0,0,tW,tH);m2.fillStyle='#fff';m2.beginPath();m2.arc(ex,ey,rr*.6,0,6.28);m2.fill();
    if(k>=1&&document.body.classList.contains('lo'))lightsOn();}
  const m=mkC.getContext('2d');m.globalCompositeOperation='destination-out';m.fillStyle=exitT?'rgba(0,0,0,0)':'rgba(0,0,0,.028)';m.fillRect(0,0,tW,tH);
  m.globalCompositeOperation='source-over';const R=Math.min(tW,tH)*.26,rg=m.createRadialGradient(px,py,0,px,py,R);rg.addColorStop(0,'rgba(255,255,255,.55)');rg.addColorStop(.55,'rgba(255,255,255,.22)');rg.addColorStop(1,'rgba(255,255,255,0)');m.fillStyle=rg;m.fillRect(px-R,py-R,2*R,2*R);
  tg.globalCompositeOperation='source-over';tg.globalAlpha=1;tg.drawImage(dkC,0,0);
  tg.fillStyle='#fff';for(const s of TS){const a=Math.pow(Math.max(0,Math.sin(s.p+t*.0012*s.s)),3);if(a<.05)continue;tg.globalAlpha=a*.9;tg.beginPath();tg.arc(s.x,s.y,s.r,0,6.28);tg.fill();}
  tg.globalAlpha=1;const c=tmC.getContext('2d');c.globalCompositeOperation='source-over';c.clearRect(0,0,tW,tH);c.drawImage(brC,0,0);c.globalCompositeOperation='destination-in';c.drawImage(mkC,0,0);tg.drawImage(tmC,0,0);
  tg.globalCompositeOperation='lighter';const gl=tg.createRadialGradient(px,py,0,px,py,R*.7);gl.addColorStop(0,'rgba(176,102,255,.16)');gl.addColorStop(1,'rgba(176,102,255,0)');tg.fillStyle=gl;tg.fillRect(px-R,py-R,2*R,2*R);tg.globalCompositeOperation='source-over';drawHot(t);drawStory(t);}
/* lights out: hidden hotspots + hot tub glow */
const HS=[{u:.865,v:.665,t:'Vířivka pro dva'},{u:.70,v:.80,t:'King Size'},{u:.45,v:.47,t:'Kino jen pro vás'},{u:.38,v:.70,t:'Prostorový zvuk'},{u:.62,v:.12,t:'Hvězdné nebe'}];
const TUB={x:0,y:0,rx:1,ry:1,a:0};
HS.forEach(h=>{const el=document.createElement('span');el.className='dk-hs';el.textContent=h.t;el.setAttribute('aria-hidden','true');dark.appendChild(el);h.el=el;h.a=0;h.shown=-1;});
function drawHot(t){
  const R=Math.min(tW,tH)*.26,fade=exitT?1-Math.min(1,(t-exitT)/600):1;
  HS.forEach(h=>{const near=h.on&&Math.hypot(px-h.x,py-h.y)<R*.5;h.a+=((near?1:0)-h.a)*(near?.09:.035);const v=Math.round(h.a*fade*100)/100;if(v!==h.shown){h.shown=v;h.el.style.opacity=v;h.el.style.transform=`translate(-50%,calc(-100% - ${10+8*(1-v)}px))`;}});
  const nearT=Math.hypot(px-TUB.x,py-TUB.y)<R*.6;TUB.a+=((nearT?1:0)-TUB.a)*(nearT?.06:.008);const A=TUB.a*fade;if(A<.01)return;
  tg.save();tg.globalCompositeOperation='lighter';
  const g=tg.createRadialGradient(TUB.x,TUB.y,0,TUB.x,TUB.y,TUB.rx*2.4);g.addColorStop(0,`rgba(190,110,255,${.5*A})`);g.addColorStop(.5,`rgba(140,70,255,${.18*A})`);g.addColorStop(1,'rgba(120,60,255,0)');tg.fillStyle=g;tg.fillRect(TUB.x-TUB.rx*2.4,TUB.y-TUB.rx*2.4,TUB.rx*4.8,TUB.rx*4.8);
  for(let i=0;i<11;i++){const an=Math.PI*(.08+.84*i/10),x=TUB.x-Math.cos(an)*TUB.rx,y=TUB.y+Math.sin(an)*TUB.ry*.9,tw=.55+.45*Math.sin(t*.004+i*1.7),r=Math.max(2,TUB.rx*.09);
    const s=tg.createRadialGradient(x,y,0,x,y,r*3);s.addColorStop(0,`rgba(255,255,255,${A*tw})`);s.addColorStop(.3,`rgba(215,170,255,${.7*A*tw})`);s.addColorStop(1,'rgba(176,102,255,0)');tg.fillStyle=s;tg.fillRect(x-r*3,y-r*3,r*6,r*6);
    tg.strokeStyle=`rgba(230,200,255,${.35*A*tw})`;tg.lineWidth=1;tg.beginPath();tg.moveTo(x-r*4,y);tg.lineTo(x+r*4,y);tg.moveTo(x,y-r*4);tg.lineTo(x,y+r*4);tg.stroke();}
  tg.restore();}
/* lights out story: LED line → constellation cube → Gold Time */
const SV=[];for(let i=0;i<6;i++){const a=(-90+60*i)*Math.PI/180;SV.push([Math.cos(a),Math.sin(a)]);}SV.push([0,0]);
const SE=[[0,1],[1,2],[2,3],[3,4],[4,5],[5,0],[6,5],[6,1],[6,3],[5,3]];
let SS=[];const ez=k=>k<=0?0:k>=1?1:k*k*(3-2*k);
function storyInit(){SS=SV.map(()=>({x:tW*(.15+Math.random()*.7),y:tH*(.05+Math.random()*.35)}));}
function drawStory(t){
  const d=tW/innerWidth,e=(t-tStart)/1000,fade=exitT?1-Math.min(1,(t-exitT)/700):1;if(fade<=0||!SS.length)return;
  tg.save();tg.globalCompositeOperation='lighter';tg.lineCap='round';
  const L=ez((e-.2)/1.8);
  if(L>0){const ins=10*d,w=tW-2*ins,h=tH-2*ins,P=2*(w+h),br=.8+.2*Math.sin(t*.0015);
    tg.setLineDash([P*L,P]);
    tg.strokeStyle=`rgba(176,102,255,${.16*br*fade})`;tg.lineWidth=9*d;tg.strokeRect(ins,ins,w,h);
    tg.strokeStyle=`rgba(200,160,255,${.85*br*fade})`;tg.lineWidth=1.6*d;tg.strokeRect(ins,ins,w,h);
    tg.setLineDash([]);}
  const sa=ez((e-1.1)/.6);
  if(sa>0){const cx=tW/2,cy=tH/2,R=Math.max(Math.min(tW,tH)*.11,62*d),k=ez((e-1.3)/1.8),pulse=.85+.15*Math.sin(t*.002);
    const P=SV.map((v,i)=>[SS[i].x+(cx+v[0]*R-SS[i].x)*k,SS[i].y+(cy+v[1]*R-SS[i].y)*k]);
    SE.forEach(([a,b],i)=>{const lk=ez((e-3.1-i*.1)/.5);if(lk<=0)return;const A=P[a],B=P[b];
      tg.strokeStyle=`rgba(200,170,255,${(i===9?.35:.6)*pulse*fade})`;tg.lineWidth=1.2*d;
      tg.beginPath();tg.moveTo(A[0],A[1]);tg.lineTo(A[0]+(B[0]-A[0])*lk,A[1]+(B[1]-A[1])*lk);tg.stroke();});
    P.forEach(([x,y])=>{const r=9*d,g=tg.createRadialGradient(x,y,0,x,y,r);g.addColorStop(0,`rgba(255,255,255,${sa*fade})`);g.addColorStop(.25,`rgba(220,190,255,${.6*sa*fade})`);g.addColorStop(1,'rgba(176,102,255,0)');tg.fillStyle=g;tg.fillRect(x-r,y-r,2*r,2*r);});}
  tg.restore();}
function lightsOff(){exitT=0;document.body.classList.add('lo');document.body.classList.remove('moved');setTimeout(()=>document.body.classList.remove('going'),800);if(tKey!==innerWidth+'x'+innerHeight)tSize();mkC.getContext('2d').clearRect(0,0,tW,tH);
  const d=tW/innerWidth;gx=px=tW/2;gy=py=tH*.55;lastMove=0;tStart=performance.now();if(reduce)tStart-=8000;storyInit();TUB.a=0;HS.forEach(h=>h.a=0);tOn=true;requestAnimationFrame(tLoop);
  document.getElementById('dkHint').textContent=matchMedia('(hover:none)').matches?'Posviťte si prstem · klepnutím rozsvítíte':'Posviťte si myší · kliknutím rozsvítíte';document.getElementById('dkClose').focus({preventScroll:true});}
function lightsOn(){document.body.classList.remove('lo');document.getElementById('lightsBtn').classList.remove('off');ciBox.classList.remove('dim');setTimeout(()=>{if(!document.body.classList.contains('lo')){tOn=false;exitT=0;}},1300);}
function startExit(x,y){if(exitT)return;const d=tW/innerWidth;ex=x*d;ey=y*d;exitT=performance.now();}
dark.addEventListener('pointermove',e=>{const d=tW/innerWidth;gx=e.clientX*d;gy=e.clientY*d;lastMove=performance.now();document.body.classList.add('moved');});
dark.addEventListener('pointerdown',e=>{const d=tW/innerWidth;gx=e.clientX*d;gy=e.clientY*d;lastMove=performance.now();dnX=e.clientX;dnY=e.clientY;dnT=performance.now();});
dark.addEventListener('pointerup',e=>{if(e.target.closest('.dk-close'))return;if(Math.hypot(e.clientX-dnX,e.clientY-dnY)<12&&performance.now()-dnT<400)startExit(e.clientX,e.clientY);});
addEventListener('resize',()=>{if(tOn){tSize();storyInit();}});
{const warm=()=>{const f=()=>{if(!tOn&&tImg.width)tSize();};if(window.requestIdleCallback)requestIdleCallback(f,{timeout:2000});else setTimeout(f,1500);};if(tImg.complete)warm();else tImg.addEventListener('load',warm);}
function clickSnd(){try{if(navigator.vibrate)navigator.vibrate(18);}catch(e){}}
const ciBox=document.querySelector('.concept-img'),ciSt=ciBox.querySelector('.ci-stars');
for(let i=0;i<46;i++){const st=document.createElement('i');st.style.left=(4+Math.random()*92)+'%';st.style.top=(3+Math.random()*34)+'%';st.style.animationDelay=(-Math.random()*2.6)+'s';if(Math.random()<.3){st.style.width=st.style.height='3px';}ciSt.appendChild(st);}
document.getElementById('lightsBtn').addEventListener('click',e=>{const b=e.currentTarget;clickSnd();b.classList.add('off');ciBox.classList.add('dim');setTimeout(()=>{document.body.classList.add('going');setTimeout(()=>{if(tImg.complete)lightsOff();else tImg.onload=lightsOff;},950);},150);});
document.getElementById('dkClose').addEventListener('click',e=>{clickSnd();const r=e.currentTarget.getBoundingClientRect();startExit(r.left+r.width/2,r.top+r.height/2);});
addEventListener('keydown',e=>{if(e.key==='Escape'&&document.body.classList.contains('lo'))startExit(innerWidth/2,innerHeight/2);});

/* ambient sound: real track (atmosfera.mp3), starts only on click */
let actx,master,playing=false,an=null,fbins=null,offT=null;const snd=document.getElementById('snd');
const bgA=new Audio('atmosfera-f.mp3');bgA.loop=true;bgA.preload='none';bgA.crossOrigin='anonymous';
function initSound(){actx=new (window.AudioContext||window.webkitAudioContext)();master=actx.createGain();master.gain.value=0;
  an=actx.createAnalyser();an.fftSize=128;an.smoothingTimeConstant=.8;fbins=new Uint8Array(an.frequencyBinCount);
  actx.createMediaElementSource(bgA).connect(master);master.connect(an);an.connect(actx.destination);}
function soundOn(){if(!actx)initSound();clearTimeout(offT);actx.resume();const n=actx.currentTime;
  master.gain.cancelScheduledValues(n);master.gain.setValueAtTime(master.gain.value,n);master.gain.linearRampToValueAtTime(.9,n+2.5);
  const pr=bgA.play();if(pr&&pr.catch)pr.catch(()=>{playing=false;syncSound();});}
function soundOff(){const n=actx.currentTime;master.gain.cancelScheduledValues(n);master.gain.setValueAtTime(master.gain.value,n);master.gain.linearRampToValueAtTime(0,n+1.5);
  clearTimeout(offT);offT=setTimeout(()=>{if(!playing){bgA.pause();actx.suspend();}},1700);}
const player=document.getElementById('player'),plIco=document.getElementById('plIco'),plBar=document.getElementById('plBar'),plCur=document.getElementById('plCur');
player.querySelector('.viz').innerHTML=Array.from({length:28},(_,i)=>`<span style="animation-delay:${(-Math.random()).toFixed(2)}s;animation-duration:${(.6+Math.random()*.7).toFixed(2)}s"></span>`).join('');
let plLast=0;function plTick(){if(!playing)return;const L=bgA.duration||62,s=bgA.currentTime%L;plBar.style.width=(s/L*100)+'%';plCur.textContent=Math.floor(s/60)+':'+String(Math.floor(s%60)).padStart(2,'0');requestAnimationFrame(plTick);}
function syncSound(){const ms=document.getElementById('mSnd');if(ms){ms.classList.toggle('on',playing);ms.setAttribute('aria-pressed',playing);ms.querySelector('.mt').textContent=playing?'Vypnout atmosféru':'Zapnout atmosféru';}snd.classList.toggle('on',playing);snd.setAttribute('aria-pressed',playing);player.classList.toggle('on',playing);
  plIco.innerHTML=playing?'<path d="M7 5h4v14H7zM13 5h4v14h-4z"/>':'<path d="M7 5v14l12-7z"/>';document.getElementById('plBtn').setAttribute('aria-label',playing?'Pozastavit ukázku':'Přehrát ukázku atmosféry');
  if(playing){plLast=performance.now();requestAnimationFrame(plTick);}}
function toggleSound(){try{playing=!playing;playing?soundOn():soundOff();syncSound();}catch(e){}}
snd.addEventListener('click',toggleSound);document.getElementById('mSnd').addEventListener('click',toggleSound);document.getElementById('plBtn').addEventListener('click',toggleSound);
const vizS=[...player.querySelectorAll('.viz span')],moodB=[...document.querySelectorAll('.mood')];
moodB.forEach(b=>b.addEventListener('click',()=>{moodB.forEach(x=>x.setAttribute('aria-checked',x===b));if(!playing)toggleSound();}));
(function react(){requestAnimationFrame(react);let e=0;if(playing&&an){an.getByteFrequencyData(fbins);let s=0;for(let i=1;i<10;i++)s+=fbins[i];e=Math.min(1,s/9/170);
  vizS.forEach((v,i)=>{const b=fbins[1+Math.floor(i*(fbins.length*.6)/vizS.length)]||0;v.style.height=(12+b/255*88).toFixed(0)+'%';});}
  else if(window.bbE>0.001)vizS.forEach(v=>v.style.height='15%');
  window.bbE=(window.bbE||0)+(e-(window.bbE||0))*.15;player.style.setProperty('--e',window.bbE.toFixed(3));})();

/* stagger reveal for blocks (visible at rest; only moves) */
if('IntersectionObserver' in window&&!reduce){const io2=new IntersectionObserver(es=>es.forEach(e=>{if(e.isIntersecting){e.target.classList.remove('pre');io2.unobserve(e.target);}}),{threshold:.1});
  document.querySelectorAll('.rv,.fact,.hr,.pill,.slot').forEach(el=>{el.classList.add('rv');const sib=[...el.parentElement.children].indexOf(el);el.style.setProperty('--d',(sib*.09)+'s');if(el.getBoundingClientRect().top>innerHeight){el.classList.add('pre');io2.observe(el);}});}
/* mobile gallery carousel: auto-advances, swipe, arrows, dots */
(function(){const G=document.getElementById('gal'),N=document.getElementById('galNav'),D=N.querySelector('.gdots'),items=[...G.children],DUR=1800,HOLD=5000;
  D.innerHTML=items.map(()=>'<i></i>').join('');const dots=[...D.children];let cur=0,inView=false,anim=false,nextAt=0,holdUntil=0,touching=false,stopped=false;
  const gp=document.getElementById('gPause');const setG=s=>{stopped=s;gp.setAttribute('aria-pressed',s);gp.setAttribute('aria-label',s?'Spustit automatické přepínání':'Zastavit automatické přepínání');gp.innerHTML=s?'<svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 5v14l12-7z"/></svg>':'<svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 5h4v14H7zM13 5h4v14h-4z"/></svg>';D.classList.toggle('paused',s);if(!s)nextAt=performance.now()+DUR;};gp.addEventListener('click',()=>setG(!stopped));
  const mob=()=>innerWidth<=760,now=()=>performance.now();
  function mark(i){cur=i;dots.forEach((d,j)=>d.classList.toggle('on',j===i));const a=dots[i];a.classList.remove('on');void a.offsetWidth;a.classList.add('on');}
  const posOf=i=>{const b=items[i];return Math.max(0,Math.min(G.scrollWidth-G.clientWidth,b.offsetLeft-(G.clientWidth-b.clientWidth)/2));};
  function go(i,user){i=(i+items.length)%items.length;mark(i);const to=posOf(i),from=G.scrollLeft,t0=now();anim=true;G.style.scrollSnapType='none';
    (function an(n){const k=Math.min(1,(n-t0)/420),e=1-Math.pow(1-k,3);G.scrollLeft=from+(to-from)*e;if(k<1)requestAnimationFrame(an);else{G.scrollLeft=to;requestAnimationFrame(()=>{G.style.scrollSnapType='';anim=false;});}})(t0);
    nextAt=now()+DUR;if(user)hold();}
  function hold(){holdUntil=now()+HOLD;nextAt=holdUntil;D.classList.add('paused');}
  setInterval(()=>{if(!mob()||!inView||anim||touching||stopped)return;const n=now();if(holdUntil&&n>=holdUntil){holdUntil=0;D.classList.remove('paused');mark(cur);nextAt=n+DUR;return;}if(!holdUntil&&n>=nextAt)go(cur+1);},120);
  N.querySelector('.gprev').addEventListener('click',()=>go(cur-1,true));N.querySelector('.gnext').addEventListener('click',()=>go(cur+1,true));
  dots.forEach((d,i)=>d.addEventListener('click',()=>go(i,true)));
  G.addEventListener('touchstart',()=>{touching=true;hold();},{passive:true});
  G.addEventListener('touchend',()=>{touching=false;hold();},{passive:true});G.addEventListener('touchcancel',()=>{touching=false;},{passive:true});
  let st;G.addEventListener('scroll',()=>{if(anim)return;clearTimeout(st);st=setTimeout(()=>{if(!mob()||anim)return;const c=G.scrollLeft+G.clientWidth/2;let best=0,bd=1e9;items.forEach((b,j)=>{const d=Math.abs(b.offsetLeft+b.clientWidth/2-c);if(d<bd){bd=d;best=j;}});if(best!==cur)mark(best);},90);},{passive:true});
  D.style.setProperty('--gd',DUR+'ms');mark(0);nextAt=now()+DUR;if(stopped)setG(true);
  new IntersectionObserver(es=>{inView=es[0].isIntersecting;if(inView){nextAt=now()+DUR;mark(cur);}},{threshold:.4}).observe(G);
})();
/* mobile gallery: highlight centred card */
const galEl=document.getElementById('gal');galEl.addEventListener('scroll',()=>{if(innerWidth>760)return;const c=galEl.scrollLeft+galEl.clientWidth/2;[...galEl.children].forEach(b=>b.classList.toggle('side',Math.abs(b.offsetLeft+b.clientWidth/2-c)>b.clientWidth*.5));},{passive:true});
galEl.dispatchEvent(new Event('scroll'));
/* reveal (visible at rest; only slides) */
if('IntersectionObserver' in window&&!reduce){const io=new IntersectionObserver(es=>es.forEach(e=>{if(e.isIntersecting){e.target.classList.remove('pre');io.unobserve(e.target);}}),{threshold:.12});
  document.querySelectorAll('.reveal').forEach(el=>{if(el.getBoundingClientRect().top>innerHeight){el.classList.add('pre');io.observe(el);}});}

/* vouchers */
(function(){const vc=document.getElementById('vcard'),o=[document.getElementById('vo1'),document.getElementById('vo2')];
  ['vFor','vMsg'].forEach(id=>{const e=document.getElementById(id);e.addEventListener('input',()=>vc.classList.toggle('typed',!!(document.getElementById('vFor').value.trim()||document.getElementById('vMsg').value.trim())));e.addEventListener('keydown',ev=>{if(ev.key==='Enter'){ev.preventDefault();e.blur();}});});
  o.forEach(b=>b.addEventListener('click',()=>{o.forEach(x=>x.setAttribute('aria-pressed',x===b));vc.classList.toggle('gold',b.id==='vo2');document.getElementById('vKind').textContent=b.dataset.k;document.getElementById('vBlock').textContent=b.dataset.b;}));
  if(matchMedia('(pointer:fine)').matches&&!reduce){vc.addEventListener('mousemove',e=>{if(document.activeElement&&vc.contains(document.activeElement)&&document.activeElement.tagName==='INPUT'){vc.style.transform='';return;}const r=vc.getBoundingClientRect(),x=(e.clientX-r.left)/r.width-.5,y=(e.clientY-r.top)/r.height-.5;vc.style.transform=`perspective(1000px) rotateY(${x*10}deg) rotateX(${-y*10}deg)`;});vc.addEventListener('mouseleave',()=>vc.style.transform='');}
  /* objednávka poukazu → modal */
  const D=document.getElementById('voDlg'),F=document.getElementById('voForm'),$=id=>document.getElementById(id);
  const FLOW={odber:['Odešlete objednávku.','Ozveme se a domluvíme čas vyzvednutí.','Zaplatíte na místě a převezmete poukaz.'],
              faktura:['Odešlete objednávku.','Fakturu vám hned pošleme e-mailem.','Po připsání platby přijde potvrzení o platbě a poukaz.']};
  const LAST='Termín návštěvy si obdarovaný domluví telefonem na <a href="tel:+420737222266">+420 737 22 22 66</a>.';
  const way=()=>F.querySelector('input[name="voWay"]:checked').value;
  function paint(){const w=way();$('voInv').hidden=w!=='faktura';$('voAddr').required=w==='faktura';
    $('voFlow').innerHTML=FLOW[w].map(t=>'<li>'+t+'</li>').join('')+'<li class="vo-last">'+LAST+'</li>';}
  F.querySelectorAll('input[name="voWay"]').forEach(r=>r.addEventListener('change',paint));
  let voScrollY=0;
  function openDlg(){const g=$('vo2').getAttribute('aria-pressed')==='true',btn=g?$('vo2'):$('vo1');
    $('voKind').textContent=$('vKind').textContent;$('voPrice').textContent=(btn.querySelector('span').textContent.split('·').pop()||'').trim();
    const f=$('vFor').value.trim(),m=$('vMsg').value.trim();$('voFor').textContent=[f&&'Pro: '+f,m&&'„'+m+'“'].filter(Boolean).join(' · ');
    D.classList.toggle('is-gold',g);$('voStep').hidden=false;$('voDone').hidden=true;$('voErr').textContent='';paint();
    voScrollY=window.scrollY||document.documentElement.scrollTop||0;
    document.body.style.top=(-voScrollY)+'px';
    document.documentElement.classList.add('vo-lock');if(D.showModal)D.showModal();else D.setAttribute('open','');D.scrollTop=0;if(matchMedia('(min-width:601px)').matches)setTimeout(()=>$('voName').focus({preventScroll:true}),60);}
  function closeDlg(){if(D.close)D.close();else D.removeAttribute('open');}
  D.addEventListener('close',()=>{document.documentElement.classList.remove('vo-lock');document.body.style.top='';window.scrollTo(0,voScrollY);});
  D.addEventListener('click',e=>{if(e.target===D)closeDlg();});
  $('voX').addEventListener('click',closeDlg);$('voOk').addEventListener('click',closeDlg);
  F.querySelectorAll('input').forEach(i=>i.addEventListener('input',()=>i.closest('.field')&&i.closest('.field').classList.remove('vo-field-bad')));
  const vBtn=document.querySelector('#vform button[type=submit]'),vF=$('vFor');
  const syncBtn=()=>{const ok=!!vF.value.trim();vBtn.classList.toggle('is-off',!ok);vBtn.setAttribute('aria-disabled',String(!ok));if(ok){$('vnote').textContent='';vc.classList.remove('need-name');}};
  vF.addEventListener('input',syncBtn);syncBtn();
  document.getElementById('vform').addEventListener('submit',e=>{e.preventDefault();
    if(!vF.value.trim()){$('vnote').textContent='Nejdřív napište do poukazu jméno obdarovaného.';vc.classList.remove('need-name');void vc.offsetWidth;vc.classList.add('need-name');vc.scrollIntoView({behavior:'smooth',block:'center'});setTimeout(()=>vF.focus({preventScroll:true}),350);return;}
    $('vnote').textContent='';openDlg();});
  F.addEventListener('submit',e=>{e.preventDefault();const w=way(),err=$('voErr');let bad=null;
    const need=['voName','voTel','voMail'].concat(w==='faktura'?['voAddr']:[]);
    need.forEach(id=>{const i=$(id),ok=id==='voMail'?/^\S+@\S+\.\S+$/.test(i.value.trim()):!!i.value.trim();i.closest('.field').classList.toggle('vo-field-bad',!ok);if(!ok&&!bad)bad=i;});
    if(bad){err.textContent='Doplňte prosím zvýrazněné údaje.';bad.scrollIntoView({block:'center',behavior:'smooth'});bad.focus({preventScroll:true});return;}err.textContent='';
    const v=id=>$(id).value.trim(),k=$('vKind').textContent;
    const lines=['Dobrý den,','','mám zájem o dárkový poukaz.','',
      'Typ: '+k+' ('+$('voPrice').textContent+')','Pro koho: '+(v('vFor')||'—'),'Věnování: '+(v('vMsg')||'—'),'',
      'Způsob převzetí: '+(w==='faktura'?'Poslat fakturu (platba převodem)':'Osobní odběr na místě'),'',
      'Jméno: '+v('voName'),'Telefon: '+v('voTel'),'E-mail: '+v('voMail')];
    if(w==='faktura'){lines.push('Fakturační adresa: '+v('voAddr'));if(v('voIco'))lines.push('Firma / IČO: '+v('voIco'));}
    lines.push('','Děkuji');
    const viaMail=()=>{location.href='mailto:'+'pavlatp@email.cz'+'?subject='+encodeURIComponent('Objednávka poukazu – '+k+' – '+(w==='faktura'?'faktura':'osobní odběr'))+'&body='+encodeURIComponent(lines.join('\n'));
};
    const done=(h,p)=>{$('voDoneH').textContent=h;$('voDoneP').innerHTML=p;$('voStep').hidden=true;$('voDone').hidden=false;D.scrollTop=0;$('voOk').focus({preventScroll:true});};
    const esc=t=>t.replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
    const send=$('voSend');if(send.disabled)return;send.disabled=true;const lbl=send.textContent;send.textContent='Odesílám…';
    const payload={way:w,kind:$('vo2').getAttribute('aria-pressed')==='true'?'gold':'standard',name:v('voName'),phone:v('voTel'),email:v('voMail'),addr:v('voAddr'),company:v('voIco'),for:v('vFor'),msg:v('vMsg'),web:$('voWeb').value};
    const ctl=new AbortController(),tm=setTimeout(()=>ctl.abort(),30000);
    fetch('objednavka.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload),signal:ctl.signal})
      .then(r=>r.json().catch(()=>({ok:false,_fallback:true})).then(j=>({r,j})))
      .then(({r,j})=>{
        if(j.ok){const m=esc(v('voMail'));
          if(j.mode==='invoice')done('Faktura je na cestě','Poslali jsme ji na <b>'+m+'</b>. Po připsání platby vám přijde potvrzení o platbě a pak si zavoláte o termín.');
          else if(j.mode==='manual')done('Objednávka přijata','Fakturu vám brzy pošleme na <b>'+m+'</b>. Po zaplacení přijde potvrzení o platbě.');
          else done('Objednávka přijata','Potvrzení jsme poslali na <b>'+m+'</b>. Brzy se ozveme a domluvíme vyzvednutí.');
          return;}
        if(r.status===422||r.status===429){err.textContent=j.error||'Zkontrolujte prosím údaje.';return;}
        throw new Error('server');})
      .catch(()=>{viaMail();done('Skoro hotovo','Otevřeli jsme vám e-mail s objednávkou. Stačí ho odeslat a my se vám obratem ozveme.');})
      .finally(()=>{clearTimeout(tm);send.disabled=false;send.textContent=lbl;});});
})();
/* faq: one open at a time */
(function(){const Q=[...document.querySelectorAll('.qa')];
  const shut=d=>{if(!d.open)return;d.classList.remove('show');clearTimeout(d._t);d._t=setTimeout(()=>{if(!d.classList.contains('show'))d.open=false;},720);};
  const openQ=d=>{clearTimeout(d._t);d.open=true;requestAnimationFrame(()=>requestAnimationFrame(()=>d.classList.add('show')));};
  Q.forEach(d=>{if(d.open)d.classList.add('show');d.querySelector('summary').addEventListener('click',e=>{e.preventDefault();if(d.classList.contains('show'))shut(d);else{Q.forEach(x=>{if(x!==d)shut(x);});openQ(d);}});});})();

/* spotlight hover */
document.querySelectorAll('.spec,.step,.for,.player,.vopt,.fact,.feat,.slot,.hr,.book-card .field').forEach(el=>{if(!el.classList.contains('field'))el.classList.add('sl');});
const SLS=new Map();let slRun=false;
function slLoop(){let active=false;SLS.forEach((s,el)=>{s.x+=(s.tx-s.x)*.09;s.y+=(s.ty-s.y)*.09;el.style.setProperty('--mx',s.x.toFixed(1)+'px');el.style.setProperty('--my',s.y.toFixed(1)+'px');if(Math.abs(s.tx-s.x)+Math.abs(s.ty-s.y)>.5)active=true;else if(!s.in)SLS.delete(el);});slRun=active||[...SLS.values()].some(s=>s.in);if(slRun)requestAnimationFrame(slLoop);}
document.addEventListener('pointermove',e=>{const el=e.target.closest&&e.target.closest('.sl');SLS.forEach((s,k)=>{if(k!==el)s.in=false;});if(!el)return;const r=el.getBoundingClientRect(),x=e.clientX-r.left,y=e.clientY-r.top;
  let s=SLS.get(el);if(!s){s={x,y,tx:x,ty:y,in:true};SLS.set(el,s);}s.tx=x;s.ty=y;s.in=true;if(!slRun){slRun=true;requestAnimationFrame(slLoop);}},{passive:true});
/* mobile menu */
(function(){const b=document.getElementById('burger'),m=document.getElementById('mmenu');
  const set=o=>{document.body.classList.toggle('menu',o);b.setAttribute('aria-expanded',o);b.setAttribute('aria-label',o?'Zavřít menu':'Otevřít menu');m.setAttribute('aria-hidden',!o);};
  b.addEventListener('click',()=>set(!document.body.classList.contains('menu')));
  m.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>set(false)));
  addEventListener('keydown',e=>{if(e.key==='Escape'&&document.body.classList.contains('menu'))set(false);});
  addEventListener('resize',()=>{if(innerWidth>960)set(false);});})();

/* gallery lightbox */
(function(){
const lb=document.getElementById('lb'),lbImg=document.getElementById('lbImg'),view=document.getElementById('lbView'),cnt=document.getElementById('lbCount'),th=document.getElementById('lbThumbs');
const gal=[...document.querySelectorAll('#gal button img')];
const IM=gal.map(i=>({src:i.currentSrc||i.src,alt:i.alt}));
let idx=0,zoomed=false,holdT=null,down=null,Z=2.4;
th.innerHTML=IM.map((m,i)=>`<button type="button" aria-label="Fotka ${i+1}"><img src="${m.src}" alt=""></button>`).join('');
const thumbs=[...th.children];thumbs.forEach((b,i)=>b.addEventListener('click',e=>{e.stopPropagation();go(i);}));
document.getElementById('lbHint').textContent=matchMedia('(hover:none)').matches?'Podržte prst pro přiblížení · přejeďte pro další fotku':'Podržte myš pro přiblížení · šipky pro další fotku';
function paint(){lbImg.src=IM[idx].src;lbImg.alt=IM[idx].alt;cnt.innerHTML=`<b>${String(idx+1).padStart(2,'0')}</b> / ${String(IM.length).padStart(2,'0')}`;thumbs.forEach((b,i)=>b.classList.toggle('on',i===idx));}
function go(i){unzoom();idx=(i+IM.length)%IM.length;lbImg.classList.add('fade');setTimeout(()=>{paint();requestAnimationFrame(()=>lbImg.classList.remove('fade'));},180);}
function open(i){idx=i;paint();lb.hidden=false;document.body.style.overflow='hidden';document.getElementById('lbNext').focus({preventScroll:true});}
function close(){unzoom();lb.hidden=true;document.body.style.overflow='';}
function origin(x,y){const r=view.getBoundingClientRect(),ox=Math.max(0,Math.min(100,(x-r.left)/r.width*100)),oy=Math.max(0,Math.min(100,(y-r.top)/r.height*100));lbImg.style.transformOrigin=`${ox}% ${oy}%`;}
function zoom(x,y){zoomed=true;origin(x,y);view.classList.add('zoom');lbImg.style.transform=`scale(${Z})`;}
function unzoom(){zoomed=false;clearTimeout(holdT);view.classList.remove('zoom');lbImg.style.transform='';}
gal.forEach((im,i)=>im.parentElement.addEventListener('click',()=>open(i)));
document.getElementById('lbPrev').addEventListener('click',e=>{e.stopPropagation();go(idx-1);});
document.getElementById('lbNext').addEventListener('click',e=>{e.stopPropagation();go(idx+1);});
document.getElementById('lbClose').addEventListener('click',e=>{e.stopPropagation();close();});
lb.addEventListener('click',e=>{if(e.target===lb)close();});
view.addEventListener('pointerdown',e=>{e.preventDefault();down={x:e.clientX,y:e.clientY,t:performance.now()};try{view.setPointerCapture(e.pointerId);}catch(_){}
  holdT=setTimeout(()=>zoom(e.clientX,e.clientY),e.pointerType==='mouse'?140:260);});
view.addEventListener('pointermove',e=>{if(!down)return;if(zoomed){origin(e.clientX,e.clientY);return;}
  if(Math.hypot(e.clientX-down.x,e.clientY-down.y)>12)clearTimeout(holdT);});
function up(e){if(!down)return;clearTimeout(holdT);const dx=e.clientX-down.x,dy=e.clientY-down.y;
  if(!zoomed&&Math.abs(dx)>50&&Math.abs(dx)>Math.abs(dy))go(idx+(dx<0?1:-1));
  unzoom();down=null;}
view.addEventListener('pointerup',up);view.addEventListener('pointercancel',up);view.addEventListener('contextmenu',e=>e.preventDefault());
addEventListener('keydown',e=>{if(lb.hidden)return;if(e.key==='Escape')close();if(e.key==='ArrowRight')go(idx+1);if(e.key==='ArrowLeft')go(idx-1);});
})();


})();



/* a11y: pause controls + reduced motion */
(function(){const RM=false;window.bbRM=RM;
  const M=document.getElementById('marq'),mb=document.getElementById('mqPause');
  const setM=p=>{M.classList.toggle('paused',p);mb.setAttribute('aria-pressed',p);mb.setAttribute('aria-label',p?'Spustit běžící text':'Zastavit běžící text');mb.innerHTML=p?'<svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 5v14l12-7z"/></svg>':'<svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 5h4v14H7zM13 5h4v14h-4z"/></svg>';};
  mb.addEventListener('click',()=>setM(!M.classList.contains('paused')));if(RM)setM(true);
})();
}catch(e){console.error(e)}
;
try{
(function(){const s=document.getElementById('goldtime'),r=document.documentElement;if(!s||!('IntersectionObserver' in window))return;
let q=0;const w=document.getElementById('goldWash');const chk=()=>{q=0;const b=s.getBoundingClientRect(),vh=innerHeight;const mg=document.querySelector('.gt-mega'),mt=mg?mg.getBoundingClientRect().top:b.top;const pv=document.getElementById('navsteva'),pb=pv?pv.getBoundingClientRect().bottom:0,nb=(document.getElementById('nav')||{getBoundingClientRect:()=>({bottom:0})}).getBoundingClientRect().bottom;r.classList.toggle('gold',(mt<vh*.45||pb<nb+40)&&pb<nb+140&&(()=>{const fe=document.querySelector('.gt-feats');return (fe?fe.getBoundingClientRect().bottom:b.bottom)>vh*.5;})());const f=document.querySelector('.gt-film')||document.getElementById('gtBld');if(f&&w&&!window.__gwStatic){const c=f.getBoundingClientRect(),nv=document.getElementById('nav'),hb=nv?nv.getBoundingClientRect().bottom:0,top=Math.max(c.top,hb),hh=Math.max(0,c.bottom-top);w.style.setProperty('--fx',c.left+'px');w.style.setProperty('--fy',top+'px');w.style.setProperty('--fw',c.width+'px');w.style.setProperty('--fh',hh+'px');}};addEventListener('scroll',()=>{if(!q)q=requestAnimationFrame(chk);},{passive:true});addEventListener('resize',chk);chk();})();
}catch(e){console.error(e)}
;
try{
(function(){const b=document.getElementById('gtBld');if(!b||!('IntersectionObserver' in window)){b&&b.classList.add('play');return;}
new IntersectionObserver(es=>es.forEach(e=>{if(e.intersectionRatio>=.5)b.classList.add('play');else if(!e.isIntersecting)b.classList.remove('play');}),{threshold:[0,.5]}).observe(b);})();
}catch(e){console.error(e)}
;
try{
(function(){const w=document.getElementById('goldWash'),c=document.querySelector('#gtBld .bb-win'),r=document.documentElement;if(!w||!c)return;let on=false;
function f(){if(window.bb3dGold){if(on)requestAnimationFrame(f);return;}const b=c.getBoundingClientRect();w.style.setProperty('--hx',(b.left+b.width/2)+'px');w.style.setProperty('--hy',(b.top+b.height/2)+'px');w.style.setProperty('--hr',(Math.max(b.width,b.height)*.75)+'px');if(on)requestAnimationFrame(f);}
new MutationObserver(()=>{const g=r.classList.contains('gold');if(g&&!on){on=true;f();}else if(!g&&on){setTimeout(()=>{if(!r.classList.contains('gold'))on=false;},1800);}}).observe(r,{attributes:true,attributeFilter:['class']});})();
}catch(e){console.error(e)}
;
try{
window.__gtFallback=function(){const b=document.getElementById('gtBld');if(!b)return;const f=b.querySelector('.gt-film');if(f)f.remove();const c=b.querySelector('.gt-cam');if(c)c.style.display='';b.classList.remove('is3d');window.bb3dGold=false;b.classList.remove('play');requestAnimationFrame(()=>requestAnimationFrame(()=>b.classList.add('play')));};
(function(){try{
const box=document.getElementById('gtBld');if(!box||!window.THREE)return;
let ok=false;try{const c=document.createElement('canvas');ok=!!(c.getContext('webgl')||c.getContext('experimental-webgl'));}catch(e){}
if(!ok)return;
const T=THREE,RM=matchMedia('(prefers-reduced-motion: reduce)').matches;
const film=document.createElement('div');film.className='gt-film';
film.innerHTML='<canvas></canvas><div class="vig"></div><div class="lb t"></div><div class="lb b"></div><div class="sub" aria-hidden="true"></div><div class="gclk" aria-hidden="true"><b>21:59</b><i>:40</i><span>PÁ</span></div><div class="gend"><small>Gold Time · Pá–So 22:00 – 24:00</small><b>Celá budova.<br><em>Jen pro vás dva.</em></b><div class="gbtns"><a class="gu-btn gcall" href="tel:+420737222266">Zamluvit Gold Time <span aria-hidden="true">→</span></a><button type="button" class="grep" aria-label="Přehrát znovu">↻<span class="gr-t"> Přehrát znovu</span></button></div></div><div class="fade"></div>';
const camWrap=box.querySelector('.gt-cam');camWrap.style.display='none';box.insertBefore(film,camWrap);box.classList.add('is3d');
const cv=film.querySelector('canvas'),sub=film.querySelector('.sub'),clk=film.querySelector('.gclk'),ckH=clk.querySelector('b'),ckS=clk.querySelector('i');let lastSec=-1;
const MOB=matchMedia('(max-width:760px), (pointer:coarse)').matches;
let R;try{R=new T.WebGLRenderer({canvas:cv,antialias:!MOB,alpha:true,powerPreference:MOB?'default':'high-performance'});}catch(e){window.__gtFallback();return;}
if(!R.getContext()){window.__gtFallback();return;}
R.setPixelRatio(Math.min(window.devicePixelRatio||1,MOB?1.5:2));
cv.addEventListener('webglcontextlost',e=>{e.preventDefault();try{cancelAnimationFrame(raf);vis=false;}catch(_){}window.__gtFallback();},false);
R.outputEncoding=T.sRGBEncoding;R.toneMapping=T.ACESFilmicToneMapping;R.toneMappingExposure=.95;
const S=new T.Scene();S.fog=new T.FogExp2(0x0a0918,0.022);
const C=new T.PerspectiveCamera(34,1,0.05,400);
const sRGB=t=>{t.encoding=T.sRGBEncoding;return t;};

/* ---------- building (after the real one: gable front, 3 tall arched windows, long side) ---------- */
const W=8,D=16,HC=8.6,G=3.0,TH=.32;
const wallM=new T.MeshStandardMaterial({color:0xe6dcc6,roughness:.92,metalness:0});
const trimM=new T.MeshStandardMaterial({color:0xf3ecdc,roughness:.85});
const plinM=new T.MeshStandardMaterial({color:0x8f877a,roughness:.95});
const roofM=new T.MeshStandardMaterial({color:0x34343c,roughness:.75,metalness:.15});
const glassM=new T.MeshStandardMaterial({color:0x101626,roughness:.18,metalness:.75});
const frameM=new T.LineBasicMaterial({color:0xf2ede2,transparent:true,opacity:.85});
const XR=[];
function rectHole(x,y,w,h){const p=new T.Path();p.moveTo(x-w/2,y);p.lineTo(x+w/2,y);p.lineTo(x+w/2,y+h);p.lineTo(x-w/2,y+h);p.lineTo(x-w/2,y);return p;}
function archHole(x,y,w,h){const p=new T.Path(),r=w/2;p.moveTo(x-r,y);p.lineTo(x+r,y);p.lineTo(x+r,y+h);p.absarc(x,y+h,r,0,Math.PI,false);p.lineTo(x-r,y);return p;}
function wallGeo(outline,holes){const s=new T.Shape(outline.map(p=>new T.Vector2(p[0],p[1])));holes.forEach(h=>s.holes.push(h));return new T.ExtrudeGeometry(s,{depth:TH,bevelEnabled:false,curveSegments:20});}
/* front facade: z=0 outer face */
const ARCH=[-2.5,0,2.5],AW=1.5,AY=3.75,AH=3.9;
const fHoles=[...ARCH.map(x=>archHole(x,AY,AW,AH)),rectHole(-2.6,1.05,1.3,1.3),archHole(0,.7,1.2,1.75),rectHole(2.6,1.1,.95,1.2)];
const pent=[[-W/2,0],[W/2,0],[W/2,HC],[0,HC+G],[-W/2,HC]];
const front=new T.Mesh(wallGeo(pent,fHoles),wallM);front.position.z=-TH;S.add(front);
const back=new T.Mesh(wallGeo(pent,[]),wallM);back.position.z=-D;S.add(back);
/* side walls */
const COLS=[],ROWS=[[1.05,1.3,.9],[3.85,2.0,1.05],[6.35,1.75,1.05]];for(let i=0;i<7;i++)COLS.push(1.9+i*1.95);
function sideHoles(){const h=[];COLS.forEach(z=>ROWS.forEach(r=>h.push(rectHole(z,r[0],r[2],r[1]))));return h;}
const right=new T.Mesh(wallGeo([[0,0],[D,0],[D,HC],[0,HC]],sideHoles()),wallM);right.rotation.y=Math.PI/2;right.position.set(W/2-TH,0,0);S.add(right);
const left=new T.Mesh(wallGeo([[0,0],[D,0],[D,HC],[0,HC]],sideHoles()),wallM);left.rotation.y=Math.PI/2;left.position.set(-W/2,0,0);S.add(left);
/* plinth, cornices, band */
function boxM(w,h,d,m,x,y,z,rz){const b=new T.Mesh(new T.BoxGeometry(w,h,d),m);b.position.set(x,y,z);if(rz)b.rotation.z=rz;S.add(b);return b;}
boxM(W+.14,.62,D+.14,plinM,0,.31,-D/2);
boxM(W+.12,.16,.16,trimM,0,3.25,.06);
boxM(.16,.16,D,trimM,W/2+.06,3.25,-D/2);boxM(.16,.16,D,trimM,-W/2-.06,3.25,-D/2);
boxM(W+.5,.28,.42,trimM,0,HC+.04,.08);
boxM(.42,.28,D+.3,trimM,W/2+.12,HC+.04,-D/2);boxM(.42,.28,D+.3,trimM,-W/2-.12,HC+.04,-D/2);
const rake=Math.hypot(W/2,G)+.5,ang=Math.atan2(G,W/2);
boxM(rake,.24,.36,trimM,-W/4,HC+G/2+.08,.06,ang);boxM(rake,.24,.36,trimM,W/4,HC+G/2+.08,.06,-ang);
/* roof */
const rl=Math.hypot(W/2+.45,G+.3);
const r1=boxM(rl,.18,D+.6,roofM,-(W/4)-.1,HC+G/2+.02,-D/2,ang);r1.position.y+=.05;
const r2=boxM(rl,.18,D+.6,roofM,(W/4)+.1,HC+G/2+.02,-D/2,-ang);r2.position.y+=.05;
/* window glass, frames, mullions and warm lights */
const warm=[];
function addWindow(x,y,z,w,h,ry,arch,mull){const g=new T.Group();g.position.set(x,y,z);g.rotation.y=ry||0;S.add(g);
  const sh=new T.Shape();sh.moveTo(-w/2,0);sh.lineTo(w/2,0);sh.lineTo(w/2,h);if(arch)sh.absarc(0,h,w/2,0,Math.PI,false);else sh.lineTo(-w/2,h);sh.lineTo(-w/2,0);
  const geo=new T.ShapeGeometry(sh,20);
  const gl=new T.Mesh(geo,glassM);g.add(gl);
  const wm=new T.MeshBasicMaterial({color:0xffc98a,transparent:true,opacity:.0,depthWrite:false,toneMapped:false,blending:T.AdditiveBlending});const lit=new T.Mesh(geo,wm);lit.position.z=.01;lit.renderOrder=8;g.add(lit);wm.userData.g=g;
  const pts=[];const L=(a,b,c,d)=>pts.push(a,b,.02,c,d,.02);
  const top=arch?h+w/2:h;L(-w/2,0,w/2,0);L(-w/2,0,-w/2,h);L(w/2,0,w/2,h);if(!arch)L(-w/2,h,w/2,h);
  if(mull){for(let i=1;i<3;i++)L(-w/2+w*i/3,0,-w/2+w*i/3,top-(i===1||i===2?w*.07:0));for(let j=1;j<6;j++){const yy=h*j/5;L(-w/2,yy,w/2,yy);}}
  else{L(0,0,0,h);L(-w/2,h*.62,w/2,h*.62);}
  const lg=new T.BufferGeometry();lg.setAttribute('position',new T.Float32BufferAttribute(pts,3));g.add(new T.LineSegments(lg,frameM));
  if(arch){const a=[];for(let i=0;i<=24;i++){const t=i/24*Math.PI;a.push(Math.cos(t)*w/2,h+Math.sin(t)*w/2,.02);}
    const ag=new T.BufferGeometry().setFromPoints(a.reduce((r,v,i)=>{if(i%3===0)r.push(new T.Vector3(a[i],a[i+1],a[i+2]));return r;},[]));g.add(new T.Line(ag,frameM));}
  return {g,wm};}
/* front windows (middle arch = Black Box, left open) */
[-2.5,0,2.5].forEach(x=>{const w=addWindow(x,AY,-.2,AW,AH,0,true,true);warm.push(w.wm);});
warm.push(addWindow(-2.6,1.05,-.2,1.3,1.3,0,false,false).wm);warm.push(addWindow(2.6,1.1,-.2,.95,1.2,0,false,false).wm);
(function(){const dg=new T.Group();dg.position.set(0,.7,-.24);S.add(dg);
  const wood=new T.MeshStandardMaterial({color:0x3a2416,roughness:.55,metalness:.05}),pan=new T.MeshStandardMaterial({color:0x4a2f1d,roughness:.5});
  const brass=new T.MeshStandardMaterial({color:0xd8b26a,roughness:.25,metalness:.9});
  const frm=new T.MeshStandardMaterial({color:0x1c1c22,roughness:.35,metalness:.8});
  const dglass=new T.MeshStandardMaterial({color:0x9fb6c8,roughness:.05,metalness:.2,transparent:true,opacity:.32,side:T.DoubleSide,depthWrite:false});
  const inside=new T.Mesh(new T.PlaneGeometry(1.16,1.75),new T.MeshBasicMaterial({color:0xffd9a8,transparent:true,opacity:.18,toneMapped:false}));inside.position.set(0,.875,-.06);dg.add(inside);
  [-.3,.3].forEach((x,i)=>{const gl=new T.Mesh(new T.PlaneGeometry(.54,1.71),dglass);gl.position.set(x,.875,.005);dg.add(gl);
    [[x,.0+.02,.58,.04],[x,1.73,.58,.04]].forEach(([fx,fy,w,hh])=>{const b=new T.Mesh(new T.BoxGeometry(w,hh,.05),frm);b.position.set(fx,fy,0);dg.add(b);});
    [x-.27,x+.27].forEach(fx=>{const b=new T.Mesh(new T.BoxGeometry(.035,1.75,.05),frm);b.position.set(fx,.875,0);dg.add(b);});
    const hd=new T.Mesh(new T.CylinderGeometry(.012,.012,.55,10),brass);hd.position.set(i?.06:-.06,.95,.05);dg.add(hd);});
  const tr=new T.Mesh(new T.CircleGeometry(.6,24,0,Math.PI),glassM);tr.position.set(0,1.75,-.01);dg.add(tr);
  const trl=new T.Mesh(new T.CircleGeometry(.56,24,0,Math.PI),new T.MeshBasicMaterial({color:0xffcf8f,transparent:true,opacity:.35,toneMapped:false}));trl.position.set(0,1.75,0);dg.add(trl);
  for(let i=0;i<4;i++){const st=new T.Mesh(new T.BoxGeometry(2.3-i*.12,.155,.36*(4-i)),plinM);st.position.set(0,.0775+i*.155,.18*(4-i)-.02);S.add(st);}
  const sc=new T.Mesh(new T.BoxGeometry(.16,.24,.12),new T.MeshBasicMaterial({color:0xffe2b0,toneMapped:false}));sc.position.set(0,3.02,.1);S.add(sc);
  const dl=new T.PointLight(0xffc98a,1.1,5,1.6);dl.position.set(0,2.9,.6);S.add(dl);})();
/* side windows */
COLS.forEach(z=>ROWS.forEach(r=>{warm.push(addWindow(W/2-.2,r[0],-z,r[2],r[1],Math.PI/2,false,false).wm);warm.push(addWindow(-W/2+.2,r[0],-z,r[2],r[1],-Math.PI/2,false,false).wm);}));
warm.sort(()=>Math.random()-.5);warm.forEach((m,i)=>{Object.assign(m.userData,{t:4.75+i/warm.length*1.9+Math.random()*.12,lit:true});m.color.set(0xffa848);});

/* ---------- Black Box room – modelled after the photos ---------- */
const FL=3.3,RW=6.2,RH=2.95,RD=5.2,RCZ=-D/2,RZ=RCZ+RD/2-.3,BK=RCZ-RD/2,FR=RCZ+RD/2,LX=-RW/2,RX=RW/2;
function cvTex(w,h,draw,rep){const c=document.createElement('canvas');c.width=w;c.height=h;draw(c.getContext('2d'),w,h);const t=sRGB(new T.CanvasTexture(c));t.anisotropy=8;if(rep){t.wrapS=t.wrapT=T.RepeatWrapping;t.repeat.set(rep[0],rep[1]);}return t;}
function noise(x,w,h,a,n){for(let i=0;i<n;i++){x.fillStyle=`rgba(255,255,255,${Math.random()*a})`;x.fillRect(Math.random()*w,Math.random()*h,1.4,1.4);}}
const TX={
 wall:cvTex(256,256,(x,w,h)=>{x.fillStyle='#0c1036';x.fillRect(0,0,w,h);noise(x,w,h,.04,1500);}),
 slat:cvTex(512,256,(x,w,h)=>{x.fillStyle='#05061a';x.fillRect(0,0,w,h);for(let i=0;i<w;i+=16){const g=x.createLinearGradient(i,0,i+16,0);g.addColorStop(0,'#090b28');g.addColorStop(.45,'#1b2058');g.addColorStop(.55,'#141a4c');g.addColorStop(1,'#04051a');x.fillStyle=g;x.fillRect(i+2,0,11,h);}},[1,1]),
 marble:cvTex(1024,1024,(x,w,h)=>{const g=x.createLinearGradient(0,0,w,h);g.addColorStop(0,'#141a4a');g.addColorStop(.5,'#1c2160');g.addColorStop(1,'#121844');x.fillStyle=g;x.fillRect(0,0,w,h);
   for(let k=0;k<6;k++){x.fillStyle=`rgba(${80+Math.random()*60},${70+Math.random()*40},${150+Math.random()*60},.18)`;x.beginPath();x.ellipse(Math.random()*w,Math.random()*h,150+Math.random()*250,80+Math.random()*160,Math.random()*3,0,6.3);x.fill();}
   for(let i=0;i<70;i++){x.beginPath();let px=Math.random()*w,py=Math.random()*h,a=Math.random()*6.28;x.moveTo(px,py);const L=8+Math.random()*30;for(let j=0;j<L;j++){a+=(Math.random()-.5)*.5;px+=Math.cos(a)*14;py+=Math.sin(a)*14;x.lineTo(px,py);}
     x.strokeStyle=`rgba(225,230,255,${.12+Math.random()*.5})`;x.lineWidth=.5+Math.random()*1.6;x.stroke();}}),
 carpet:cvTex(512,512,(x,w,h)=>{x.fillStyle='#10143a';x.fillRect(0,0,w,h);noise(x,w,h,.09,26000);for(let i=0;i<18;i++){const g=x.createRadialGradient(Math.random()*w,Math.random()*h,0,0,0,0);}},[3,3]),
 velvet:cvTex(512,512,(x,w,h)=>{x.fillStyle='#05061a';x.fillRect(0,0,w,h);for(let i=0;i<40;i++){const g=x.createLinearGradient(0,Math.random()*h,w,Math.random()*h);g.addColorStop(0,'rgba(80,90,200,0)');g.addColorStop(.5,'rgba(80,90,200,.10)');g.addColorStop(1,'rgba(80,90,200,0)');x.fillStyle=g;x.fillRect(0,0,w,h);}noise(x,w,h,.06,9000);}),
 sheet:cvTex(256,256,(x,w,h)=>{x.fillStyle='#10164a';x.fillRect(0,0,w,h);noise(x,w,h,.05,3000);}),
 towel:cvTex(128,128,(x,w,h)=>{x.fillStyle='#4b4f78';x.fillRect(0,0,w,h);noise(x,w,h,.25,4000);}),
};
const M={
 wall:new T.MeshStandardMaterial({map:TX.wall,roughness:.9,side:T.BackSide}),
 slat:new T.MeshStandardMaterial({map:TX.slat,roughness:.6,metalness:.1}),
 marble:new T.MeshStandardMaterial({map:TX.marble,roughness:.25,metalness:.15}),
 carpet:new T.MeshStandardMaterial({map:TX.carpet,roughness:1}),
 leather:new T.MeshStandardMaterial({color:0x07081a,roughness:.34,metalness:.2}),
 dark:new T.MeshStandardMaterial({color:0x080a20,roughness:.45,metalness:.3}),
 velvet:new T.MeshStandardMaterial({map:TX.velvet,roughness:.95}),
 sheet:new T.MeshStandardMaterial({map:TX.sheet,roughness:.9}),
 pillow:new T.MeshStandardMaterial({color:0x1d2160,roughness:.85}),
 towel:new T.MeshStandardMaterial({map:TX.towel,roughness:1}),
 metal:new T.MeshStandardMaterial({color:0xb8bdd8,roughness:.2,metalness:1}),
 white:new T.MeshStandardMaterial({color:0xeef0ff,roughness:.5}),
 ledV:new T.MeshBasicMaterial({color:0xb27bff,toneMapped:false}),
 ledW:new T.MeshBasicMaterial({color:0xdcd2ff,toneMapped:false}),
 star:new T.MeshBasicMaterial({color:0xffffff,toneMapped:false}),
};
function bx(w,h,d,m,x,y,z,parent){const o=new T.Mesh(new T.BoxGeometry(w,h,d),m);o.position.set(x,y,z);(parent||S).add(o);return o;}
function rbx(w,h,d,r,m,x,y,z,parent){const s=new T.Shape(),a=w/2-r,b=h/2-r;s.moveTo(-a,-h/2);s.lineTo(a,-h/2);s.quadraticCurveTo(w/2,-h/2,w/2,-b);s.lineTo(w/2,b);s.quadraticCurveTo(w/2,h/2,a,h/2);s.lineTo(-a,h/2);s.quadraticCurveTo(-w/2,h/2,-w/2,b);s.lineTo(-w/2,-b);s.quadraticCurveTo(-w/2,-h/2,-a,-h/2);
  const g=new T.ExtrudeGeometry(s,{depth:d-2*r*.6,bevelEnabled:true,bevelThickness:r*.6,bevelSize:r*.6,bevelSegments:3,curveSegments:6});g.translate(0,0,-(d-2*r*.6)/2);const o=new T.Mesh(g,m);o.position.set(x,y,z);(parent||S).add(o);return o;}
const bloomTex=cvTex(64,64,(x,w,h)=>{const g=x.createRadialGradient(32,32,0,32,32,32);g.addColorStop(0,'rgba(255,255,255,1)');g.addColorStop(.3,'rgba(255,255,255,.35)');g.addColorStop(1,'rgba(255,255,255,0)');x.fillStyle=g;x.fillRect(0,0,w,h);});
let BG=null;function bloom(col,s,x,y,z,o){const sp=new T.Sprite(new T.SpriteMaterial({map:bloomTex,color:col,transparent:true,opacity:o,blending:T.AdditiveBlending,depthWrite:false,toneMapped:false}));sp.scale.set(s,s,1);sp.position.set(x,y,z);S.add(sp);if(BG){sp.userData.o0=o;BG.push(sp);}return sp;}
function ledLine(a,b,m,th){const d=b.clone().sub(a),L=d.length();const o=new T.Mesh(new T.CylinderGeometry(th||.012,th||.012,L,6),m||M.ledV);o.position.copy(a).add(d.clone().multiplyScalar(.5));o.quaternion.setFromUnitVectors(new T.Vector3(0,1,0),d.normalize());S.add(o);
  const n=Math.max(2,Math.round(L/.25));for(let i=0;i<=n;i++){const p=a.clone().lerp(b,i/n);bloom((m||M.ledV).color,.42,p.x,p.y,p.z,.32);}return o;}
/* shell */
const room=new T.Mesh(new T.BoxGeometry(RW,RH,RD),M.wall);room.position.set(0,FL+RH/2,RCZ);S.add(room);
const fl=new T.Mesh(new T.PlaneGeometry(RW-.01,RD-.01),M.carpet);fl.rotation.x=-Math.PI/2;fl.position.set(0,FL+.003,RCZ);S.add(fl);
const ceil=new T.Mesh(new T.PlaneGeometry(RW-.01,RD-.01),new T.MeshBasicMaterial({color:0x02030c}));ceil.rotation.x=Math.PI/2;ceil.position.set(0,FL+RH-.003,RCZ);S.add(ceil);
/* fibre-optic star ceiling (twinkles) */
const starG=new T.BufferGeometry(),stP=[],stC=[];for(let i=0;i<900;i++){stP.push(LX+.05+Math.random()*(RW-.1),FL+RH-.01,BK+.05+Math.random()*(RD-.1));const b=.55+Math.random()*.45;stC.push(b,b,b*(.95+Math.random()*.1));}
starG.setAttribute('position',new T.Float32BufferAttribute(stP,3));starG.setAttribute('color',new T.Float32BufferAttribute(stC,3));
const starPts=new T.Points(starG,new T.PointsMaterial({size:.022,vertexColors:true,transparent:true,opacity:1,toneMapped:false}));S.add(starPts);
const starPts2=new T.Points(starG,new T.PointsMaterial({size:.05,map:bloomTex,color:0xbfc8ff,transparent:true,opacity:.35,blending:T.AdditiveBlending,depthWrite:false,toneMapped:false}));S.add(starPts2);
/* soffit band along the walls with LED edge, and the long curved LED line across the ceiling */
const SO=.42,SH=.18,sy=FL+RH-SH/2;
bx(RW,SH,SO,M.dark,0,sy,BK+SO/2);bx(SO,SH,RD,M.dark,LX+SO/2,sy,RCZ);bx(SO,SH,RD,M.dark,RX-SO/2,sy,RCZ);
const LEDB=[];BG=LEDB;ledLine(new T.Vector3(LX+SO,FL+RH-SH,BK+SO),new T.Vector3(RX-SO,FL+RH-SH,BK+SO));
ledLine(new T.Vector3(LX+SO,FL+RH-SH,BK+SO),new T.Vector3(LX+SO,FL+RH-SH,FR-.05));
ledLine(new T.Vector3(RX-SO,FL+RH-SH,BK+SO),new T.Vector3(RX-SO,FL+RH-SH,FR-.05));
(function(){const pts=[];for(let i=0;i<=40;i++){const u=i/40;pts.push(new T.Vector3(LX+SO+u*(RW*.62),FL+RH-.02,FR-.25-Math.sin(u*Math.PI*.9)*RD*.55));}
  const cur=new T.CatmullRomCurve3(pts);S.add(new T.Mesh(new T.TubeGeometry(cur,80,.012,6),M.ledV));cur.getPoints(28).forEach(p=>bloom(0xb27bff,.42,p.x,p.y,p.z,.3));})();BG=null;
/* back wall: slat panels, TV, media wall, speakers */
const scrW=2.55,scrH=1.44,scrX=0,scrY=FL+1.55;
bx(.5,RH-SH-.02,.06,M.slat,scrX-scrW/2-.45,FL+(RH-SH)/2,BK+.04);
bx(.5,RH-SH-.02,.06,M.slat,scrX+scrW/2+.45,FL+(RH-SH)/2,BK+.04);
bx(scrW+.2,.95,.24,M.dark,scrX,FL+.48,BK+.13);
const scrFrame=bx(scrW+.06,scrH+.06,.05,new T.MeshStandardMaterial({color:0x05050a,roughness:.3}),scrX,scrY,BK+.05);
const scr=new T.Mesh(new T.PlaneGeometry(scrW,scrH),new T.MeshBasicMaterial({color:0x4d7cff,toneMapped:false}));scr.position.set(scrX,scrY,BK+.08);S.add(scr);
(function(){const src='img/foto-02.jpg';const i2=new Image();i2.onload=()=>{const c=document.createElement('canvas');c.width=1024;c.height=Math.round(1024*scrH/scrW);
  const W2=i2.width,H2=i2.height;c.getContext('2d').drawImage(i2,0,0,W2,H2,0,0,c.width,c.height);scr.material.map=sRGB(new T.CanvasTexture(c));scr.material.color.set(0xffffff);scr.material.needsUpdate=true;};i2.src=src;})();
const scrGlow=bloom(0x6aa8ff,4.2,scrX,scrY,BK+.4,.22);
const scrL=new T.PointLight(0x5a5cff,2.4,8,1.2);scrL.position.set(scrX,scrY,BK+1.3);S.add(scrL);
function speaker(x){const g=new T.Group();g.position.set(x,FL,BK+.3);S.add(g);bx(.26,1.08,.3,M.dark,0,.54,0,g);
  [[.86,.045],[.66,.075],[.42,.075]].forEach(([y,r])=>{const d=new T.Mesh(new T.CylinderGeometry(r,r,.02,24),M.metal);d.rotation.x=Math.PI/2;d.position.set(0,y,.155);g.add(d);
    const ring=new T.Mesh(new T.TorusGeometry(r+.006,.004,6,32),new T.MeshBasicMaterial({color:0xc58bff,toneMapped:false}));ring.position.set(0,y,.162);g.add(ring);});}
speaker(scrX-scrW/2-.2);speaker(scrX+scrW/2+.2);
/* leather sofas (left of the TV against the back wall and right of the TV) */
function sofa(x,z,w,ry){const g=new T.Group();g.position.set(x,FL,z);g.rotation.y=ry||0;S.add(g);
  rbx(w,.36,.86,.05,M.leather,0,.25,0,g);rbx(w,.5,.2,.06,M.leather,0,.62,-.34,g);rbx(.2,.55,.86,.06,M.leather,-w/2+.1,.36,0,g);rbx(.2,.55,.86,.06,M.leather,w/2-.1,.36,0,g);
  const n=Math.max(1,Math.round((w-.4)/.7));for(let i=0;i<n;i++){const cw=(w-.4)/n;rbx(cw-.03,.14,.66,.05,M.leather,-w/2+.2+cw*(i+.5),.48,.05,g);}
  [-w/2+.12,w/2-.12].forEach(fx=>[-.33,.33].forEach(fz=>{const f=new T.Mesh(new T.CylinderGeometry(.015,.015,.08,8),M.metal);f.position.set(fx,.04,fz);g.add(f);}));return g;}
sofa(LX+.72,BK+.55,1.2);
const s2x=RX-.72;sofa(s2x,BK+.55,1.2);
/* white branch decor (as on the photos) */
function branches(x,z){const g=new T.Group();g.position.set(x,FL,z);S.add(g);const m=new T.MeshBasicMaterial({color:0xdfe2ff,toneMapped:false});
  const base=new T.Mesh(new T.CylinderGeometry(.06,.08,.05,16),M.dark);base.position.y=.025;g.add(base);
  for(let i=0;i<7;i++){const a=(i-3)*.18,len=1.0+Math.random()*.45;const pts=[new T.Vector3(0,0,0),new T.Vector3(Math.sin(a)*.12,len*.55,0),new T.Vector3(Math.sin(a)*.45,len,Math.cos(i)*.05)];
    g.add(new T.Mesh(new T.TubeGeometry(new T.QuadraticBezierCurve3(...pts),16,.006,4),m));}
  const st=new T.Mesh(new T.CylinderGeometry(.008,.008,.6,6),m);st.position.y=.3;g.add(st);return g;}
branches(LX+.22,BK+.3);branches(RX-.22,BK+.3);
/* low black coffee table with an orchid and champagne for two */
const tblX=LX+.72,tblZ=BK+1.4;
const tb=new T.Group();tb.position.set(tblX,FL,tblZ);S.add(tb);
bx(.72,.04,.5,M.dark,0,.46,0,tb);bx(.66,.03,.44,M.dark,0,.12,0,tb);[[-.33,-.22],[.33,-.22],[-.33,.22],[.33,.22]].forEach(([a,b])=>bx(.04,.46,.04,M.dark,a,.23,b,tb));
(function(){const v=new T.Mesh(new T.CylinderGeometry(.04,.05,.13,16),new T.MeshStandardMaterial({color:0x10102a,roughness:.1,metalness:.6}));v.position.set(-.22,.545,-.1);tb.add(v);
  const pm=new T.MeshStandardMaterial({color:0xe6a3ff,emissive:0x6a2a8a,emissiveIntensity:.5,roughness:.6});for(let i=0;i<9;i++){const f=new T.Mesh(new T.SphereGeometry(.022,8,6),pm);f.position.set(-.22+Math.sin(i)*.05,.66+i*.025,-.1+Math.cos(i*1.7)*.04);tb.add(f);}})();
function lth(pts,seg){const sp=new T.SplineCurve(pts.map(p=>new T.Vector2(p[0],p[1]))).getPoints(pts.length*8);return new T.LatheGeometry(sp,seg||40);}
const couple=new T.Group();couple.position.set(.05,.48,.04);tb.add(couple);
const glassMat=new T.MeshStandardMaterial({color:0xf4f0ff,roughness:.05,metalness:.1,transparent:true,opacity:.3,side:T.DoubleSide,depthWrite:false});
const waterMat=new T.MeshStandardMaterial({color:0xcfe6ff,emissive:0x2a4a78,emissiveIntensity:.35,roughness:.05,metalness:.1,transparent:true,opacity:.42,depthWrite:false});
function tumbler(x,z){const g=new T.Group();g.position.set(x,0,z);couple.add(g);
  g.add(new T.Mesh(new T.LatheGeometry([[0,0],[.036,0],[.038,.006],[.042,.12],[.04,.122],[.034,.008],[0,.008]].map(p=>new T.Vector2(p[0],p[1])),40),glassMat));
  g.add(new T.Mesh(new T.CylinderGeometry(.037,.034,.075,32),waterMat).translateY(.046));
  g.userData.b=new T.Object3D();g.add(g.userData.b);return g;}
const f1=tumbler(-.06,.03),f2=tumbler(.06,.02);
const carafe=new T.Mesh(lth([[0,0],[.06,0],[.068,.04],[.062,.12],[.03,.2],[.024,.27],[.03,.3],[.032,.305]],40),glassMat);carafe.position.set(.2,0,-.08);couple.add(carafe);
const carafeW=new T.Mesh(lth([[0,.006],[.056,.006],[.063,.04],[.058,.11],[0,.11]],36),waterMat);carafeW.position.set(.2,0,-.08);couple.add(carafeW);
const refl=new T.Group();refl.visible=false;S.add(refl);
const champL=new T.PointLight(0xffd59a,.45,1.4,2);champL.position.set(tblX,FL+.95,tblZ+.2);S.add(champL);
/* marble alcove in the back-right corner: mirror, AC unit, corner whirlpool */
const mZ0=FR-2.25,mZ1=FR-.02,mL=mZ1-mZ0,mC=(mZ0+mZ1)/2;
const mf=new T.Mesh(new T.PlaneGeometry(RX-1.15,RH-SH),M.marble);mf.rotation.y=Math.PI;mf.position.set((RX+1.15)/2,FL+(RH-SH)/2,FR-.02);S.add(mf);
const mr=new T.Mesh(new T.PlaneGeometry(mL,RH-SH),M.marble);mr.rotation.y=-Math.PI/2;mr.position.set(RX-.02,FL+(RH-SH)/2,mC);S.add(mr);
bx(.12,RH-SH,.16,M.slat,RX-.06,FL+(RH-SH)/2,mZ0);bx(.12,RH-SH,.16,M.slat,RX-.06,FL+(RH-SH)/2,mZ1);
bx(.2,.24,.82,M.white,RX-.12,FL+RH-SH-.34,mC);
BG=LEDB;ledLine(new T.Vector3(RX-.03,FL+RH-SH-.62,mZ0+.1),new T.Vector3(RX-.03,FL+RH-SH-.62,mZ1-.1),M.ledW,.008);BG=null;
const mirror=new T.Mesh(new T.PlaneGeometry(.55,1.55),new T.MeshStandardMaterial({color:0x7d86c8,roughness:.03,metalness:1}));mirror.position.set(LX+2.15,FL+1.2,BK+.035);mirror.visible=false;S.add(mirror);
const tubX=RX-.62,tubZ=FR-.62;const TUBB=[],TUBW=[];BG=TUBB;
(function(){const g=new T.Group();g.position.set(RX-.03,FL,FR-.03);S.add(g);const R0=1.5,H=.6;
  const shp=(o)=>{const s=new T.Shape();s.moveTo(-o,o);s.lineTo(-R0+o,o);s.lineTo(-R0+o,.35+o*.3);s.quadraticCurveTo(-R0+.05+o*.9,R0-.05-o*.9,-.35-o*.3,R0-o);s.lineTo(-o,R0-o);s.lineTo(-o,o);return s;};
  const toW=(x,y,h)=>new T.Vector3(g.position.x+x,FL+h,g.position.z-y);
  const shellM=new T.MeshStandardMaterial({color:0x0a0b1e,roughness:.06,metalness:.55});
  const bodyS=shp(0);bodyS.holes.push(shp(.17));const body=new T.Mesh(new T.ExtrudeGeometry(bodyS,{depth:H-.06,bevelEnabled:true,bevelThickness:.03,bevelSize:.03,bevelSegments:3,curveSegments:28}),shellM);body.rotation.x=-Math.PI/2;body.position.y=.02;g.add(body);
  /* rim / lip with a hole */
  const rimS=shp(0);rimS.holes.push(shp(.17));
  const rim=new T.Mesh(new T.ExtrudeGeometry(rimS,{depth:.05,bevelEnabled:true,bevelThickness:.02,bevelSize:.02,bevelSegments:3,curveSegments:28}),new T.MeshStandardMaterial({color:0x1b1e46,roughness:.08,metalness:.4}));rim.rotation.x=-Math.PI/2;rim.position.y=H;g.add(rim);
  /* inner basin (lit acrylic) */
  const basin=new T.Mesh(new T.ExtrudeGeometry(shp(.17),{depth:.4,bevelEnabled:false,curveSegments:28}),new T.MeshStandardMaterial({color:0xcfc6ff,emissive:0x5b36d6,emissiveIntensity:.55,roughness:.2,side:T.BackSide}));basin.rotation.x=-Math.PI/2;basin.position.y=H-.39;g.add(basin);const bot=new T.Mesh(new T.ShapeGeometry(shp(.17),28),new T.MeshStandardMaterial({color:0xd8cfff,emissive:0x6a46e6,emissiveIntensity:.7,roughness:.3}));bot.rotation.x=-Math.PI/2;bot.position.y=H-.38;g.add(bot);
  /* water with moving caustics */
  const wc=document.createElement('canvas');wc.width=wc.height=256;const wx=wc.getContext('2d');const wg=wx.createLinearGradient(0,0,256,256);wg.addColorStop(0,'#8a63ff');wg.addColorStop(1,'#5a3be0');wx.fillStyle=wg;wx.fillRect(0,0,256,256);
  for(let i=0;i<60;i++){wx.beginPath();const x=Math.random()*256,y=Math.random()*256,r=10+Math.random()*28;wx.ellipse(x,y,r,r*.6,Math.random()*3,0,6.3);wx.strokeStyle=`rgba(235,225,255,${.15+Math.random()*.35})`;wx.lineWidth=1+Math.random()*2;wx.stroke();}
  const wt=sRGB(new T.CanvasTexture(wc));wt.wrapS=wt.wrapT=T.RepeatWrapping;wt.repeat.set(2,2);
  const water=new T.Mesh(new T.ShapeGeometry(shp(.18),28),new T.MeshBasicMaterial({map:wt,transparent:true,opacity:.92,toneMapped:false}));water.rotation.x=-Math.PI/2;water.position.y=H-.08;g.add(water);
  window.__tubWater=wt;
  /* headrests in the corner */
  [[-.32,.95],[-.95,.32]].forEach(([x,y],i)=>{const hr=rbx(.36,.1,.16,.04,new T.MeshStandardMaterial({color:0x101230,roughness:.5}),0,0,0,S);hr.position.copy(toW(x,y,H+.08));hr.rotation.y=i?-.15:Math.PI/2+.15;hr.rotation.z=.25;});
  /* chrome faucet + handles on the rim near the wall */
  const chrome=new T.MeshStandardMaterial({color:0xe6e8f5,roughness:.08,metalness:1});
  (function(){const p=toW(-.06,.78,H+.05);const st=new T.Mesh(new T.CylinderGeometry(.022,.026,.2,16),chrome);st.position.copy(p).add(new T.Vector3(0,.1,0));S.add(st);
    const sp=new T.Mesh(new T.TorusGeometry(.09,.016,10,24,Math.PI),chrome);sp.position.copy(p).add(new T.Vector3(-.09,.2,0));S.add(sp);
    [-.22,.22].forEach(d=>{const q=toW(-.06,.78+d,H+.05);const k=new T.Mesh(new T.CylinderGeometry(.03,.03,.05,16),chrome);k.position.copy(q).add(new T.Vector3(0,.03,0));S.add(k);});})();
  /* grab rails on the front */
  (function(){const pts=[];const c=new T.QuadraticBezierCurve(new T.Vector2(-R0,.35),new T.Vector2(-R0+.05,R0-.05),new T.Vector2(-.35,R0));
    [[.12,.3],[.7,.88]].forEach(([a,b])=>{const ps=[];for(let i=0;i<=10;i++){const p=c.getPoint(a+(b-a)*i/10);ps.push(toW(p.x*1.05,p.y*1.05,.42));}S.add(new T.Mesh(new T.TubeGeometry(new T.CatmullRomCurve3(ps),24,.012,8),chrome));});})();
  /* glass window + front lights + jets */
  const curve=new T.QuadraticBezierCurve(new T.Vector2(-R0,.35),new T.Vector2(-R0+.05,R0-.05),new T.Vector2(-.35,R0));
  const winPts=curve.getPoints(16).slice(5,12).map(p=>toW(p.x*1.035,p.y*1.035,.3));
  for(let i=0;i<winPts.length-1;i++){const a=winPts[i],b=winPts[i+1],d=b.clone().sub(a),m=new T.Mesh(new T.PlaneGeometry(d.length()+.01,.22),new T.MeshBasicMaterial({color:0xb7a2ff,transparent:true,opacity:.7,side:T.DoubleSide,toneMapped:false}));m.position.copy(a).add(d.multiplyScalar(.5));m.rotation.y=Math.atan2(-(b.z-a.z),b.x-a.x);S.add(m);TUBW.push(m.material);}
  curve.getPoints(8).forEach(p=>{bloom(0xd8d0ff,.24,0,0,0,.95).position.copy(toW(p.x*1.03,p.y*1.03,.09));});
  const inner=new T.QuadraticBezierCurve(new T.Vector2(-R0+.2,.42),new T.Vector2(-R0+.25,R0-.25),new T.Vector2(-.42,R0-.2));
  inner.getPoints(9).forEach(p=>{bloom(0xf0e6ff,.14,0,0,0,1).position.copy(toW(p.x,p.y,H-.2));bloom(0xffffff,.07,0,0,0,1).position.copy(toW(p.x,p.y,H-.2));});
  [[-.2,.6],[-.2,1.0],[-.6,.2],[-1.0,.2]].forEach(([x,y])=>{bloom(0xf0e6ff,.12,0,0,0,1).position.copy(toW(x,y,H-.22));});
  g.userData.water=water;window.__tubG=g;})();BG=null;
const TUBE=[];window.__tubG.traverse(o=>{const m=o.material;if(m&&m.emissiveIntensity>0)TUBE.push([m,m.emissiveIntensity]);});
const LEDM=[[M.ledV,M.ledV.color.clone()],[M.ledW,M.ledW.color.clone()]];LEDB.forEach((sp,i)=>sp.userData.d=i/Math.max(1,LEDB.length-1));
const tubL=new T.PointLight(0x6a5cff,1.3,2.6,1.8);tubL.position.set(tubX-.2,FL+.9,tubZ+.2);S.add(tubL);
const bb=[];for(let i=0;i<160;i++){const a=Math.random()*6.28,r=Math.random()*.5;bb.push(tubX+Math.cos(a)*r,FL+.53+Math.random()*.02,tubZ+Math.sin(a)*r);}
const bbG=new T.BufferGeometry();bbG.setAttribute('position',new T.Float32BufferAttribute(bb,3));
const tubB=new T.Points(bbG,new T.PointsMaterial({color:0xece4ff,size:.022,transparent:true,opacity:.75,toneMapped:false}));S.add(tubB);
/* King Size bed against the right wall, nightstands, towels */
const bedX=0,bedZ=FR-1.15;
(function(){const g=new T.Group();g.position.set(bedX,FL,bedZ);g.rotation.y=-Math.PI/2;S.add(g);
  rbx(2.2,.42,2.0,.06,M.velvet,0,.21,0,g);
  rbx(2.08,.24,1.9,.08,M.sheet,-.02,.52,0,g);
  const hb=rbx(.16,1.0,2.15,.06,M.velvet,1.06,.62,0,g);
  for(let i=0;i<4;i++)rbx(.04,.88,.02,.01,M.dark,1.0,.64,-.8+i*.53,g);
  const thr=new T.Mesh(new T.BoxGeometry(1.15,.05,1.98,8,1,8),M.velvet);thr.position.set(-.48,.665,0);g.add(thr);
  const tp=thr.geometry.attributes.position;for(let i=0;i<tp.count;i++){const x=tp.getX(i),z=tp.getZ(i);tp.setY(i,tp.getY(i)+Math.sin(x*7+z*3)*.012+Math.cos(z*6)*.01);}tp.needsUpdate=true;thr.geometry.computeVertexNormals();
  [[.82,-.5],[.82,.5],[.65,-.22],[.65,.24]].forEach(([x,z],i)=>{const p=rbx(.16,.36,.55,.07,M.pillow,x,.82,z,g);p.rotation.z=.28;});
  [[-.62,-.13],[-.62,.13]].forEach(([x,z])=>{const t=new T.Mesh(new T.CylinderGeometry(.09,.09,.42,20),M.towel);t.rotation.x=Math.PI/2;t.position.set(x,.78,z);g.add(t);});
  [-1.35,1.35].forEach(z=>{bx(.5,.5,.42,M.dark,.86,.25,z,g);const tis=bx(.2,.1,.12,M.white,.86,.55,z,g);});})();
/* dim warm-blue room lighting like on the photos */
const roomL=new T.PointLight(0x5a5cff,.9,10,1.2);roomL.position.set(0,FL+RH-.4,RCZ);S.add(roomL);
const fillL=new T.PointLight(0x5a5cff,.45,7,1.4);fillL.position.set(bedX-.5,FL+2.0,bedZ+.6);S.add(fillL);
const leftL=new T.PointLight(0x5a5cff,.6,7,1.4);leftL.position.set(LX+1.2,FL+1.8,BK+1.4);S.add(leftL);
const cube=new T.Mesh(new T.BoxGeometry(.001,.001,.001),M.dark);cube.visible=false;S.add(cube);
const dust=[];for(let i=0;i<180;i++)dust.push(LX+.3+Math.random()*(RW-.6),FL+.4+Math.random()*2.2,BK+.3+Math.random()*(RD-.6));
const dg=new T.BufferGeometry();dg.setAttribute('position',new T.Float32BufferAttribute(dust,3));
const dustP=new T.Points(dg,new T.PointsMaterial({color:0xd8e0ff,size:.012,transparent:true,opacity:.4,blending:T.AdditiveBlending,depthWrite:false}));S.add(dustP);
/* glowing outline + volume of the room for the finale */
const roomEdge=new T.LineSegments(new T.EdgesGeometry(new T.BoxGeometry(RW,RH,RD)),new T.LineBasicMaterial({color:0xb57aff,transparent:true,opacity:0,depthTest:false,toneMapped:false}));roomEdge.position.copy(room.position);roomEdge.renderOrder=10;S.add(roomEdge);
const roomGlow=new T.Mesh(new T.BoxGeometry(RW*.98,RH*.98,RD*.98),new T.MeshBasicMaterial({color:0x7a3cff,transparent:true,opacity:0,blending:T.AdditiveBlending,depthWrite:false,depthTest:false,side:T.DoubleSide,toneMapped:false}));roomGlow.position.copy(room.position);roomGlow.renderOrder=9;S.add(roomGlow);
const roomBeam=new T.Mesh(new T.BoxGeometry(RW*1.04,RH*1.04,RD*1.04),new T.MeshBasicMaterial({color:0xc58bff,transparent:true,opacity:0,blending:T.AdditiveBlending,depthWrite:false,depthTest:false,side:T.BackSide,toneMapped:false}));roomBeam.position.copy(room.position);roomBeam.renderOrder=11;S.add(roomBeam);
window.__room={starPts,starPts2,scrGlow};


/* ---------- night environment ---------- */
S.add(new T.HemisphereLight(0x5a6aa8,0x0b0a12,.22));
const moon=new T.DirectionalLight(0x9fb3ff,.38);moon.position.set(-14,22,18);S.add(moon);
function up(x,z,tx,ty,tz,int){const s=new T.SpotLight(0xffd3a0,int,26,.42,.75,1.4);s.position.set(x,.15,z);s.target.position.set(tx,ty,tz);S.add(s);S.add(s.target);}
up(-2.5,1.6,-2.5,7.5,0,1.05);up(2.5,1.6,2.5,7.5,0,1.05);up(W/2+2,-5,W/2,6.5,-5,.9);up(W/2+2,-12,W/2,6.5,-12,.8);
const gc=document.createElement('canvas');gc.width=gc.height=128;const gx=gc.getContext('2d'),gr=gx.createRadialGradient(64,64,0,64,64,64);
gr.addColorStop(0,'rgba(255,255,255,1)');gr.addColorStop(.25,'rgba(255,255,255,.5)');gr.addColorStop(1,'rgba(255,255,255,0)');gx.fillStyle=gr;gx.fillRect(0,0,128,128);const gtex=new T.CanvasTexture(gc);
function glow(col,s,x,y,z,o){const m=new T.SpriteMaterial({map:gtex,color:col,transparent:true,opacity:o,blending:T.AdditiveBlending,depthWrite:false,toneMapped:false});const sp=new T.Sprite(m);sp.scale.set(s,s,1);sp.position.set(x,y,z);S.add(sp);return sp;}
const gCube=glow(0xb57aff,.0001,0,FL+2.75,BK+.5,0),gWin=glow(0x9a4dff,7,0,FL+1.4,RCZ,.0);gWin.material.depthTest=false;
const poleM=new T.MeshStandardMaterial({color:0x2a2a30,roughness:.6,metalness:.6});
function lamp(x,z){const p=new T.Mesh(new T.CylinderGeometry(.06,.09,5.2,10),poleM);p.position.set(x,2.6,z);S.add(p);
  const hd=new T.Mesh(new T.SphereGeometry(.22,16,12),new T.MeshBasicMaterial({color:0xffe2b0,toneMapped:false}));hd.position.set(x,5.25,z);S.add(hd);
  const l=new T.PointLight(0xffc98a,1.5,13,1.6);l.position.set(x,5.1,z);S.add(l);const gs=glow(0xffc98a,2.2,x,5.25,z,.75);(window.__lamps=window.__lamps||[]).push({l,gs,hd});}
lamp(-6.2,4.2);lamp(10.2,.8);
const ground=new T.Mesh(new T.PlaneGeometry(200,200),new T.MeshStandardMaterial({color:0x17161c,roughness:.82,metalness:.1}));ground.rotation.x=-Math.PI/2;S.add(ground);
const walk=new T.Mesh(new T.PlaneGeometry(30,4.5),new T.MeshStandardMaterial({color:0x2b2932,roughness:.9}));walk.rotation.x=-Math.PI/2;walk.position.set(0,.005,2.6);S.add(walk);
/* neighbour office block + trees */
function winTex(){const c=document.createElement('canvas');c.width=256;c.height=256;const x=c.getContext('2d');x.fillStyle='#1d1f27';x.fillRect(0,0,256,256);
  for(let r=0;r<10;r++)for(let k=0;k<8;k++){x.fillStyle=Math.random()<.12?'#d8b47a':'#0d0f16';x.fillRect(8+k*31,8+r*25,22,15);}return sRGB(new T.CanvasTexture(c));}
const nb=new T.Mesh(new T.BoxGeometry(9,11,12),new T.MeshStandardMaterial({color:0x8f939c,map:winTex(),roughness:.9,emissive:0xffffff,emissiveMap:winTex(),emissiveIntensity:.25}));nb.position.set(-15,5.5,-8);S.add(nb);const nbM=nb.material;
const trM=new T.MeshStandardMaterial({color:0x0f1712,roughness:1});
function tree(x,z,s){const t=new T.Mesh(new T.CylinderGeometry(.12*s,.18*s,2.4*s,8),new T.MeshStandardMaterial({color:0x1a1410}));t.position.set(x,1.2*s,z);S.add(t);
  const c=new T.Mesh(new T.IcosahedronGeometry(1.6*s,1),trM);c.position.set(x,3.4*s,z);c.scale.y=1.25;S.add(c);}
tree(10.5,-3,1.2);tree(11.5,-9,1.4);tree(-8.5,-17,1.3);
/* ---------- story extras: people leaving, lock, floor labels, sleeping town ---------- */
const STORY={people:[],labels:[],lamps:[],towns:[]};
/* walking silhouettes */
(function(){const pm=new T.MeshStandardMaterial({color:0x0a0a12,roughness:.7});
 function lath(pts){const sp=new T.SplineCurve(pts.map(p=>new T.Vector2(p[0],p[1]))).getPoints(pts.length*6);sp[0].x=0;sp[sp.length-1].x=0;return new T.LatheGeometry(sp,20);}
 function person(h,coat){const g=new T.Group();const body=new T.Mesh(lath([[0,.9],[.17,.92],[.2,1.12],[.19,1.35],[.12,1.45],[0,1.47]]),pm);g.add(body);
  const head=new T.Mesh(new T.SphereGeometry(.11,16,12),pm);head.position.y=1.6;g.add(head);
  const legs=[];[-.08,.08].forEach(x=>{const l=new T.Mesh(new T.CylinderGeometry(.065,.05,.9,8),pm);l.position.set(x,.45,0);l.geometry.translate(0,-.45,0);l.position.y=.9;g.add(l);legs.push(l);});
  if(coat){const c=new T.Mesh(lath([[0,.55],[.22,.56],[.2,1.0],[.18,1.3],[0,1.32]]),pm);g.add(c);}
  g.scale.setScalar(h);g.visible=false;S.add(g);return {g,legs};}
 const routes=[[1.0,[-.2,0,.4],[-5.5,0,9]],[1.7,[.15,0,.4],[6.5,0,8.5]],[2.4,[-.1,0,.4],[-1.5,0,11]],[3.1,[.2,0,.4],[4,0,11.5]]];
 routes.forEach(([t,a,b],i)=>{const p=person(.98+((i*7)%3)*.04,i%2===0);p.t=t;p.a=new T.Vector3(...a);p.b=new T.Vector3(...b);STORY.people.push(p);});})();
/* door light that turns gold + lock badge */
const lockTex=cvTex(256,256,(x,w,h)=>{x.translate(128,128);x.shadowColor='rgba(255,210,120,.9)';x.shadowBlur=24;x.strokeStyle='#ffe0a0';x.lineWidth=14;x.lineCap='round';
  x.beginPath();x.arc(0,-22,34,Math.PI,0);x.stroke();x.fillStyle='#f0cf85';x.beginPath();x.roundRect?x.roundRect(-52,-22,104,84,14):x.rect(-52,-22,104,84);x.fill();x.fillStyle='#3a2a08';x.beginPath();x.arc(0,12,10,0,6.3);x.fill();x.fillRect(-4,14,8,22);});
const lockSp=new T.Sprite(new T.SpriteMaterial({map:lockTex,transparent:true,opacity:0,depthTest:false,toneMapped:false}));lockSp.scale.set(1.5,1.5,1);lockSp.position.set(0,3.25,.9);lockSp.renderOrder=20;S.add(lockSp);
const lockGlow=new T.Sprite(new T.SpriteMaterial({map:bloomTex,color:0xffcf7a,transparent:true,opacity:0,blending:T.AdditiveBlending,depthTest:false,depthWrite:false,toneMapped:false}));lockGlow.scale.set(4,4,1);lockGlow.position.copy(lockSp.position);lockGlow.renderOrder=19;S.add(lockGlow);
/* floor labels (3D text sprites beside the building) */
function label(txt,sub,gold){return cvTex(1024,256,(x,w,h)=>{x.font='600 64px Unbounded, Manrope, sans-serif';x.textBaseline='middle';x.shadowColor=gold?'rgba(176,102,255,.9)':'rgba(0,0,0,.8)';x.shadowBlur=gold?26:10;
  x.fillStyle=gold?'#e9d6ff':'#f4efe6';x.fillText(txt,20,92);x.font='600 40px Manrope, sans-serif';x.shadowBlur=gold?18:6;x.fillStyle=gold?'#c79bff':'rgba(244,239,230,.75)';x.fillText(sub,22,176);
  x.strokeStyle=gold?'#b066ff':'rgba(244,239,230,.6)';x.lineWidth=4;x.beginPath();x.moveTo(0,30);x.lineTo(0,226);x.stroke();});}
[[1.6,'Přízemí','prázdné',false],[6.9,'2. patro','prázdné',false],[FL+1.5,'1. patro','Black Box · jen vy dva',true]].forEach(([y,a,b,g],i)=>{i=i;
  const sp=new T.Sprite(new T.SpriteMaterial({map:label(a,b,g),transparent:true,opacity:0,depthTest:false,toneMapped:false}));sp.scale.set(4.4,1.1,1);sp.position.set(W/2+2.9,y,1.6);sp.renderOrder=21;S.add(sp);STORY.labels.push({sp,t:6.5+i*.45});});
/* a few more neighbouring houses whose windows go dark too */
[[14,5,-6,7,9,10],[-14,4,7,8,8,9],[24,4.5,-14,8,9,8]].forEach(([x,y,z,w,h,d])=>{const m=new T.MeshStandardMaterial({color:0x8a8e98,map:winTex(),roughness:.9,emissive:0xffffff,emissiveMap:winTex(),emissiveIntensity:.3});
  const b=new T.Mesh(new T.BoxGeometry(w,h,d),m);b.position.set(x,h/2,z);S.add(b);STORY.towns.push(m);});
/* sky stars */
const sg=new T.BufferGeometry(),sp=[];for(let i=0;i<900;i++){const th=Math.random()*Math.PI*2,ph=Math.random()*Math.PI*.42,r=150;sp.push(r*Math.sin(ph)*Math.cos(th),r*Math.cos(ph),r*Math.sin(ph)*Math.sin(th));}
sg.setAttribute('position',new T.Float32BufferAttribute(sp,3));S.add(new T.Points(sg,new T.PointsMaterial({color:0xffffff,size:.5,transparent:true,opacity:.85,fog:false})));

/* warm halo around each lit window */
const halos=[];warm.forEach(m=>{const g=m.userData.g;if(!g)return;const p=new T.Vector3();g.updateMatrixWorld(true);g.localToWorld(p.set(0,.6,.15));const sp=glow(0xffc070,1.9,p.x,p.y,p.z,0);sp.renderOrder=8;halos.push([sp,m]);});
/* ---------- camera & timeline ---------- */
const focus=new T.Vector3(0,FL+1.0,BK+.6);
const path=new T.CatmullRomCurve3([new T.Vector3(9.2,6.4,15.5),new T.Vector3(4,FL+2.9,7),new T.Vector3(.6,FL+1.9,RCZ+6.2),new T.Vector3(bedX,FL+1.62,FR-.25)]);
const endT=new T.Vector3(0,4.6,-5.5),look=new T.Vector3(),orbC=new T.Vector3(0,0,-D/2);
const DUR=13.6,TA=4.6,TC=7.6,TB=6.4,TE=19.8,TR=5.2,TA2=TC+TB,lookW=new T.Vector3(0,FL+1.35,BK),ease=t=>t<.5?4*t*t*t:1-Math.pow(-2*t+2,3)/2;
const subs=[[.6,'Pátek večer. V budově je ještě živo.'],[4.6,'22:00. Budova se zamyká.'],[6.3,'Nikdo jiný už tu není.'],[11.6,'Jen vy dva. Do půlnoci.'],[TE+.6,'Celá budova je jen vaše.']];
let t0=0,run=false,vis=false,raf=0,curSub=-1,RMp=false;
const wash=document.getElementById('goldWash'),pv=new T.Vector3(),pu=new T.Vector3();
window.bb3dGold=true;
const farDir=path.getPoint(0).sub(new T.Vector3(0,4.5,-D/2)).multiplyScalar(.42);const endP=path.getPoint(0),endR=Math.hypot(endP.x-orbC.x,endP.z-orbC.z),endA=Math.atan2(endP.x-orbC.x,endP.z-orbC.z);
/* moon – upper left of the final wide shot (procedural, near-side layout) */
(function(){const camEnd=path.getPoint(0).add(farDir),tgt=endT.clone().add(new T.Vector3(0,-1.6,0)),f=tgt.clone().sub(camEnd).normalize(),up=new T.Vector3(0,1,0),left=up.clone().cross(f).normalize();
 const pos=camEnd.clone().addScaledVector(f,120).addScaledVector(left,30).addScaledVector(up,38);
 const N=512,C=N/2,MR=118,mc=document.createElement('canvas');mc.width=mc.height=N;const x=mc.getContext('2d');
 let sd=7;const rnd=()=>(sd=(sd*16807)%2147483647)/2147483647;
 const P=new Uint8Array(512);for(let i=0;i<256;i++)P[i]=i;for(let i=255;i>0;i--){const j=Math.floor(rnd()*(i+1));[P[i],P[j]]=[P[j],P[i]];}for(let i=0;i<256;i++)P[i+256]=P[i];
 const fd=t=>t*t*(3-2*t),hs=(a,b)=>P[P[a&255]+(b&255)]/255;
 const vn=(u,v)=>{const a=Math.floor(u),b=Math.floor(v),fu=fd(u-a),fv=fd(v-b);return (hs(a,b)*(1-fu)+hs(a+1,b)*fu)*(1-fv)+(hs(a,b+1)*(1-fu)+hs(a+1,b+1)*fu)*fv;};
 const fbm=(u,v)=>{let s=0,a=.5;for(let o=0;o<5;o++){s+=a*vn(u,v);u*=2.03;v*=2.03;a*=.5;}return s;};
 /* maria roughly where they are on the real near side (u,v in -1..1, v down) */
 const MA=[[-.30,-.38,.34],[-.05,-.30,.16],[.14,-.36,.20],[.30,-.06,.24],[.62,-.28,.12],[.50,.20,.15],[.34,.34,.10],[-.58,-.02,.40],[-.46,.34,.26],[-.16,.30,.18],[-.68,.46,.12],[-.02,-.60,.12],[.10,.06,.10]];
 const CR=[];for(let i=0;i<34;i++){const r=Math.pow(rnd(),3)*.055+.01,a=rnd()*6.283,d=Math.sqrt(rnd())*.95;CR.push([Math.cos(a)*d,Math.sin(a)*d,r]);}
 CR.push([-.12,.64,.035,1]);CR.push([-.40,.02,.028,1]);
 const im=x.createImageData(N,N),D=im.data;
 for(let py=0;py<N;py++)for(let px=0;px<N;px++){const u=(px-C)/MR,v=(py-C)/MR,rr=u*u+v*v;if(rr>1)continue;
  const nz=fbm(u*3.2+11,v*3.2+5),nf=fbm(u*14,v*14);
  const wu=u+(fbm(u*2.4+3,v*2.4+9)-.5)*.42,wv=v+(fbm(u*2.4+17,v*2.4+1)-.5)*.42;let m=0;for(const [mx,my,ms] of MA){const d=Math.hypot(wu-mx,wv-my)/(ms*1.3);m+=Math.exp(-d*d*1.6);}m=fd(Math.min(1,Math.max(0,(m-.3)/.8)))*(.8+fbm(u*6,v*6)*.4);
  let b=.84+(nf-.5)*.12+(nz-.5)*.12+(fbm(u*30,v*30)-.5)*.06;
  for(const c of CR){const d=Math.hypot(u-c[0],v-c[1])/c[2];if(d<1.3){const sh=((u-c[0])+(v-c[1]))/c[2]*.5;if(d<.85)b+=(.025*sh-.025)*(1-m*.4);else if(d<1.1)b+=.05-.04*sh;else b+=.015;}
   if(c[3]){const ang=Math.atan2(v-c[1],u-c[0]),ray=Math.pow(Math.max(0,Math.sin(ang*9+c[0]*40)),18);if(d>1&&d<8)b+=ray*.12*(1-d/8);}}
  const mu=Math.sqrt(1-rr),limb=.55+.45*Math.pow(mu,.5);b*=limb;b=Math.max(0,Math.min(1,b));
  const e=Math.min(1,(1-Math.sqrt(rr))*MR/1.2),o=(py*N+px)*4;D[o]=b*248;D[o+1]=b*244;D[o+2]=b*232;D[o+3]=255*e;}
 x.putImageData(im,0,0);
 const hc=document.createElement('canvas');hc.width=hc.height=N;const h=hc.getContext('2d');
 let g=h.createRadialGradient(C,C,MR*.9,C,C,C);g.addColorStop(0,'rgba(225,228,245,.32)');g.addColorStop(.25,'rgba(190,195,235,.12)');g.addColorStop(1,'rgba(160,165,230,0)');h.fillStyle=g;h.fillRect(0,0,N,N);h.drawImage(mc,0,0);
 const moon=new T.Sprite(new T.SpriteMaterial({map:sRGB(new T.CanvasTexture(hc)),transparent:true,depthWrite:false,fog:false,toneMapped:false}));moon.scale.set(24,24,1);moon.position.copy(pos);S.add(moon);
 const ml=new T.DirectionalLight(0xc8ccff,.25);ml.position.copy(pos);S.add(ml);})();
[wallM,trimM,plinM,roofM,glassM].forEach(m=>{m.transparent=true;XR.push(m);});
function setX(o){XR.forEach(m=>{m.opacity=o;m.depthWrite=o>.97;});frameM.opacity=.85*o;}
let lw=0,lh=0;function size(){const w=film.clientWidth,h=film.clientHeight;if(!w||!h||(w===lw&&h===lh))return;lw=w;lh=h;R.setSize(w,h,false);C.aspect=w/h;C.updateProjectionMatrix();}
function frame(now){
  const t=run?(now-t0)/1000:0,cl=v=>Math.min(1,Math.max(0,v)),k0=ease(cl((t-TC)/TB)),k=t<TE?k0:k0*(1-ease(cl((t-TE)/TR))),xr=1-k,hq=t<TA2?0:t<TE?ease(cl((t-TA2)/1.8)):k;
  if(t<TC){const a=-.22+.22*ease(cl(t/TC));C.position.set(orbC.x+Math.sin(endA+a)*endR,endP.y,orbC.z+Math.cos(endA+a)*endR);look.copy(endT);}
  else if(k<1){path.getPoint(k,C.position);look.copy(endT).lerp(focus,ease(cl(k*1.15)));
    if(t>=TE){const r=1-k;C.position.x+=.8*k;C.position.addScaledVector(farDir,r*r*(3-2*r));look.y-=(MOB?2.4:1.6)*r;}}
  else{const e=path.getPoint(1),u=cl((t-TC-TB)/(TE-TC-TB));C.position.set(e.x+.8*(u*u*(3-2*u)),e.y,e.z);look.copy(focus);}
  setX(t<TC-1.2?1:Math.max(.05,1-.78*ease(cl((t-TC+1.2)/1.4))-.17*cl((k-.35)/.2)));const offW=t<TE?cl((t-TC-.3)/.8):0,endB=t<TE?1:1+.3*cl((t-TE-1)/2),fk=cl((t-TC+.8)/1)*(1-cl((k-.25)/.15))*(1-offW);roomEdge.material.opacity=Math.min(1,.95*fk*endB);roomGlow.material.opacity=.16*fk*endB*(.8+.2*Math.sin(t*2));roomBeam.material.opacity=.5*fk*endB*(.85+.15*Math.sin(t*2));tubB.material.opacity=.45+.3*Math.sin(t*5);C.position.y+=.45*hq;look.lerp(lookW,.6*hq);C.fov=46+12*k+24*hq;if(C.aspect<1){C.fov=2*Math.atan(Math.tan(C.fov*Math.PI/360)/C.aspect)*180/Math.PI;}C.updateProjectionMatrix();C.lookAt(look);
  cube.rotation.y=t*.45;if(window.__tubWater){window.__tubWater.offset.set(t*.03,t*.02);}{const R0=window.__room;R0.starPts.material.opacity=.75+.25*Math.sin(t*3.1);R0.starPts2.material.opacity=.25+.15*Math.sin(t*2.3+1);R0.scrGlow.material.opacity=.2+.05*Math.sin(t*1.7);}cube.rotation.x=.55+Math.sin(t*.7)*.08;
  f1.userData.b.position.y=(t*.035)%.06;f2.userData.b.position.y=((t+.7)*.035)%.06;refl.rotation.z=-couple.rotation.z;dustP.position.y=(t*.03)%.12;dustP.rotation.y=Math.sin(t*.2)*.04;
  const pulse=.88+Math.sin(t*2)*.12;gCube.material.opacity=.9*pulse;gWin.material.opacity=(.25+.6*fk)*endB*pulse*(1-cl((k-.6)/.2))*(1-offW);gWin.scale.setScalar(7+9*fk);
  for(const m of warm){if(!m.userData.lit){m.opacity=0;continue;}const u=t-m.userData.t;m.opacity=u<0?.6:u<.25?(Math.random()>.4?.6:.08):Math.max(0,.6-(u-.25)*3);const gg=m.userData.g;if(gg){const dd=gg.position.distanceTo(C.position);m.opacity*=Math.min(1,Math.max(0,(dd-2.5)/4));}}
  for(const [sp,m] of halos){const dd=sp.position.distanceTo(C.position);sp.material.opacity=m.opacity*.6*Math.min(1,Math.max(0,(dd-2)/4));}
  let s=-1;subs.forEach((x,i)=>{if(t>=x[0])s=i;});if(s!==curSub){curSub=s;sub.classList.remove('on');if(s>=0)setTimeout(()=>{sub.textContent=subs[s][1];sub.classList.add('on');},s===0?0:450);}
  {const sec=Math.floor(79180+t*4.35),hh=Math.floor(sec/3600)%24,mm=Math.floor(sec/60)%60,ss=sec%60;if(sec!==lastSec){lastSec=sec;ckH.textContent=String(hh).padStart(2,'0')+':'+String(mm).padStart(2,'0');ckS.textContent=':'+String(ss).padStart(2,'0');}
   clk.classList.toggle('gold',sec>=79200);clk.classList.toggle('hit',sec>=79200&&sec<79206);}
  nbM.emissiveIntensity=.25*(1-cl((t-4.9)/2.6));STORY.towns.forEach((m,i)=>m.emissiveIntensity=.3*(1-cl((t-5.2-i*.5)/2)));
  (window.__lamps||[]).forEach(o=>{const d=1-.7*cl((t-6)/2);o.l.intensity=1.5*d;o.gs.material.opacity=.75*d;});
  STORY.people.forEach(p=>{const u=(t-p.t)/6.5;p.g.visible=u>0&&u<1;if(!p.g.visible)return;p.g.position.copy(p.a).lerp(p.b,u);p.g.rotation.y=Math.atan2(p.b.x-p.a.x,p.b.z-p.a.z);const sw=Math.sin(t*7+p.t*3)*.38;p.legs[0].rotation.x=sw;p.legs[1].rotation.x=-sw;p.g.position.y=Math.abs(Math.sin(t*7+p.t*3))*.03;});
  {const lk=cl((t-TA)/.35)*(1-cl((t-TC+.6)/.6));lockSp.material.opacity=lk;lockSp.scale.setScalar(1.5*(1+.3*(1-cl((t-TA)/.35))));lockGlow.material.opacity=lk*(.55+.25*Math.sin(t*4));}
  STORY.labels.forEach(o=>{o.sp.material.opacity=cl((t-o.t)/.5)*(1-cl((k-.12)/.1));});
  film.classList.toggle('end',t>DUR+.6);film.classList.toggle('fin',t>TE+TR*.5);
  if(window.__room){scr.material.color.setScalar(1);}
  {/* the room wakes up as the camera flies in */
   const L=(a,b)=>cl((t-a-TA2)/(b-a)),off=offW,lv=w=>1-off+off*w;
   const wl=lv(L(.4,1.3));LEDM.forEach(([m,c])=>m.color.copy(c).multiplyScalar(.04+.96*wl));
   LEDB.forEach(sp=>{sp.material.opacity=sp.userData.o0*lv(cl((t-TA2-.4-sp.userData.d*1.1)/.3));});
   starG.setDrawRange(0,Math.floor(900*lv(ease(L(1.1,2.9)))));
   const tw0=L(2.3,3.0),tw=lv(tw0<1&&tw0>0?(Math.random()>.35?tw0:tw0*.2):tw0);tubL.intensity=1.3*tw;tubB.material.opacity*=tw;TUBB.forEach(sp=>sp.material.opacity=sp.userData.o0*tw);TUBW.forEach(m=>m.opacity=.7*tw);TUBE.forEach(([m,e])=>m.emissiveIntensity=e*tw);
   const sw0=L(3.0,3.6),sw=lv(sw0<1&&sw0>0?(Math.random()>.5?sw0:sw0*.25):sw0);scr.material.color.setScalar(sw*sw);scrGlow.material.opacity*=sw;scrL.intensity=2.4*sw;
   const aw=.12+.88*lv(L(.4,3.6));roomL.intensity=.9*aw;fillL.intensity=.45*aw;leftL.intensity=.6*aw;}
  R.render(S,C);
  if(wash&&document.documentElement.classList.contains('gold')){const b=cv.getBoundingClientRect();pv.copy(focus).project(C);pu.copy(focus).add(new T.Vector3(0,1+1.8*k,0)).project(C);
    const x=b.left+(pv.x+1)/2*b.width,y=b.top+(1-pv.y)/2*b.height,r=Math.abs((pv.y-pu.y)/2*b.height);
    wash.style.setProperty('--hx',x+'px');wash.style.setProperty('--hy',y+'px');wash.style.setProperty('--hr',Math.max(10,Math.min(900,r*1.15))+'px');}
  if(vis&&!RMp)raf=requestAnimationFrame(frame);
}
function start(){if(RMp){run=true;t0=performance.now()-(TE+TR+1)*1000;film.classList.add('run');size();frame(performance.now());return;}
  run=true;t0=performance.now();film.classList.add('run');film.classList.remove('end');curSub=-1;sub.classList.remove('on');}
const grepB=film.querySelector('.grep');grepB.addEventListener('click',()=>{if(RMp){RMp=false;grepB.textContent='↻ Přehrát znovu';}film.classList.remove('fin','end');start();cancelAnimationFrame(raf);if(vis)raf=requestAnimationFrame(frame);});
function reset(){run=false;film.classList.remove('run','end','fin');sub.classList.remove('on');curSub=-1;}
new IntersectionObserver(es=>es.forEach(e=>{vis=e.isIntersecting;
  if(e.intersectionRatio>=.5&&!run)start();
  if(!e.isIntersecting)reset();
  cancelAnimationFrame(raf);if(vis){size();raf=requestAnimationFrame(frame);}
}),{threshold:[0,.5]}).observe(film);
if('ResizeObserver' in window)new ResizeObserver(()=>{size();if(!vis||RMp)R.render(S,C);}).observe(film);
size();
}catch(err){console.error('Gold Time film:',err);window.__gtFallback();}})();
}catch(e){console.error(e)}
;
try{
(function(){const i=document.querySelector('.concept-img img'),hi=document.getElementById('heroImg');if(i&&hi){const u=getComputedStyle(hi).backgroundImage.slice(5,-2);if(u)i.src=u;}})();
}catch(e){console.error(e)}
;
try{
/* fit GOLD TIME to its column */(function(){const m=document.querySelector('.gt-mega');if(!m)return;
function fit(){m.style.fontSize='100px';const w=m.clientWidth,tw=m.firstChild.getBoundingClientRect().width;if(tw&&w)m.style.fontSize=Math.min(190,Math.floor(100*w/tw*.97))+'px';}
fit();addEventListener('resize',fit);document.fonts&&document.fonts.ready.then(fit);})();
}catch(e){console.error(e)}
;
try{
/* Gold Time wow: reveals, count-up, golden dust */
(function(){const RM=false;
 const io=new IntersectionObserver(es=>es.forEach(e=>{if(e.isIntersecting){e.target.classList.add('in');if(e.target.id==='gtStats')count(e.target);io.unobserve(e.target);}}),{threshold:.35});
 ['.gt-mega','#gtTl','.gt-feats','.gt-quote'].forEach(s=>{const el=document.querySelector(s);if(el)io.observe(el);});
 function count(box){box.querySelectorAll('b[data-to]').forEach(b=>{const to=+b.dataset.to;if(RM||!to){b.textContent=to;return;}const t0=performance.now(),D=1600;
   (function f(n){const k=Math.min(1,(n-t0)/D),e=1-Math.pow(1-k,3);b.textContent=Math.round(to*e);if(k<1)requestAnimationFrame(f);})(t0);});}
 const c=document.getElementById('gtDust'),sec=document.getElementById('goldtime');if(!c||!sec)return;const x=c.getContext('2d');let W=0,H=0,P=[],vis=false,raf=0;
 function size(){const d=Math.min(devicePixelRatio||1,2);W=c.clientWidth;H=c.clientHeight;c.width=W*d;c.height=H*d;x.setTransform(d,0,0,d,0,0);
   const n=Math.round(Math.min(140,W*H/9000));P=Array.from({length:n},()=>({x:Math.random()*W,y:Math.random()*H,r:.4+Math.random()*1.8,v:.15+Math.random()*.45,w:Math.random()*6.28,a:.25+Math.random()*.6}));}
 function draw(){x.clearRect(0,0,W,H);for(const p of P){p.y-=p.v;p.w+=.01;p.x+=Math.sin(p.w)*.25;if(p.y<-10){p.y=H+10;p.x=Math.random()*W;}
   const g=x.createRadialGradient(p.x,p.y,0,p.x,p.y,p.r*4);g.addColorStop(0,`rgba(255,226,160,${p.a})`);g.addColorStop(1,'rgba(255,226,160,0)');x.fillStyle=g;x.beginPath();x.arc(p.x,p.y,p.r*4,0,6.283);x.fill();}
   if(vis)raf=requestAnimationFrame(draw);}
 new IntersectionObserver(es=>{vis=es[0].isIntersecting;cancelAnimationFrame(raf);if(vis){if(!W)size();raf=requestAnimationFrame(draw);}}).observe(sec);
 addEventListener('resize',()=>{if(vis)size();});})();
}catch(e){console.error(e)}
;
try{
/* Gold Time timeline follows the scroll */
(function(){const t=document.getElementById('gtTl');if(!t)return;const li=[...t.querySelectorAll('li:not(.tl-track)')];let q=0;
 function upd(){q=0;const r=t.getBoundingClientRect(),vh=innerHeight;const p=Math.max(0,Math.min(1,(vh*.82-r.top)/(r.height+vh*.35)));
  t.style.setProperty('--tlp',p.toFixed(4));const L=li[li.length-1];if(L)t.style.setProperty('--tlh',(L.offsetTop-li[0].offsetTop)+'px');li.forEach((l,i)=>l.classList.toggle('on',p>=i/(li.length-1)-.001||false));}
 addEventListener('scroll',()=>{if(!q)q=requestAnimationFrame(upd);},{passive:true});addEventListener('resize',upd);upd();})();
}catch(e){console.error(e)}
;
try{
/* touch: play hover effects when a card is in the middle of the screen */
(function(){if(!matchMedia('(hover:none)').matches)return;const S=[".sl", ".spec", ".step", ".feat", ".for", ".player", ".hr", ".gold-up", ".gt-f", ".concept-img", ".gal button", ".vopt.g", ".occ span", ".pill", ".qa"];const els=[...document.querySelectorAll(S.join(','))];if(!els.length)return;
 const io=new IntersectionObserver(es=>es.forEach(e=>{e.target.classList.toggle('tv',e.isIntersecting);if(e.isIntersecting&&e.target.classList.contains('sl')){const r=e.target.getBoundingClientRect();e.target.style.setProperty('--mx',(r.width/2)+'px');e.target.style.setProperty('--my',(r.height*.35)+'px');}}),{rootMargin:'-30% 0px -30% 0px'});
 els.forEach(el=>io.observe(el));})();
}catch(e){console.error(e)}
;
try{
/* voucher switch thumb */(function(){const sw=document.querySelector('.vswitch');if(!sw)return;const g=document.getElementById('vo2');const u=()=>sw.classList.toggle('is-gold',g.getAttribute('aria-pressed')==='true');sw.querySelectorAll('.vopt').forEach(b=>b.addEventListener('click',()=>setTimeout(u,0)));u();})();
/* mobile hero: gentle automatic pan until the visitor touches it (GPU transform, sub-pixel smooth) */
(function(){const par=document.getElementById('heroPar'),img=document.getElementById('heroImg'),hero=document.getElementById('hero');if(!par||!img||!matchMedia('(pointer:coarse)').matches)return;
 let hold=performance.now()+1800,run=false,base=0,amp=0,t0=0,vis=true,dx=0;img.style.willChange='transform,translate';
 const off=()=>{if(!run)return;run=false;const sl=Math.round(base-dx);img.style.translate='';par.scrollLeft=sl;};
 const stop=()=>{off();hold=performance.now()+6000;};['touchstart','pointerdown','wheel'].forEach(e=>par.addEventListener(e,stop,{passive:true}));
 new IntersectionObserver(es=>{vis=es[0].isIntersecting;if(!vis)off();}).observe(hero);
 (function f(n){requestAnimationFrame(f);if(!vis||n<hold)return;
  if(!run){const max=par.scrollWidth-par.clientWidth;if(max<20)return;amp=Math.min(max*.45,170);base=Math.max(amp,Math.min(max-amp,par.scrollLeft));par.scrollLeft=base;t0=n;run=true;}
  const k=Math.min(1,(n-t0)/1500),e=k*k*(3-2*k);dx=-Math.sin((n-t0)/3400)*amp*e;img.style.translate=dx.toFixed(2)+'px 0';})(performance.now());})();
}catch(e){console.error(e)}
;
try{
/* "Darovat Gold Time" preselects the Gold voucher */
document.querySelectorAll('a[href="#poukazy"]').forEach(a=>{if(/gold/i.test(a.textContent))a.addEventListener('click',()=>{const g=document.getElementById('vo2');if(g&&g.getAttribute('aria-pressed')!=='true')g.click();});});
/* contact: align the Gold Time card with the Gold Time row on the left */
(function(){const card=document.querySelector('.book-call .gold-up'),row=document.querySelector('.hr.goldrow');if(!card||!row)return;
 function al(){card.style.marginTop='';if(innerWidth<=760)return;const d=row.getBoundingClientRect().bottom-card.getBoundingClientRect().bottom;if(d>0)card.style.marginTop=(parseFloat(getComputedStyle(card).marginTop)+d)+'px';}
 al();addEventListener('resize',al);document.fonts&&document.fonts.ready.then(al);addEventListener('load',al);})();
}catch(e){console.error(e)}
;
try{
/* keep the film cut-out of the gold filter glued to the film every frame */
(function(){const w=document.getElementById('goldWash'),r=document.documentElement;if(!w)return;let last='';
 (function f(){if(window.__gwStatic)return;requestAnimationFrame(f);const on=r.classList.contains('gold')||getComputedStyle(w).opacity>0.01;if(!on)return;
  const el=document.querySelector('.gt-film')||document.getElementById('gtBld');if(!el)return;const c=el.getBoundingClientRect(),nv=document.getElementById('nav'),hb=nv?nv.getBoundingClientRect().bottom:0,top=Math.max(c.top,hb),hh=Math.max(0,c.bottom-top);
  const k=c.left.toFixed(1)+'|'+top.toFixed(1)+'|'+c.width.toFixed(1)+'|'+hh.toFixed(1);if(k===last)return;last=k;
  w.style.setProperty('--fx',c.left+'px');w.style.setProperty('--fy',top+'px');w.style.setProperty('--fw',c.width+'px');w.style.setProperty('--fh',hh+'px');
  const R=parseFloat(getComputedStyle(el).borderTopLeftRadius)||0,W=c.width,H=hh,rt=top>c.top+.5?0:R,mk=W+'|'+H+'|'+rt;if(mk!==w.__mk&&W>0&&H>0){w.__mk=mk;const d='M'+rt+' 0H'+(W-rt)+'Q'+W+' 0 '+W+' '+rt+'V'+(H-R)+'Q'+W+' '+H+' '+(W-R)+' '+H+'H'+R+'Q0 '+H+' 0 '+(H-R)+'V'+rt+'Q0 0 '+rt+' 0Z';
   w.style.setProperty('--fm','url("data:image/svg+xml,'+encodeURIComponent("<svg xmlns='http://www.w3.org/2000/svg' width='"+W+"' height='"+H+"'><path d='"+d+"'/></svg>")+'")');}})();})();
}catch(e){console.error(e)}
;
try{
/* phones: gold filter scrolls natively with the page, film cut-out never moves -> no flicker */
(function(){if(!matchMedia('(max-width:760px), (pointer:coarse)').matches)return;window.__gwStatic=true;
 const w=document.getElementById('goldWash'),s=document.getElementById('goldtime');if(!w||!s)return;w.classList.add('gw-abs');let last='',mk='';
 const nw=document.createElement('div');nw.className='gold-wash-nav';nw.setAttribute('aria-hidden','true');w.after(nw);
 const nh=()=>{const nv=document.getElementById('nav');nw.style.setProperty('--nh',(nv?Math.max(0,nv.getBoundingClientRect().bottom):0)+'px');};nh();addEventListener('resize',nh);addEventListener('load',nh);
 function upd(){const el=document.querySelector('.gt-film')||document.getElementById('gtBld');if(!el)return;const sy=scrollY,sr=s.getBoundingClientRect(),vh=Math.max(innerHeight,screen.height||0);
  w.style.setProperty('--gwh','0px');const docH=document.documentElement.scrollHeight;
  const top=Math.max(0,sr.top+sy-vh),h=Math.min(sr.bottom+sy+vh,docH)-top;
  const c=el.getBoundingClientRect(),R=parseFloat(getComputedStyle(el).borderTopLeftRadius)||0,fx=c.left,fy=c.top+sy-top,W=c.width,H=c.height;
  w.style.setProperty('--gwt',top+'px');w.style.setProperty('--gwh',h+'px');
  const k=[fx,fy,W,H].map(v=>v.toFixed(1)).join('|');if(k===last)return;last=k;
  w.style.setProperty('--fx',fx+'px');w.style.setProperty('--fy',fy+'px');w.style.setProperty('--fw',W+'px');w.style.setProperty('--fh',H+'px');
  const m=W.toFixed(0)+'|'+H.toFixed(0)+'|'+R;if(m!==mk&&W>0&&H>0){mk=m;const d='M'+R+' 0H'+(W-R)+'Q'+W+' 0 '+W+' '+R+'V'+(H-R)+'Q'+W+' '+H+' '+(W-R)+' '+H+'H'+R+'Q0 '+H+' 0 '+(H-R)+'V'+R+'Q0 0 '+R+' 0Z';
   w.style.setProperty('--fm','url("data:image/svg+xml,'+encodeURIComponent("<svg xmlns='http://www.w3.org/2000/svg' width='"+W+"' height='"+H+"'><path d='"+d+"'/></svg>")+'")');}}
 upd();addEventListener('load',upd);addEventListener('resize',upd);document.fonts&&document.fonts.ready.then(upd);
 if('ResizeObserver' in window){const ro=new ResizeObserver(upd);ro.observe(s);ro.observe(document.body);const b=document.getElementById('gtBld');b&&ro.observe(b);}
 let n=0;const t=setInterval(()=>{upd();if(++n>20)clearInterval(t);},500);})();
}catch(e){console.error(e)}
;
try{
/* Gold Time: zlatá osa jede se scrollem */
(function(){const box=document.getElementById('gtTl');if(!box||!box.classList.contains('gtc'))return;
 const list=box.querySelector('.gtc-list'),li=[...list.querySelectorAll('li')];let q=0;
 function upd(){q=0;const vh=innerHeight,r=list.getBoundingClientRect(),dots=li.map(l=>{const d=l.querySelector('i');return d.offsetTop+l.offsetTop+d.offsetHeight/2;});
  const f=dots[0],last=dots[dots.length-1],max=last-f;list.style.setProperty('--lt',f+'px');list.style.setProperty('--lmax',max+'px');
  const p=Math.max(0,Math.min(1,(vh*.6-(r.top+f))/max)),h=max*p;list.style.setProperty('--lh',h+'px');
  li.forEach((l,i)=>l.classList.toggle('lit',dots[i]-f<=h+2));}
 addEventListener('scroll',()=>{if(!q)q=requestAnimationFrame(upd);},{passive:true});addEventListener('resize',upd);upd();})();
}catch(e){console.error(e)}
;
try{
/* mobil: popisky v úvodní fotce až po ~1,5 s čisté fotky */
(function(){const b=document.body;(function w(){if(!window.bbGo)return setTimeout(w,100);setTimeout(()=>b.classList.add('hs-show'),3600);})();setTimeout(()=>b.classList.add('hs-show'),9000);})();
}catch(e){console.error(e)}
;
try{
/* patička: rychlá objednávka (e-mail, telefon, volitelně poukaz) */
(function(){const D=document.getElementById('qoDlg');if(!D)return;const F=document.getElementById('qoForm'),$=id=>document.getElementById(id);
  const TEL='<a href="tel:+420737222266">+420 737 22 22 66</a>';
  const kind=()=>F.querySelector('input[name="qoKind"]:checked').value,wantV=()=>$('qoV').checked;
  function paint(){const g=kind()==='gold',v=wantV();D.classList.toggle('is-gold',g);$('qoVBox').hidden=!v;$('qoName').required=v;
    $('qoFlow').innerHTML=['Odešlete objednávku.','Fakturu vám hned pošleme e-mailem.',v?'Po připsání platby přijde potvrzení o platbě a dárkový poukaz.':'Po připsání platby přijde potvrzení o platbě.'].map(t=>'<li>'+t+'</li>').join('')+'<li class="vo-last">Termín si domluvíte telefonem na '+TEL+'.</li>';}
  F.querySelectorAll('input[name="qoKind"]').forEach(r=>r.addEventListener('change',paint));
  $('qoV').addEventListener('change',()=>{paint();if(wantV())setTimeout(()=>$('qoName').focus({preventScroll:false}),60);else $('qoName').closest('.field').classList.remove('vo-field-bad');});
  F.querySelectorAll('input').forEach(i=>i.addEventListener('input',()=>i.closest('.field')&&i.closest('.field').classList.remove('vo-field-bad')));
  let y=0;
  function open(){$('qoStep').hidden=false;$('qoDone').hidden=true;$('qoErr').textContent='';paint();y=window.scrollY||document.documentElement.scrollTop||0;document.body.style.top=(-y)+'px';
    document.documentElement.classList.add('vo-lock');if(D.showModal)D.showModal();else D.setAttribute('open','');D.scrollTop=0;if(matchMedia('(min-width:601px)').matches)setTimeout(()=>$('qoMail').focus({preventScroll:true}),60);}
  function close(){if(D.close)D.close();else{D.removeAttribute('open');D.dispatchEvent(new Event('close'));}}
  D.addEventListener('close',()=>{document.documentElement.classList.remove('vo-lock');document.body.style.top='';window.scrollTo(0,y);});
  D.addEventListener('click',e=>{if(e.target===D)close();});
  $('qoOpen').addEventListener('click',open);$('qoX').addEventListener('click',close);$('qoOk').addEventListener('click',close);
  F.addEventListener('submit',e=>{e.preventDefault();const err=$('qoErr');let bad=null;const v=id=>$(id).value.trim();
    const need=['qoMail','qoTel'].concat(wantV()?['qoName']:[]);
    need.forEach(id=>{const i=$(id),val=i.value.trim(),ok=id==='qoMail'?/^\S+@\S+\.\S+$/.test(val):id==='qoTel'?/^[+0-9 ()\-\/]{6,}$/.test(val):val.length>=2;i.closest('.field').classList.toggle('vo-field-bad',!ok);if(!ok&&!bad)bad=i;});
    if(bad){err.textContent=bad.id==='qoName'?'Napište jméno, které má být na poukazu.':'Doplňte prosím zvýrazněné údaje.';bad.scrollIntoView({block:'center',behavior:'smooth'});bad.focus({preventScroll:true});return;}err.textContent='';
    const k=kind(),kl=k==='gold'?'Gold Time (2 900 Kč)':'Standard – 2 hodiny (2 000 Kč)',wv=wantV();
    const lines=['Dobrý den,','','objednávám: '+kl,'Zaslat dárkový poukaz: '+(wv?'ano – jméno na poukaz: '+v('qoName')+(v('qoMsg')?', věnování: '+v('qoMsg'):''):'ne'),'','E-mail: '+v('qoMail'),'Telefon: '+v('qoTel'),'','Prosím o zaslání faktury. Děkuji'];
    const viaMail=()=>{location.href='mailto:'+'pavlatp@email.cz'+'?subject='+encodeURIComponent('Objednávka – '+kl)+'&body='+encodeURIComponent(lines.join('\n'));};
    const esc=t=>t.replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
    const done=(h,p)=>{$('qoDoneH').textContent=h;$('qoDoneP').innerHTML=p;$('qoStep').hidden=true;$('qoDone').hidden=false;D.scrollTop=0;$('qoOk').focus({preventScroll:true});};
    const send=$('qoSend');if(send.disabled)return;send.disabled=true;const lbl=send.textContent;send.textContent='Odesílám…';
    const payload={way:'objednavka',kind:k,phone:v('qoTel'),email:v('qoMail'),voucher:wv?'1':'0',for:wv?v('qoName'):'',msg:wv?v('qoMsg'):'',name:wv?v('qoName'):'',web:$('qoWeb').value};
    const ctl=new AbortController(),tm=setTimeout(()=>ctl.abort(),30000);
    fetch('objednavka.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload),signal:ctl.signal})
      .then(r=>r.json().catch(()=>({ok:false})).then(j=>({r,j})))
      .then(({r,j})=>{if(j.ok){const m=esc(v('qoMail'));
          if(j.mode==='invoice')done('Faktura je na cestě','Poslali jsme ji na <b>'+m+'</b>. Po připsání platby vám přijde potvrzení'+(wv?' a dárkový poukaz':'')+'.');
          else done('Objednávka přijata','Fakturu vám brzy pošleme na <b>'+m+'</b>.');return;}
        if(r.status===422||r.status===429){err.textContent=j.error||'Zkontrolujte prosím údaje.';return;}throw new Error('server');})
      .catch(()=>{viaMail();done('Skoro hotovo','Otevřeli jsme vám e-mail s objednávkou. Stačí ho odeslat a fakturu vám pošleme.');})
      .finally(()=>{clearTimeout(tm);send.disabled=false;send.textContent=lbl;});});
})();
}catch(e){console.error(e)}
;
try{
/* modaly na mobilu: při otevřené klávesnici držet modal ve viditelné části a scrollovat k poli */
(function(){const vv=window.visualViewport;const DS=[...document.querySelectorAll('.vo-dlg')];if(!vv||!DS.length)return;
  const open=()=>DS.find(d=>d.open);
  function fit(){const D=open();if(!D)return;const kb=innerHeight-vv.height>80;
    document.documentElement.style.setProperty('--vvt',(kb?vv.offsetTop:0)+'px');
    if(kb){D.style.top=vv.offsetTop+'px';D.style.height=vv.height+'px';D.style.bottom='auto';}else{D.style.top='';D.style.height='';D.style.bottom='';}}
  function show(el){const D=el.closest('.vo-dlg');if(!D)return;fit();const f=el.closest('.field,.qo-chk')||el,b=f.getBoundingClientRect(),dr=D.getBoundingClientRect();
    if(b.top<dr.top+70||b.bottom>dr.bottom-16)D.scrollBy({top:b.top-dr.top-Math.max(70,(dr.height-b.height)/3),behavior:'smooth'});}
  vv.addEventListener('resize',()=>{fit();const a=document.activeElement;if(a&&a.matches&&a.matches('.vo-dlg input,.vo-dlg textarea'))show(a);});
  vv.addEventListener('scroll',fit);
  document.addEventListener('focusin',e=>{if(e.target.closest&&e.target.closest('.vo-dlg')&&e.target.matches('input,textarea'))setTimeout(()=>show(e.target),350);});
  DS.forEach(D=>D.addEventListener('close',()=>{D.style.top='';D.style.height='';D.style.bottom='';document.documentElement.style.removeProperty('--vvt');}));
})();
}catch(e){console.error(e)}
