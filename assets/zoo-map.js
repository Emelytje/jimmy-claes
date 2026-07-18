(function(){
  'use strict';
  document.querySelectorAll('.pb-zoo-map[data-zoos]').forEach(function(el){
    var zoos;
    try{ zoos = JSON.parse(el.getAttribute('data-zoos')); }catch(e){ zoos = []; }

    var map = L.map(el);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
      maxZoom: 18
    }).addTo(map);

    if(!zoos.length){ map.setView([20, 0], 2); return; }

    var bounds = [];
    zoos.forEach(function(z){
      var marker = L.marker([z.lat, z.lng]).addTo(map);
      var popupEl;
      if(z.url){
        popupEl = document.createElement('a');
        popupEl.href = z.url; popupEl.target = '_blank'; popupEl.rel = 'noopener';
        popupEl.textContent = z.label;
      } else {
        popupEl = document.createElement('span');
        popupEl.textContent = z.label;
      }
      marker.bindPopup(popupEl);
      bounds.push([z.lat, z.lng]);
    });

    if(bounds.length === 1) map.setView(bounds[0], 6);
    else map.fitBounds(bounds, { padding: [30, 30] });
  });
})();
