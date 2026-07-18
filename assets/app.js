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

  // On mobile every dropdown level (Dieren, and any nested category with
  // children) taps open/closed instead of navigating straight away, since
  // there's no hover to reveal the submenu on touch. Delegated so it also
  // covers dropdowns nested arbitrarily deep in the category tree.
  document.addEventListener('click', function(e){
    var dropdownToggle = e.target.closest('.nav-dropdown-toggle');
    if(!dropdownToggle) return;
    if(window.matchMedia('(max-width:760px)').matches){
      e.preventDefault();
      dropdownToggle.closest('.nav-dropdown').classList.toggle('is-open');
    }
  });

  // Deep category trees can nest dropdowns several levels; each level flies
  // out to the right by default and can otherwise run off the edge of the
  // screen. On hover, flip a menu to open leftward instead if it would
  // overflow the viewport.
  document.querySelectorAll('.nav-dropdown').forEach(function(dd){
    var menu = dd.querySelector(':scope > .nav-dropdown-menu');
    if(!menu) return;
    dd.addEventListener('mouseenter', function(){
      menu.classList.remove('flyout-left');
      if(menu.getBoundingClientRect().right > window.innerWidth) menu.classList.add('flyout-left');
    });
  });

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
  // A pagebuilder gallery block's own wrapper section is class="pb-block
  // pb-gallery" — that also matches the ".pb-gallery" selector below, so
  // without dedup every image in such a block would get bound twice (once
  // via the outer section, once via the real ".pb-gallery" grid div inside
  // it), silently double-firing every click handler.
  var groups = [];
  var claimedImgs = [];
  document.querySelectorAll('.pb-gallery, .gallery').forEach(function(container){
    var imgs = Array.prototype.slice.call(container.querySelectorAll('img')).filter(function(img){
      return claimedImgs.indexOf(img) === -1;
    });
    if(imgs.length){ groups.push(imgs); claimedImgs = claimedImgs.concat(imgs); }
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

    // Simpele afschrikking tegen rechtstreeks downloaden (rechtsklik-opslaan
    // / slepen) — geen harde beveiliging (de bron-URL blijft altijd
    // technisch bereikbaar), maar voorkomt de gewone manieren om een foto
    // mee te nemen, ook wanneer er een Google Drive-link is ingesteld.
    function preventImageSaving(img){
      img.setAttribute('draggable', 'false');
      img.addEventListener('contextmenu', function(e){ e.preventDefault(); });
      img.addEventListener('dragstart', function(e){ e.preventDefault(); });
    }
    preventImageSaving(boxImg);

    // Enkele klik opent Google Drive rechtstreeks wanneer er een Drive-doel
    // is: de precieze foto als die uit een gekoppelde Drive-map komt
    // (data-drive-file), anders de hele gekoppelde map (data-drive-url op
    // <body>) — geen lightbox-zoom in dat geval. Zonder Drive-koppeling
    // blijft een gewone foto gewoon inzoomen zoals altijd.
    var driveUrl = document.body.getAttribute('data-drive-url') || '';
    groups.forEach(function(group){
      group.forEach(function(img, idx){
        preventImageSaving(img);
        var driveFileId = img.getAttribute('data-drive-file');
        var driveTarget = driveFileId ? ('https://drive.google.com/file/d/' + encodeURIComponent(driveFileId) + '/view') : driveUrl;
        img.style.cursor = driveTarget ? 'pointer' : 'zoom-in';
        img.addEventListener('click', function(e){
          if(img.closest('a')) e.preventDefault();
          if(driveTarget){ window.open(driveTarget, '_blank', 'noopener'); return; }
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

  document.querySelectorAll('.pb-slideshow').forEach(function(show){
    var slides = Array.prototype.slice.call(show.querySelectorAll('.pb-slideshow-slide'));
    var dots = Array.prototype.slice.call(show.querySelectorAll('.pb-slideshow-dot'));
    if(slides.length < 2) return;
    var interval = parseInt(show.getAttribute('data-interval'), 10) || 5000;
    var current = 0, timer;

    function show_(i){
      current = (i + slides.length) % slides.length;
      slides.forEach(function(s, idx){ s.classList.toggle('is-active', idx === current); });
      dots.forEach(function(d, idx){ d.classList.toggle('is-active', idx === current); });
    }
    function restart(){ clearInterval(timer); timer = setInterval(function(){ show_(current + 1); }, interval); }

    var prev = show.querySelector('.pb-slideshow-prev');
    var next = show.querySelector('.pb-slideshow-next');
    if(prev) prev.addEventListener('click', function(){ show_(current - 1); restart(); });
    if(next) next.addEventListener('click', function(){ show_(current + 1); restart(); });
    dots.forEach(function(d, idx){ d.addEventListener('click', function(){ show_(idx); restart(); }); });

    restart();
  });
})();
