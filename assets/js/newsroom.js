(function () {
"use strict";

function qsa(sel,el){
 return Array.prototype.slice.call(
 (el||document).querySelectorAll(sel)
 );
}

function qs(sel,el){
 return (el||document).querySelector(sel);
}

function onReady(fn){
 if(document.readyState!=="loading") fn();
 else document.addEventListener("DOMContentLoaded",fn,{once:true});
}

function isReduce(){
 return !!(
 window.matchMedia &&
 window.matchMedia('(prefers-reduced-motion: reduce)').matches
 );
}

function getNewsroomRoots(){
 return qsa(
'.zeb-pack--newsroom,\
.zeb-pack--newsroom-scope,\
.zeb-pack--newsroom-actualites,\
.zeb-pack--newsroom-publications,\
.zeb-pack--actualites,\
.zeb-pack--publications,\
[class*="zeb-pack--newsroom-"]'
);
}

function getHomeNewsRoots(){
 return qsa('.hn-wrap');
}

function markJS(root){
 if(root && !root.classList.contains('is-js')){
  root.classList.add('is-js');
 }
}

/* AUTO TAG NEWSROOM */
function autoTagRevealNewsroom(root){

var els=qsa(
'.zn-card,.zn-featured,.zn-sidePanelBlock,\
h1,h2,h3,.zn-pageTitle,.zn-sectionTitle,.zn-title,\
.zn-text p,p,li,img,svg',
root
);

els.forEach(function(el){
 if(!el.hasAttribute('data-reveal')){
   el.setAttribute('data-reveal','up');
 }
});

}

/* AUTO STAGGER */
function autoTagStaggerNewsroom(root){

var grids=qsa('.zn-grid',root);

grids.forEach(function(grid){

 if(!grid.hasAttribute('data-stagger')){
   grid.setAttribute('data-stagger','1');
 }

 qsa('.zn-card',grid).forEach(function(card){
   if(!card.hasAttribute('data-stagger-item')){
      card.setAttribute('data-stagger-item','');
   }
 });

});

}

/* HOME AUTO TAG */
function autoTagRevealHome(root){

[
'.hn-head',
'.hn-kicker',
'.hn-title',
'.hn-nav',
'.hn-card',
'.hn-cta'
].forEach(function(sel){

 qsa(sel,root).forEach(function(el){
   if(!el.hasAttribute('data-reveal')){
      el.setAttribute('data-reveal','up');
   }
 });

});

var track=qs('.hn-track',root);

if(track && !track.hasAttribute('data-stagger')){
 track.setAttribute('data-stagger','1');
}

qsa('.hn-card',root).forEach(function(card){
 if(!card.hasAttribute('data-stagger-item')){
   card.setAttribute('data-stagger-item','');
 }
});

}

/* REVEALS */
function initReveal(root){

var els=qsa('[data-reveal]',root);

if(!els.length) return;

if(isReduce() || !('IntersectionObserver' in window)){
 els.forEach(el=>el.classList.add('is-inview'));
 return;
}

var io=new IntersectionObserver(function(entries){

entries.forEach(function(ent){

 if(!ent.isIntersecting) return;

 ent.target.classList.add('is-inview');
 io.unobserve(ent.target);

});

},{
 threshold:.16,
 rootMargin:'0px 0px -12% 0px'
});

els.forEach(function(el){
 io.observe(el);
});

setTimeout(function(){

qsa('[data-reveal]:not(.is-inview)',root)
.forEach(function(el){
el.classList.add('is-inview');
});

},2200);

}

/* STAGGER */
function initStagger(root){

var grids=qsa('[data-stagger]',root);

if(!grids.length) return;

grids.forEach(function(grid){

if(isReduce()){
 grid.classList.add('is-stagger-on');
 return;
}

var io=new IntersectionObserver(function(entries){

entries.forEach(function(ent){

 if(!ent.isIntersecting) return;

 grid.classList.add('is-stagger-on');
 io.unobserve(grid);

});

},{
 threshold:.18,
 rootMargin:'0px 0px -10% 0px'
});

io.observe(grid);

setTimeout(function(){
grid.classList.add('is-stagger-on');
},2200);

});

}

/* HOME SLIDER */
function getGapPx(track){
var cs=getComputedStyle(track);
return parseFloat(cs.gap||0)||0;
}

function initHomeSlider(wrap){

var track=qs('[data-hn-track]',wrap);
var prev=qs('[data-hn-prev]',wrap);
var next=qs('[data-hn-next]',wrap);

if(!track) return;

function step(){

var card=qs('.hn-card',track);

if(!card) return 0;

return Math.round(
card.getBoundingClientRect().width +
getGapPx(track)
);

}

function goNext(){
track.scrollBy({
 left:step(),
 behavior:'smooth'
});
}

function goPrev(){
track.scrollBy({
 left:-step(),
 behavior:'smooth'
});
}

if(prev){
prev.addEventListener('click',goPrev);
}

if(next){
next.addEventListener('click',goNext);
}

}

/* ROOT INIT */
function initNewsroomRoot(root){

markJS(root);

autoTagRevealNewsroom(root);
autoTagStaggerNewsroom(root);

initReveal(root);
initStagger(root);

}

function initHomeRoot(root){

markJS(root);

autoTagRevealHome(root);

initReveal(root);
initStagger(root);

initHomeSlider(root);

}

/* GLOBAL INIT */
function initAll(){

getNewsroomRoots().forEach(initNewsroomRoot);

getHomeNewsRoots().forEach(initHomeRoot);

}

/* OBSERVER late content */
function observeDynamic(){

if(!('MutationObserver' in window)) return;

var mo=new MutationObserver(function(){

setTimeout(initAll,140);

});

mo.observe(
document.body,
{
childList:true,
subtree:true
}
);

}

onReady(function(){

initAll();

observeDynamic();

setTimeout(initAll,350);
setTimeout(initAll,950);

});

window.addEventListener(
'load',
initAll,
{once:true}
);

})();