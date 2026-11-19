const menuToggle=document.getElementById('menuToggle'),nav=document.getElementById('mainNav');
menuToggle?.addEventListener('click',()=>nav.classList.toggle('open'));
nav?.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>nav.classList.remove('open')));
const waButton=document.getElementById('waButton'),waPop=document.getElementById('waPop');
waButton?.addEventListener('click',()=>waPop.classList.toggle('show'));
waPop?.querySelectorAll('[data-msg]').forEach(a=>a.addEventListener('click',e=>{e.preventDefault();const msg=encodeURIComponent(a.dataset.msg);window.open(`https://wa.me/${window.WA_NUMBER}?text=${msg}`,'_blank','noopener');}));
document.addEventListener('click',e=>{if(waPop&&!e.target.closest('.wa-widget'))waPop.classList.remove('show')});
const galleryLightbox=document.getElementById('galleryLightbox');
if(galleryLightbox){
 const galleryImage=document.getElementById('galleryLightboxImage'),galleryProject=document.getElementById('galleryLightboxProject'),galleryCaption=document.getElementById('galleryLightboxCaption'),galleryCount=document.getElementById('galleryLightboxCount'),galleryPrevious=document.getElementById('galleryPrevious'),galleryNext=document.getElementById('galleryNext'),galleryClose=galleryLightbox.querySelector('.gallery-lightbox-close');
 let galleryItems=[],galleryIndex=0,galleryOpener=null;
 function showGalleryImage(){const item=galleryItems[galleryIndex];if(!item)return;galleryImage.src=item.href;galleryImage.alt=item.dataset.caption||'';galleryProject.textContent=item.dataset.project||'';galleryCaption.textContent=item.dataset.caption||'';galleryCount.textContent=(galleryIndex+1)+' / '+galleryItems.length;galleryPrevious.disabled=galleryItems.length<2;galleryNext.disabled=galleryItems.length<2;}
 function closeGallery(){if(galleryLightbox.hidden)return;galleryLightbox.hidden=true;galleryLightbox.setAttribute('aria-hidden','true');document.body.classList.remove('gallery-open');if(galleryOpener&&galleryOpener.isConnected)galleryOpener.focus();galleryOpener=null;}
 function moveGallery(step){if(!galleryItems.length)return;galleryIndex=(galleryIndex+step+galleryItems.length)%galleryItems.length;showGalleryImage();}
 document.addEventListener('click',event=>{const trigger=event.target.closest('[data-gallery-item]');if(trigger){event.preventDefault();const grid=trigger.closest('.ongoing-photo-grid');if(!grid)return;galleryItems=Array.from(grid.querySelectorAll('[data-gallery-item]'));galleryIndex=galleryItems.indexOf(trigger);galleryOpener=trigger;showGalleryImage();galleryLightbox.hidden=false;galleryLightbox.setAttribute('aria-hidden','false');document.body.classList.add('gallery-open');galleryClose.focus();return;}if(event.target===galleryLightbox||event.target.closest('[data-gallery-close]'))closeGallery();});
 galleryPrevious.addEventListener('click',()=>moveGallery(-1));galleryNext.addEventListener('click',()=>moveGallery(1));
 document.addEventListener('keydown',event=>{if(galleryLightbox.hidden)return;if(event.key==='Escape'){event.preventDefault();closeGallery();}else if(event.key==='ArrowLeft'){event.preventDefault();moveGallery(-1);}else if(event.key==='ArrowRight'){event.preventDefault();moveGallery(1);}else if(event.key==='Tab'){const focusable=Array.from(galleryLightbox.querySelectorAll('button:not([disabled])'));const first=focusable[0],last=focusable[focusable.length-1];if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}}});
}
