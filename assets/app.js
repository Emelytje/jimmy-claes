(function(){
  'use strict';

  var toggle = document.querySelector('.nav-toggle');
  if(toggle){
    toggle.addEventListener('click', function(){
      var open = document.body.classList.toggle('nav-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.querySelectorAll('.top nav a').forEach(function(a){
      a.addEventListener('click', function(){ document.body.classList.remove('nav-open'); toggle.setAttribute('aria-expanded','false'); });
    });
  }

  var animated = document.querySelectorAll('[data-animate]');
  if(animated.length){
    if('IntersectionObserver' in window){
      var io = new IntersectionObserver(function(entries){
        entries.forEach(function(entry){
          if(entry.isIntersecting){
            entry.target.classList.add('pb-in-view');
            io.unobserve(entry.target);
          }
        });
      }, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });
      animated.forEach(function(el){ io.observe(el); });
    } else {
      animated.forEach(function(el){ el.classList.add('pb-in-view'); });
    }
  }

  var lightboxTargets = document.querySelectorAll('.pb-gallery-item img, .pb-figure img, .photo img');
  if(lightboxTargets.length){
    var box = document.createElement('div');
    box.className = 'pb-lightbox';
    box.innerHTML = '<button type="button" class="pb-lightbox-close" aria-label="Sluiten">&times;</button><img alt="">';
    document.body.appendChild(box);
    var boxImg = box.querySelector('img');

    function openBox(src, alt){
      boxImg.src = src; boxImg.alt = alt || '';
      box.classList.add('is-open');
    }
    function closeBox(){ box.classList.remove('is-open'); boxImg.src=''; }

    lightboxTargets.forEach(function(img){
      img.style.cursor = 'zoom-in';
      img.addEventListener('click', function(e){
        if(img.closest('a')) e.preventDefault();
        openBox(img.currentSrc || img.src, img.alt);
      });
    });
    box.addEventListener('click', function(e){ if(e.target === box) closeBox(); });
    box.querySelector('.pb-lightbox-close').addEventListener('click', closeBox);
    document.addEventListener('keydown', function(e){ if(e.key === 'Escape') closeBox(); });
  }
})();
