(function(){
  'use strict';

  var toggle = document.querySelector('.nav-toggle');
  if(toggle){
    toggle.addEventListener('click', function(){
      var open = document.body.classList.toggle('nav-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.querySelectorAll('.top nav a:not(.nav-dropdown-toggle)').forEach(function(a){
      a.addEventListener('click', function(){ document.body.classList.remove('nav-open'); toggle.setAttribute('aria-expanded','false'); });
    });
  }

  // On mobile the "Dieren" nav item taps open/closed instead of navigating
  // straight away, since there's no hover to reveal the submenu on touch.
  var dropdownToggle = document.querySelector('.nav-dropdown-toggle');
  if(dropdownToggle){
    dropdownToggle.addEventListener('click', function(e){
      if(window.matchMedia('(max-width:760px)').matches){
        e.preventDefault();
        dropdownToggle.closest('.nav-dropdown').classList.toggle('is-open');
      }
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

  // Group images per gallery container so the lightbox can page through
  // "the rest of this gallery" with prev/next, not just open one photo.
  var groups = [];
  document.querySelectorAll('.pb-gallery, .gallery').forEach(function(container){
    var imgs = Array.prototype.slice.call(container.querySelectorAll('img'));
    if(imgs.length) groups.push(imgs);
  });
  document.querySelectorAll('.pb-figure img').forEach(function(img){
    if(!img.closest('.pb-gallery') && !img.closest('.gallery')) groups.push([img]);
  });

  if(groups.length){
    var box = document.createElement('div');
    box.className = 'pb-lightbox';
    box.innerHTML = '<button type="button" class="pb-lightbox-close" aria-label="Sluiten">&times;</button>'
      + '<button type="button" class="pb-lightbox-nav pb-lightbox-prev" aria-label="Vorige">&#8249;</button>'
      + '<img alt="">'
      + '<button type="button" class="pb-lightbox-nav pb-lightbox-next" aria-label="Volgende">&#8250;</button>'
      + '<div class="pb-lightbox-counter"></div>';
    document.body.appendChild(box);
    var boxImg = box.querySelector('img');
    var counterEl = box.querySelector('.pb-lightbox-counter');
    var prevBtn = box.querySelector('.pb-lightbox-prev');
    var nextBtn = box.querySelector('.pb-lightbox-next');
    var currentGroup = null, currentIndex = 0;

    function show(){
      var img = currentGroup[currentIndex];
      boxImg.src = img.currentSrc || img.src;
      boxImg.alt = img.alt || '';
      var multi = currentGroup.length > 1;
      prevBtn.style.display = nextBtn.style.display = counterEl.style.display = multi ? '' : 'none';
      if(multi) counterEl.textContent = (currentIndex+1) + ' / ' + currentGroup.length;
    }
    function openBox(group, index){ currentGroup = group; currentIndex = index; show(); box.classList.add('is-open'); }
    function closeBox(){ box.classList.remove('is-open'); boxImg.src=''; }
    function prev(){ currentIndex = (currentIndex - 1 + currentGroup.length) % currentGroup.length; show(); }
    function next(){ currentIndex = (currentIndex + 1) % currentGroup.length; show(); }

    groups.forEach(function(group){
      group.forEach(function(img, idx){
        img.style.cursor = 'zoom-in';
        img.addEventListener('click', function(e){
          if(img.closest('a')) e.preventDefault();
          openBox(group, idx);
        });
      });
    });
    box.addEventListener('click', function(e){ if(e.target === box) closeBox(); });
    box.querySelector('.pb-lightbox-close').addEventListener('click', closeBox);
    prevBtn.addEventListener('click', function(e){ e.stopPropagation(); prev(); });
    nextBtn.addEventListener('click', function(e){ e.stopPropagation(); next(); });
    document.addEventListener('keydown', function(e){
      if(!box.classList.contains('is-open')) return;
      if(e.key === 'Escape') closeBox();
      else if(e.key === 'ArrowLeft') prev();
      else if(e.key === 'ArrowRight') next();
    });
  }
})();
