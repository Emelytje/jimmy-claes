(function(){
'use strict';

/* ============================================================
   Helpers
   ============================================================ */
function esc(s){
  return String(s==null?'':s).replace(/[&<>"']/g, function(c){
    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];
  });
}
function uid(){ return 'b_' + Math.random().toString(16).slice(2,10); }
function clamp(n,min,max){ n=parseInt(n,10); if(isNaN(n)) n=min; return Math.max(min,Math.min(max,n)); }
function debounce(fn, wait){ var t; return function(){ var args=arguments, ctx=this; clearTimeout(t); t=setTimeout(function(){ fn.apply(ctx,args); }, wait); }; }
function imgSrc(path){
  if(!path) return '';
  if(/^https?:\/\//i.test(path)) return path;
  return '../' + String(path).replace(/^\/+/, '');
}
function get(obj, path){
  return path.split('.').reduce(function(o,k){ return (o==null?undefined:o[k]); }, obj);
}
function set(obj, path, val){
  var parts = path.split('.');
  var last = parts.pop();
  var target = parts.reduce(function(o,k){ return o[k]; }, obj);
  target[last] = val;
}

var GOOGLE_FONTS = [
  'Fraunces','Karla','Playfair Display','Cormorant Garamond','Marcellus','Libre Baskerville',
  'DM Serif Display','Lora','Merriweather','EB Garamond','Bitter','Crimson Pro',
  'Inter','Poppins','Work Sans','Nunito Sans','Mulish','Rubik','Jost','Manrope',
  'Space Grotesk','Sora','Barlow','Outfit','Quicksand','Josefin Sans'
];

var loadedFonts = {};
function ensureGoogleFont(family){
  if(!family || loadedFonts[family]) return;
  loadedFonts[family] = true;
  var link = document.createElement('link');
  link.rel = 'stylesheet';
  link.href = 'https://fonts.googleapis.com/css2?family=' + encodeURIComponent(family).replace(/%20/g,'+') + ':ital,wght@0,400;0,600;0,700;1,400&display=swap';
  document.head.appendChild(link);
}

var SHADOWS = { none:'', sm:'0 1px 3px rgba(30,20,10,.08)', md:'0 8px 24px rgba(30,20,10,.10)', lg:'0 20px 48px rgba(30,20,10,.16)' };

function styleAttr(settings, extra){
  var s = settings || {}; var decl = [];
  if(s.fontFamily){ ensureGoogleFont(s.fontFamily); decl.push("font-family:'"+s.fontFamily.replace(/'/g,'')+"',var(--font-body)"); }
  if(s.fontSize) decl.push('font-size:'+parseInt(s.fontSize,10)+'px');
  if(s.textColor){ decl.push('--pb-text-color:'+s.textColor); decl.push('color:'+s.textColor); }
  if(s.bgColor) decl.push('background-color:'+s.bgColor);
  if(s.paddingY!=='' && s.paddingY!=null) decl.push('padding-top:'+parseInt(s.paddingY,10)+'px;padding-bottom:'+parseInt(s.paddingY,10)+'px');
  if(s.paddingX!=='' && s.paddingX!=null) decl.push('padding-left:'+parseInt(s.paddingX,10)+'px;padding-right:'+parseInt(s.paddingX,10)+'px');
  if(s.radius!=='' && s.radius!=null) decl.push('border-radius:'+parseInt(s.radius,10)+'px');
  if(s.shadow && SHADOWS[s.shadow]) decl.push('box-shadow:'+SHADOWS[s.shadow]);
  if(s.align) decl.push('text-align:'+s.align);
  if(extra){ Object.keys(extra).forEach(function(k){ decl.push(k+':'+extra[k]); }); }
  return decl.length ? ' style="'+esc(decl.join(';'))+'"' : '';
}
function animAttr(settings){
  var a = (settings||{}).animation;
  if(!a || a==='none') return '';
  return ' data-animate="'+esc(a)+'"';
}

/* ============================================================
   Block definitions: defaults + renderers (mirrors blocks.php)
   ============================================================ */
var DEFAULT_SETTINGS = { fontFamily:'', fontSize:'', textColor:'', bgColor:'', align:'left', paddingY:56, paddingX:24, radius:0, shadow:'none', animation:'fade-up' };

var BLOCKS = {
  hero: {
    label:'Hero', icon:'&#9733;', group:'Layout',
    settings:function(){ return Object.assign({}, DEFAULT_SETTINGS, {align:'center', paddingY:'', paddingX:'', animation:'fade-in'}); },
    data:function(){ return {title:'Titel van je pagina', subtitle:'Een korte, krachtige ondertitel die de bezoeker verwelkomt.', buttonText:'', buttonHref:'#', bgImage:'', overlay:45}; },
    render:function(d, s, id){
      var bg = d.bgImage ? imgSrc(d.bgImage) : '';
      var extra = bg ? {'background-image':"url('"+bg.replace(/'/g,'')+"')"} : null;
      var html = '<section class="pb-block pb-hero'+(bg?' pb-hero-has-bg':'')+'" data-block-id="'+id+'" data-block-type="hero"'+styleAttr(s, extra)+animAttr(s)+'>';
      if(bg) html += '<div class="pb-hero-overlay" style="opacity:'+((d.overlay!=null?d.overlay:45)/100)+'"></div>';
      html += '<div class="pb-inner pb-hero-inner">';
      html += '<h1 data-edit-field="title">'+esc(d.title)+'</h1>';
      html += '<p class="pb-hero-subtitle" data-edit-field="subtitle">'+esc(d.subtitle)+'</p>';
      html += '<a class="pb-btn pb-btn-solid pb-btn-lg" href="'+esc(d.buttonHref||'#')+'" data-edit-field="buttonText" onclick="return false">'+esc(d.buttonText||'Knoptekst')+'</a>';
      html += '</div></section>';
      return html;
    }
  },
  title: {
    label:'Titel', icon:'&#84;', group:'Inhoud',
    settings:function(){ return Object.assign({}, DEFAULT_SETTINGS); },
    data:function(){ return {text:'Nieuwe titel', level:'h2'}; },
    render:function(d, s, id){
      var lvl = ['h1','h2','h3'].indexOf(d.level)>=0 ? d.level : 'h2';
      return wrap('title', id, s, '<'+lvl+' data-edit-field="text">'+esc(d.text)+'</'+lvl+'>');
    }
  },
  text: {
    label:'Tekst', icon:'&#182;', group:'Inhoud',
    settings:function(){ return Object.assign({}, DEFAULT_SETTINGS); },
    data:function(){ return {html:'Dubbelklik om deze tekst te bewerken.'}; },
    render:function(d, s, id){
      return wrap('text', id, s, '<div class="pb-text" data-edit-field="html" data-edit-html="1">'+(d.html||'')+'</div>');
    }
  },
  image: {
    label:'Foto', icon:'&#128247;', group:'Media',
    settings:function(){ return Object.assign({}, DEFAULT_SETTINGS); },
    data:function(){ return {src:'', alt:'', caption:'', width:'full', link:'', aspectRatio:null, widthPct:''}; },
    render:function(d, s, id){
      if(!d.src) return wrap('image', id, s, '<div class="pbe-empty-col" style="min-height:140px">Geen foto gekozen — kies er een rechts &#8594;</div>');
      var cls = d.width==='full' ? 'pb-img-full' : 'pb-img-contained';
      var arStyle = d.aspectRatio ? ' style="aspect-ratio:'+d.aspectRatio+';object-fit:cover;height:auto"' : '';
      var img = '<img src="'+esc(imgSrc(d.src))+'" alt="'+esc(d.alt)+'" class="'+cls+'"'+arStyle+'>';
      var cap = '<figcaption data-edit-field="caption">'+esc(d.caption||'')+'</figcaption>';
      var figStyle = (d.widthPct!=null && d.widthPct!=='') ? ' style="max-width:'+d.widthPct+'%;margin-left:auto;margin-right:auto"' : '';
      return wrap('image', id, s, '<figure class="pb-figure"'+figStyle+'>'+img+cap+'</figure>');
    }
  },
  gallery: {
    label:'Fotogalerij', icon:'&#128444;', group:'Media',
    settings:function(){ return Object.assign({}, DEFAULT_SETTINGS); },
    data:function(){ return {images:[], columns:3, layout:'grid'}; },
    render:function(d, s, id){
      var cols = clamp(d.columns||3,2,4);
      var layout = d.layout==='masonry' ? 'masonry' : 'grid';
      if(!d.images || !d.images.length) return wrap('gallery', id, s, '<div class="pbe-empty-col" style="min-height:140px">Nog geen foto\'s — voeg ze toe rechts &#8594;</div>');
      var html = '<div class="pb-gallery pb-gallery-'+layout+'" style="--pb-cols:'+cols+'">';
      d.images.forEach(function(img){
        var cls = 'pb-gallery-item'+(img.size==='large' ? ' pb-gallery-item-lg' : '');
        html += '<figure class="'+cls+'"><img src="'+esc(imgSrc(img.src))+'" alt="'+esc(img.alt||'')+'">'
          + (img.caption ? '<figcaption>'+esc(img.caption)+'</figcaption>' : '') + '</figure>';
      });
      html += '</div>';
      return wrap('gallery', id, s, html);
    }
  },
  video: {
    label:'Video', icon:'&#9654;', group:'Media',
    settings:function(){ return Object.assign({}, DEFAULT_SETTINGS); },
    data:function(){ return {url:'', poster:''}; },
    render:function(d, s, id){
      if(!d.url) return wrap('video', id, s, '<div class="pbe-empty-col" style="min-height:140px">Plak een YouTube/Vimeo-link rechts &#8594;</div>');
      var embed = videoEmbed(d.url);
      var inner;
      if(embed) inner = '<div class="pb-video-wrap"><iframe src="'+esc(embed)+'"></iframe></div>';
      else if(/\.(mp4|webm|ogg)(\?.*)?$/i.test(d.url)) inner = '<div class="pb-video-wrap"><video controls src="'+esc(d.url)+'"></video></div>';
      else inner = '<div class="pbe-empty-col">Onherkenbare video-link</div>';
      return wrap('video', id, s, inner);
    }
  },
  button: {
    label:'Knop', icon:'&#128433;', group:'Inhoud',
    settings:function(){ return Object.assign({}, DEFAULT_SETTINGS); },
    data:function(){ return {text:'Klik hier', href:'#', style:'solid', size:'md'}; },
    render:function(d, s, id){
      var html = '<a class="pb-btn pb-btn-'+esc(d.style||'solid')+' pb-btn-'+esc(d.size||'md')+'" href="'+esc(d.href||'#')+'" data-edit-field="text" onclick="return false">'+esc(d.text||'Knoptekst')+'</a>';
      return wrap('button', id, s, html);
    }
  },
  divider: {
    label:'Scheidingslijn', icon:'&#8213;', group:'Layout',
    settings:function(){ return Object.assign({}, DEFAULT_SETTINGS, {paddingY:20, animation:'none'}); },
    data:function(){ return {style:'line'}; },
    render:function(d, s, id){ return wrap('divider', id, s, '<div class="pb-divider pb-divider-'+esc(d.style||'line')+'"></div>'); }
  },
  quote: {
    label:'Citaat', icon:'&#8220;', group:'Inhoud',
    settings:function(){ return Object.assign({}, DEFAULT_SETTINGS, {align:'center', paddingY:64}); },
    data:function(){ return {text:'Een mooie quote over dieren en fotografie.', author:'Naam'}; },
    render:function(d, s, id){
      var html = '<blockquote class="pb-quote"><p data-edit-field="text">'+esc(d.text)+'</p><cite data-edit-field="author">'+esc(d.author||'')+'</cite></blockquote>';
      return wrap('quote', id, s, html);
    }
  },
  columns: {
    label:'Kolommen', icon:'&#9638;', group:'Layout',
    settings:function(){ return Object.assign({}, DEFAULT_SETTINGS); },
    data:function(){ return {count:2, gap:32, cols:[{blocks:[]},{blocks:[]}]}; },
    render:function(d, s, id, depth){
      var count = clamp(d.cols ? d.cols.length : (d.count||2), 1, 4);
      var html = '<div class="pb-columns-grid pb-columns-'+count+'" style="--pb-gap:'+(d.gap!=null?d.gap:32)+'px">';
      (d.cols||[]).forEach(function(col, i){
        html += '<div class="pb-column pbe-sortable-zone" data-col-owner="'+id+'" data-col-index="'+i+'">';
        html += renderList(col.blocks||[], depth+1);
        if(!col.blocks || !col.blocks.length) html += '<div class="pbe-empty-col">Sleep hier een blok</div>';
        html += '</div>';
      });
      html += '</div>';
      return wrap('columns', id, s, html);
    }
  },
  row: {
    label:'Rij', icon:'&#8596;', group:'Layout', hidden:true,
    settings:function(){ return Object.assign({}, DEFAULT_SETTINGS, {paddingY:0, paddingX:0, animation:'none'}); },
    data:function(){ return {cells:[{widthPct:50,blocks:[]},{widthPct:50,blocks:[]}], mobileStack:true, gap:24}; },
    render:function(d, s, id, depth){
      var cells = d.cells || [];
      var html = '<div class="pb-row-flex" data-row-id="'+id+'" data-stack="'+((!('mobileStack' in d)||d.mobileStack)?1:0)+'" style="--pb-row-gap:'+(d.gap!=null?d.gap:24)+'px">';
      html += '<button type="button" class="pbe-row-tab" data-action="select-row" data-row-select="'+id+'">Rij</button>';
      cells.forEach(function(cell, i){
        var w = cell.widthPct!=null ? cell.widthPct : (100/cells.length);
        html += '<div class="pb-cell pbe-sortable-zone" data-cell-owner="'+id+'" data-cell-index="'+i+'" style="width:'+w+'%">';
        html += renderList(cell.blocks||[], depth+1);
        if(!cell.blocks || !cell.blocks.length) html += '<div class="pbe-empty-col">Sleep hier een blok</div>';
        html += '</div>';
        if(i < cells.length-1) html += '<div class="pbe-col-resizer" data-row-id="'+id+'" data-left-index="'+i+'" data-right-index="'+(i+1)+'"><span class="pbe-col-resizer-handle"></span></div>';
      });
      html += '</div>';
      return wrap('row', id, s, html);
    }
  },
  recent: {
    label:'Recent toegevoegd', icon:'&#128337;', group:'Layout',
    settings:function(){ return Object.assign({}, DEFAULT_SETTINGS); },
    data:function(){ return {source:'mixed', count:3}; },
    render:function(d, s, id){
      var html = '<div class="pbe-empty-col" style="min-height:110px">Live voorbeeld verschijnt op de site zelf — toont automatisch de nieuwste gepubliceerde content.</div>';
      return wrap('recent', id, s, html);
    }
  },
  contact: {
    label:'Contactformulier', icon:'&#9993;', group:'Formulier',
    settings:function(){ return Object.assign({}, DEFAULT_SETTINGS); },
    data:function(){ return {title:'Neem contact op', buttonText:'Versturen'}; },
    render:function(d, s, id){
      var html = (d.title ? '<h3 data-edit-field="title">'+esc(d.title)+'</h3>' : '')
        + '<div class="pb-contact-form" style="pointer-events:none;opacity:.85">'
        + '<label>Naam<input disabled></label><label>E-mail (optioneel)<input disabled></label>'
        + '<label>Bericht<textarea disabled rows="4"></textarea></label>'
        + '<button type="button" disabled>'+esc(d.buttonText||'Versturen')+'</button></div>';
      return wrap('contact', id, s, html);
    }
  },
  html: {
    label:'Eigen HTML', icon:'&#60;/&#62;', group:'Geavanceerd',
    settings:function(){ return Object.assign({}, DEFAULT_SETTINGS); },
    data:function(){ return {code:'<!-- Eigen HTML -->'}; },
    render:function(d, s, id){
      return wrap('html', id, s, '<div style="font-family:monospace;font-size:.8rem;color:#8a7c6c;border:1px dashed #cabfa9;padding:14px;border-radius:8px">HTML-blok (' + (d.code||'').length + ' tekens) — bewerk rechts &#8594;</div>');
    }
  }
};
var GROUP_ORDER = ['Layout','Inhoud','Media','Formulier','Geavanceerd'];

function wrap(type, id, settings, inner){
  return '<section class="pb-block pb-'+type+'" data-block-id="'+id+'" data-block-type="'+type+'"'+styleAttr(settings)+animAttr(settings)+'>'
       + '<div class="pbe-block-toolbar">'
       + '<button type="button" class="pbe-drag-handle" data-action="drag" title="Verslepen">&#9776;</button>'
       + '<button type="button" data-action="duplicate" title="Dupliceren">&#10064;</button>'
       + '<button type="button" data-action="delete" title="Verwijderen">&#128465;</button>'
       + '</div><div class="pb-inner">'+inner+'</div></section>';
}
function renderList(blocks, depth){
  return (blocks||[]).map(function(b){ return renderBlockHtml(b, depth||0); }).join('');
}
function renderBlockHtml(block, depth){
  var def = BLOCKS[block.type];
  if(!def) return '';
  return def.render(block.data||{}, block.settings||{}, block.id, depth||0);
}
function videoEmbed(url){
  var m = url.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([A-Za-z0-9_\-]{6,})/);
  if(m) return 'https://www.youtube-nocookie.com/embed/'+m[1];
  m = url.match(/vimeo\.com\/(\d+)/);
  if(m) return 'https://player.vimeo.com/video/'+m[1];
  return null;
}
function createBlock(type){
  var def = BLOCKS[type];
  return { id: uid(), type: type, settings: def.settings(), data: def.data() };
}

/* ============================================================
   State
   ============================================================ */
var PAGE = window.PBE_INITIAL;
var state = {
  id: PAGE.id, title: PAGE.title, slug: PAGE.slug,
  meta_title: PAGE.meta_title||'', meta_description: PAGE.meta_description||'',
  published: !!PAGE.published, show_in_nav: !!PAGE.show_in_nav,
  blocks: PAGE.blocks && PAGE.blocks.length ? PAGE.blocks : []
};
var selectedId = null;
var blocksById = {};
var dirty = false;

function childArraysOf(block){
  if(block.type==='columns') return (block.data.cols||[]).map(function(c){ return c.blocks; });
  if(block.type==='row') return (block.data.cells||[]).map(function(c){ return c.blocks; });
  return [];
}
function rebuildIndex(){
  blocksById = {};
  (function walk(list){
    (list||[]).forEach(function(b){
      blocksById[b.id] = b;
      childArraysOf(b).forEach(walk);
    });
  })(state.blocks);
}
function findAndRemove(id){
  var found = null;
  function walk(list){
    for(var i=0;i<list.length;i++){
      if(list[i].id===id){ found = list.splice(i,1)[0]; return true; }
      var children = childArraysOf(list[i]);
      for(var c=0;c<children.length;c++){ if(walk(children[c])) return true; }
    }
    return false;
  }
  walk(state.blocks);
  return found;
}
function arrayForZone(zoneEl){
  if(zoneEl.id==='pbeCanvas') return state.blocks;
  if(zoneEl.hasAttribute('data-cell-owner')){
    var rowOwner = blocksById[zoneEl.getAttribute('data-cell-owner')];
    return rowOwner.data.cells[parseInt(zoneEl.getAttribute('data-cell-index'),10)].blocks;
  }
  var ownerId = zoneEl.getAttribute('data-col-owner');
  var idx = parseInt(zoneEl.getAttribute('data-col-index'),10);
  var owner = blocksById[ownerId];
  return owner.data.cols[idx].blocks;
}

/* ============================================================
   DOM refs
   ============================================================ */
var canvasEl = document.getElementById('pbeCanvas');
var canvasScaleEl = document.getElementById('pbeCanvasScale');
var settingsEl = document.getElementById('pbeSettings');
var saveStateEl = document.getElementById('pbeSaveState');
var titleInput = document.getElementById('pbeTitleInput');

/* ============================================================
   Rendering
   ============================================================ */
function renderCanvas(){
  rebuildIndex();
  if(!state.blocks.length){
    canvasEl.innerHTML = '<div class="pbe-empty-canvas">Sleep hier een blok naartoe vanuit het paneel links om te beginnen.</div>';
  } else {
    canvasEl.innerHTML = renderList(state.blocks, 0);
  }
  applySelectionVisual();
  attachResizeHandles();
  initSortables();
  markDirty();
}
function updateBlockDom(id){
  var el = canvasEl.querySelector('[data-block-id="'+id+'"]');
  var block = blocksById[id];
  if(!el || !block) return renderCanvas();
  var html = renderBlockHtml(block, 0);
  var tmp = document.createElement('div');
  tmp.innerHTML = html;
  var newEl = tmp.firstElementChild;
  el.replaceWith(newEl);
  if(selectedId===id) newEl.classList.add('is-selected');
  if(block.type==='columns' || block.type==='row') initSortables();
  attachResizeHandles();
  markDirty();
}
function applySelectionVisual(){
  canvasEl.querySelectorAll('.pb-block.is-selected').forEach(function(el){ el.classList.remove('is-selected'); });
  if(selectedId){
    var el = canvasEl.querySelector('[data-block-id="'+selectedId+'"]');
    if(el) el.classList.add('is-selected');
  }
}
function selectBlock(id){
  selectedId = id;
  applySelectionVisual();
  attachResizeHandles();
  renderSettingsPanel();
}
function deselect(){
  selectedId = null;
  applySelectionVisual();
  attachResizeHandles();
  renderSettingsPanel();
}

function findParentRow(blockId){
  for(var i=0;i<state.blocks.length;i++){
    var b = state.blocks[i];
    if(b.type==='row'){
      for(var c=0;c<b.data.cells.length;c++){
        var blocks = b.data.cells[c].blocks||[];
        for(var k=0;k<blocks.length;k++){ if(blocks[k].id===blockId) return {row:b, cellIndex:c}; }
      }
    }
  }
  return null;
}

/* ============================================================
   Sortable wiring
   ============================================================ */
var sortableInstances = [];
function initSortables(){
  sortableInstances.forEach(function(s){ s.destroy(); });
  sortableInstances = [];

  var palette = document.getElementById('pbePalette');
  sortableInstances.push(new Sortable(palette, {
    group: { name:'pb-blocks', pull:'clone', put:false },
    sort:false, animation:150, forceFallback:true,
    ghostClass:'pbe-sortable-ghost', dragClass:'pbe-sortable-drag',
    onStart: function(evt){ startEdgeTracking(evt); },
    onEnd: function(){ stopEdgeTracking(); }
  }));

  document.querySelectorAll('.pbe-sortable-zone').forEach(function(zone){
    sortableInstances.push(new Sortable(zone, {
      group: { name:'pb-blocks', pull:true, put:true },
      animation:150, handle:'.pbe-drag-handle', forceFallback:true,
      ghostClass:'pbe-sortable-ghost', dragClass:'pbe-sortable-drag',
      onAdd: handleSortEnd, onUpdate: handleSortEnd, onRemove: handleSortEnd,
      onStart: function(evt){ startEdgeTracking(evt); },
      onEnd: function(){ stopEdgeTracking(); }
    }));
  });
}

/* ---- edge-drag-to-row detection (top-level canvas only) ----
   Sortable's own onMove callback fires too irregularly (heavily
   throttled/coalesced) to reliably drive this, so a plain document-level
   mousemove listener does the hover/edge-zone math independently; the
   final drop still goes through Sortable's onAdd/onUpdate/onRemove. */
var pendingEdgeDrop = null;
var edgeIndicatorEl = null;
var dragActive = false;
var draggedBlockId = null;
function startEdgeTracking(evt){
  dragActive = true;
  draggedBlockId = evt.item.getAttribute('data-block-id') || null;
  pendingEdgeDrop = null;
  clearEdgeIndicator();
}
function stopEdgeTracking(){
  dragActive = false;
  draggedBlockId = null;
  clearEdgeIndicator();
}
function computeEdgeDrop(clientX, clientY){
  // elementFromPoint() alone would hit Sortable's fixed-position drag
  // ghost (it tracks the cursor exactly); walk the full z-order stack and
  // skip past it to find the real block underneath.
  var stack = document.elementsFromPoint(clientX, clientY);
  var related = null;
  for(var i=0;i<stack.length;i++){
    var candidate = stack[i].closest && stack[i].closest('.pb-block');
    if(candidate && canvasEl.contains(candidate)){ related = candidate; break; }
  }
  if(!related){ pendingEdgeDrop = null; clearEdgeIndicator(); return; }
  var targetId = related.getAttribute('data-block-id');
  if(!targetId || targetId === draggedBlockId){ pendingEdgeDrop = null; clearEdgeIndicator(); return; }
  var parentRow = findParentRow(targetId);
  var isTop = !parentRow && state.blocks.some(function(b){ return b.id===targetId; });
  if(!parentRow && !isTop){ pendingEdgeDrop = null; clearEdgeIndicator(); return; }

  var rect = related.getBoundingClientRect();
  var zone = Math.min(rect.width * 0.18, 140);
  var side = null;
  if(clientX - rect.left < zone) side = 'left';
  else if(rect.right - clientX < zone) side = 'right';
  if(!side){ pendingEdgeDrop = null; clearEdgeIndicator(); return; }

  pendingEdgeDrop = { targetId: targetId, side: side, parentRow: parentRow ? parentRow.row.id : null, cellIndex: parentRow ? parentRow.cellIndex : null };
  showEdgeIndicator(related, side);
}
document.addEventListener('mousemove', function(e){
  if(!dragActive) return;
  computeEdgeDrop(e.clientX, e.clientY);
});
// Sortable resolves its own drop position from the mouseup coordinates
// directly, which may land somewhere no mousemove was ever delivered for
// (mousemove is heavily coalesced) - recompute one last time here so
// pendingEdgeDrop reflects the true final cursor position.
document.addEventListener('mouseup', function(e){
  if(!dragActive) return;
  computeEdgeDrop(e.clientX, e.clientY);
}, true);
function showEdgeIndicator(el, side){
  if(!edgeIndicatorEl){
    edgeIndicatorEl = document.createElement('div');
    edgeIndicatorEl.className = 'pbe-edge-indicator';
    document.body.appendChild(edgeIndicatorEl);
  }
  var rect = el.getBoundingClientRect();
  edgeIndicatorEl.style.top = rect.top+'px';
  edgeIndicatorEl.style.height = rect.height+'px';
  edgeIndicatorEl.style.left = (side==='left' ? rect.left-2 : rect.right-2)+'px';
  edgeIndicatorEl.style.display = 'block';
}
function clearEdgeIndicator(){
  if(edgeIndicatorEl) edgeIndicatorEl.style.display = 'none';
}

var sortHandled = false;
function handleSortEnd(evt){
  if(sortHandled) return; sortHandled = true;
  setTimeout(function(){ sortHandled = false; }, 0);

  var edge = pendingEdgeDrop;
  pendingEdgeDrop = null;
  clearEdgeIndicator();
  if(edge){
    handleEdgeDrop(evt, edge);
    return;
  }

  var newType = evt.item.getAttribute('data-new-type');
  var targetArr = arrayForZone(evt.to);

  if(newType){
    evt.item.remove();
    if(evt.to !== canvasEl && (newType==='columns' || newType==='row')){
      alert('Kolommen kunnen niet in kolommen genest worden.');
      renderCanvas();
      return;
    }
    var block = createBlock(newType);
    targetArr.splice(evt.newIndex, 0, block);
    renderCanvas();
    selectBlock(block.id);
    return;
  }

  var id = evt.item.getAttribute('data-block-id');
  if(evt.from === evt.to){
    // pure reorder: rebuild that array from current DOM order
    var ids = Array.prototype.map.call(evt.to.children, function(c){ return c.getAttribute('data-block-id'); }).filter(Boolean);
    var objs = ids.map(function(i){ return blocksById[i]; });
    targetArr.length = 0;
    Array.prototype.push.apply(targetArr, objs);
    markDirty();
  } else {
    var movingBlock = blocksById[id];
    if(movingBlock && (movingBlock.type==='columns' || movingBlock.type==='row') && evt.to !== canvasEl){
      alert('Kolommen/rijen kunnen niet in kolommen genest worden.');
      renderCanvas();
      return;
    }
    var obj = findAndRemove(id);
    if(obj){
      targetArr.splice(evt.newIndex, 0, obj);
      renderCanvas();
      selectBlock(obj.id);
    }
  }
}

function handleEdgeDrop(evt, pending){
  var newType = evt.item.getAttribute('data-new-type');
  var draggedBlock;
  if(newType){
    evt.item.remove();
    if(newType==='row' || newType==='columns'){
      alert('Kan geen rij/kolommen-blok naast een ander blok slepen.');
      renderCanvas();
      return;
    }
    draggedBlock = createBlock(newType);
  } else {
    var id = evt.item.getAttribute('data-block-id');
    if(id === pending.targetId){ renderCanvas(); return; }
    draggedBlock = findAndRemove(id);
    if(!draggedBlock){ renderCanvas(); return; }
    if(draggedBlock.type==='row' || draggedBlock.type==='columns'){
      state.blocks.push(draggedBlock);
      alert('Rijen/kolommen-blokken kunnen niet naast een ander blok geplaatst worden.');
      renderCanvas();
      return;
    }
  }

  if(pending.parentRow){
    var row = blocksById[pending.parentRow];
    if(!row){ state.blocks.push(draggedBlock); renderCanvas(); return; }
    var insertIdx = pending.side==='left' ? pending.cellIndex : pending.cellIndex+1;
    row.data.cells.splice(insertIdx, 0, {widthPct:null, blocks:[draggedBlock]});
    redistributeCellWidths(row.data.cells);
  } else {
    var target = blocksById[pending.targetId];
    var idx = target ? state.blocks.indexOf(target) : -1;
    if(idx === -1){ state.blocks.push(draggedBlock); renderCanvas(); return; }
    var newCells = pending.side==='left'
      ? [{widthPct:50, blocks:[draggedBlock]}, {widthPct:50, blocks:[target]}]
      : [{widthPct:50, blocks:[target]}, {widthPct:50, blocks:[draggedBlock]}];
    var newRow = { id: uid(), type:'row', settings: BLOCKS.row.settings(), data: { cells:newCells, mobileStack:true, gap:24 } };
    state.blocks.splice(idx, 1, newRow);
  }
  renderCanvas();
  selectBlock(draggedBlock.id);
}
function redistributeCellWidths(cells){
  var n = cells.length;
  cells.forEach(function(c){ c.widthPct = round2(100/n); });
}
function clampNum(n,min,max){ return Math.max(min,Math.min(max,n)); }
function round2(n){ return Math.round(n*100)/100; }

/* ============================================================
   Canvas interactions (select, toolbar actions, inline edit)
   ============================================================ */
canvasEl.addEventListener('click', function(e){
  var actionBtn = e.target.closest('[data-action]');
  if(actionBtn){
    e.preventDefault(); e.stopPropagation();
    var blockEl = actionBtn.closest('.pb-block');
    var id = blockEl.getAttribute('data-block-id');
    var action = actionBtn.getAttribute('data-action');
    if(action==='delete'){
      if(confirm('Dit blok verwijderen?')){ findAndRemove(id); if(selectedId===id) selectedId=null; renderCanvas(); renderSettingsPanel(); }
    } else if(action==='duplicate'){
      duplicateBlock(id);
    } else if(action==='select-row'){
      selectBlock(id);
    }
    return;
  }
  var blockEl = e.target.closest('.pb-block');
  if(blockEl){
    selectBlock(blockEl.getAttribute('data-block-id'));
  } else {
    deselect();
  }
});
function duplicateBlock(id){
  var block = blocksById[id];
  if(!block) return;
  var copy = JSON.parse(JSON.stringify(block));
  (function reid(b){ b.id = uid(); childArraysOf(b).forEach(function(arr){ arr.forEach(reid); }); })(copy);
  // find owning array + index of original, insert copy after it
  function insertAfter(list){
    for(var i=0;i<list.length;i++){
      if(list[i]===block){ list.splice(i+1,0,copy); return true; }
      var children = childArraysOf(list[i]);
      for(var c=0;c<children.length;c++){ if(insertAfter(children[c])) return true; }
    }
    return false;
  }
  insertAfter(state.blocks);
  renderCanvas();
  selectBlock(copy.id);
}

// inline contenteditable sync
canvasEl.addEventListener('focusin', function(e){
  var field = e.target.closest('[data-edit-field]');
  if(!field) return;
  var blockEl = field.closest('.pb-block');
  if(blockEl && blockEl.getAttribute('data-block-id')===selectedId){
    field.setAttribute('contenteditable','true');
  }
});
canvasEl.addEventListener('focusout', function(e){
  var field = e.target.closest('[data-edit-field]');
  if(!field) return;
  syncEditableField(field);
  field.removeAttribute('contenteditable');
});
canvasEl.addEventListener('input', function(e){
  var field = e.target.closest('[data-edit-field]');
  if(field) syncEditableField(field);
});
function syncEditableField(field){
  var blockEl = field.closest('.pb-block');
  if(!blockEl) return;
  var block = blocksById[blockEl.getAttribute('data-block-id')];
  if(!block) return;
  var key = field.getAttribute('data-edit-field');
  var isHtml = field.hasAttribute('data-edit-html');
  block.data[key] = isHtml ? field.innerHTML : field.textContent;
  markDirty();
}
canvasEl.addEventListener('dblclick', function(e){
  var field = e.target.closest('[data-edit-field]');
  if(!field) return;
  var blockEl = field.closest('.pb-block');
  if(blockEl && blockEl.getAttribute('data-block-id')!==selectedId) selectBlock(blockEl.getAttribute('data-block-id'));
  field.setAttribute('contenteditable','true');
  field.focus();
});

/* ============================================================
   Resize handles (row cell width + image height/aspect ratio)
   ============================================================ */
function attachResizeHandles(){
  canvasEl.querySelectorAll('.pbe-resize-handle').forEach(function(h){ h.remove(); });
  if(!selectedId) return;
  var el = canvasEl.querySelector('[data-block-id="'+selectedId+'"]');
  var block = blocksById[selectedId];
  if(!el || !block) return;
  var cell = el.closest('.pb-cell');
  var inRow = !!cell;
  var isImage = block.type === 'image' && !!block.data.src;

  var dirs = [];
  if(inRow || isImage) dirs.push('w','e');
  if(isImage) dirs.push('n','s');
  if(isImage) dirs.push('nw','ne','sw','se');
  if(!dirs.length) return;

  dirs.forEach(function(dir){
    var handle = document.createElement('div');
    handle.className = 'pbe-resize-handle pbe-resize-'+dir;
    handle.setAttribute('data-dir', dir);
    el.appendChild(handle);
  });
}

canvasEl.addEventListener('mousedown', function(e){
  var divider = e.target.closest('.pbe-col-resizer');
  if(divider){ startDividerResize(e, divider); return; }
  var handle = e.target.closest('.pbe-resize-handle');
  if(handle){ startBlockResize(e, handle); return; }
});

function showDimensionBadge(){
  var badge = document.createElement('div');
  badge.className = 'pbe-dim-badge';
  document.body.appendChild(badge);
  return badge;
}
function updateDimensionBadge(badge, x, y, text){
  badge.textContent = text;
  badge.style.left = (x+18)+'px';
  badge.style.top = (y+18)+'px';
}
function removeDimensionBadge(badge){ badge.remove(); }

function startDividerResize(e, divider){
  e.preventDefault();
  var rowId = divider.getAttribute('data-row-id');
  var leftIdx = parseInt(divider.getAttribute('data-left-index'),10);
  var rightIdx = parseInt(divider.getAttribute('data-right-index'),10);
  var rowBlock = blocksById[rowId];
  var rowEl = canvasEl.querySelector('.pb-row-flex[data-row-id="'+rowId+'"]');
  if(!rowBlock || !rowEl) return;
  var rowRect = rowEl.getBoundingClientRect();
  var startX = e.clientX;
  var cells = rowBlock.data.cells;
  var n = cells.length;
  var startLeftPct = cells[leftIdx].widthPct != null ? cells[leftIdx].widthPct : (100/n);
  var startRightPct = cells[rightIdx].widthPct != null ? cells[rightIdx].widthPct : (100/n);
  var totalPct = startLeftPct + startRightPct;
  var badge = showDimensionBadge();

  function onMove(ev){
    var deltaPct = ((ev.clientX - startX) / rowRect.width) * 100;
    var newLeft = clampNum(round2(startLeftPct + deltaPct), 5, totalPct-5);
    var newRight = round2(totalPct - newLeft);
    cells[leftIdx].widthPct = newLeft;
    cells[rightIdx].widthPct = newRight;
    var cellEls = rowEl.querySelectorAll(':scope > .pb-cell');
    if(cellEls[leftIdx]) cellEls[leftIdx].style.width = newLeft+'%';
    if(cellEls[rightIdx]) cellEls[rightIdx].style.width = newRight+'%';
    updateDimensionBadge(badge, ev.clientX, ev.clientY, Math.round(newLeft)+'% / '+Math.round(newRight)+'%');
  }
  function onUp(){
    document.removeEventListener('mousemove', onMove);
    document.removeEventListener('mouseup', onUp);
    removeDimensionBadge(badge);
    markDirty();
  }
  document.addEventListener('mousemove', onMove);
  document.addEventListener('mouseup', onUp);
}

function startBlockResize(e, handle){
  e.preventDefault();
  var dir = handle.getAttribute('data-dir');
  var el = handle.closest('.pb-block');
  var block = blocksById[el.getAttribute('data-block-id')];
  var cell = el.closest('.pb-cell');
  var rowBlock = null, rowEl = null, cellIndex = null, neighborIndex = null, startCellPct = null, startNeighborPct = null;

  var hasWidthDrag = cell && /[ew]/.test(dir);
  if(hasWidthDrag){
    var rowId = cell.getAttribute('data-cell-owner');
    rowBlock = blocksById[rowId];
    rowEl = canvasEl.querySelector('.pb-row-flex[data-row-id="'+rowId+'"]');
    cellIndex = parseInt(cell.getAttribute('data-cell-index'),10);
    var n = rowBlock.data.cells.length;
    startCellPct = rowBlock.data.cells[cellIndex].widthPct != null ? rowBlock.data.cells[cellIndex].widthPct : (100/n);
    neighborIndex = dir.indexOf('e')>=0 ? cellIndex+1 : cellIndex-1;
    if(!rowBlock.data.cells[neighborIndex]){ hasWidthDrag = false; neighborIndex = null; }
    else startNeighborPct = rowBlock.data.cells[neighborIndex].widthPct != null ? rowBlock.data.cells[neighborIndex].widthPct : (100/n);
  }

  var imgEl = el.querySelector('.pb-figure img');
  var isImage = block.type === 'image' && !!imgEl;
  var figureEl = isImage ? el.querySelector('.pb-figure') : null;
  var hasFigureWidthDrag = !cell && isImage && /[ew]/.test(dir);
  var containerW, startFigurePct;
  if(hasFigureWidthDrag){
    containerW = el.getBoundingClientRect().width;
    startFigurePct = (block.data.widthPct!=null && block.data.widthPct!=='') ? block.data.widthPct : 100;
  }
  var hasHeightDrag = !!imgEl && /[ns]/.test(dir);

  var startX = e.clientX, startY = e.clientY;
  var startW = el.getBoundingClientRect().width;
  var startH = imgEl ? imgEl.getBoundingClientRect().height : 0;
  var badge = showDimensionBadge();

  function onMove(ev){
    var dx = ev.clientX - startX, dy = ev.clientY - startY;
    var labelParts = [];

    if(hasWidthDrag){
      var signedDx = dir.indexOf('w')>=0 ? -dx : dx;
      var totalPct = startCellPct + startNeighborPct;
      var deltaPct = (signedDx / rowEl.getBoundingClientRect().width) * 100;
      var newSelf = clampNum(round2(startCellPct + deltaPct), 5, totalPct-5);
      var newNeighbor = round2(totalPct - newSelf);
      rowBlock.data.cells[cellIndex].widthPct = newSelf;
      rowBlock.data.cells[neighborIndex].widthPct = newNeighbor;
      var cellEls = rowEl.querySelectorAll(':scope > .pb-cell');
      if(cellEls[cellIndex]) cellEls[cellIndex].style.width = newSelf+'%';
      if(cellEls[neighborIndex]) cellEls[neighborIndex].style.width = newNeighbor+'%';
      labelParts.push(Math.round(newSelf)+'% breed');
    }

    if(hasFigureWidthDrag){
      var signedDxFig = dir.indexOf('w')>=0 ? -dx : dx;
      var deltaPctFig = (signedDxFig / containerW) * 100;
      var newPct = clampNum(round2(startFigurePct + deltaPctFig), 10, 100);
      block.data.widthPct = newPct;
      figureEl.style.maxWidth = newPct+'%';
      figureEl.style.marginLeft = 'auto';
      figureEl.style.marginRight = 'auto';
      labelParts.push(Math.round(newPct)+'% breed');
    }

    if(hasHeightDrag){
      var signedDy = dir.indexOf('n')>=0 ? -dy : dy;
      var newH = clampNum(startH + signedDy, 60, 1200);
      var newW = startW;
      if(dir.length===2){
        if(ev.shiftKey){
          var signedDx2 = dir.indexOf('w')>=0 ? -dx : dx;
          newW = clampNum(startW + signedDx2, 60, 2000);
        } else {
          newW = startW * (newH / startH);
        }
      }
      var newAspect = round2(newW / newH);
      block.data.aspectRatio = newAspect;
      imgEl.style.aspectRatio = newAspect;
      imgEl.style.height = 'auto';
      labelParts.push(Math.round(newH)+'px hoog');
    }

    updateDimensionBadge(badge, ev.clientX, ev.clientY, labelParts.join(' · '));
  }
  function onUp(){
    document.removeEventListener('mousemove', onMove);
    document.removeEventListener('mouseup', onUp);
    removeDimensionBadge(badge);
    if(hasFigureWidthDrag){
      settingsEl.querySelectorAll('[data-bind="data.widthPct"]').forEach(function(inp){ inp.value = block.data.widthPct; });
    }
    markDirty();
  }
  document.addEventListener('mousemove', onMove);
  document.addEventListener('mouseup', onUp);
}

/* ============================================================
   Settings panel
   ============================================================ */
function renderSettingsPanel(){
  if(!selectedId || !blocksById[selectedId]){
    settingsEl.innerHTML = '<div class="pbe-noselect">Selecteer een blok in het canvas om het te stylen.</div>';
    return;
  }
  var block = blocksById[selectedId];
  var def = BLOCKS[block.type];
  var html = '<h2>'+def.label+'</h2><span class="pbe-block-type">Blokinstellingen</span>';
  html += contentFieldsHtml(block);
  html += styleFieldsHtml(block);
  settingsEl.innerHTML = html;
  bindGalleryButtons(block);
  bindImageUploadButtons(block);
}

function fontDatalist(){
  return '<datalist id="pbeFontList">'+GOOGLE_FONTS.map(function(f){ return '<option value="'+esc(f)+'">'; }).join('')+'</datalist>';
}
var datalistInjected = false;
function ensureDatalist(){
  if(datalistInjected) return;
  datalistInjected = true;
  document.body.insertAdjacentHTML('beforeend', fontDatalist());
}
ensureDatalist();

function styleFieldsHtml(block){
  var s = block.settings;
  var html = '<div class="pbe-group"><div class="pbe-group-title">Stijl</div>';

  html += '<div class="pbe-field"><label>Lettertype</label><input type="text" list="pbeFontList" placeholder="Standaard" data-bind="settings.fontFamily" value="'+esc(s.fontFamily||'')+'"></div>';
  html += '<div class="pbe-row"><div class="pbe-field"><label>Tekstgrootte (px)</label><input type="number" min="10" max="120" data-bind="settings.fontSize" value="'+esc(s.fontSize||'')+'" placeholder="Standaard"></div>'
        + '<div class="pbe-field"><label>Radius (px)</label><input type="number" min="0" max="80" data-bind="settings.radius" value="'+esc(s.radius!=null?s.radius:0)+'"></div></div>';

  html += '<div class="pbe-field"><label>Tekstkleur</label><div class="pbe-color-row">'
    + '<input type="color" data-bind="settings.textColor" value="'+esc(s.textColor||'#2b2420')+'">'
    + '<input type="text" data-bind="settings.textColor" value="'+esc(s.textColor||'')+'" placeholder="Standaard">'
    + '<button type="button" class="pbe-clear-btn" data-action="clear-field" data-target="settings.textColor">wis</button></div></div>';

  html += '<div class="pbe-field"><label>Achtergrondkleur</label><div class="pbe-color-row">'
    + '<input type="color" data-bind="settings.bgColor" value="'+esc(s.bgColor||'#ffffff')+'">'
    + '<input type="text" data-bind="settings.bgColor" value="'+esc(s.bgColor||'')+'" placeholder="Transparant">'
    + '<button type="button" class="pbe-clear-btn" data-action="clear-field" data-target="settings.bgColor">wis</button></div></div>';

  html += '<div class="pbe-field"><label>Uitlijning</label><div class="pbe-seg" data-seg="settings.align">'
    + ['left','center','right'].map(function(a){ return '<button type="button" data-val="'+a+'" class="'+(s.align===a?'is-active':'')+'">'+({left:'Links',center:'Midden',right:'Rechts'}[a])+'</button>'; }).join('')
    + '</div></div>';

  html += '<div class="pbe-row"><div class="pbe-field"><label>Padding boven/onder</label><input type="number" min="0" max="240" data-bind="settings.paddingY" value="'+esc(s.paddingY!==''&&s.paddingY!=null?s.paddingY:'')+'"></div>'
        + '<div class="pbe-field"><label>Padding zijkant</label><input type="number" min="0" max="160" data-bind="settings.paddingX" value="'+esc(s.paddingX!==''&&s.paddingX!=null?s.paddingX:'')+'"></div></div>';

  html += '<div class="pbe-field"><label>Schaduw</label><div class="pbe-seg" data-seg="settings.shadow">'
    + ['none','sm','md','lg'].map(function(a){ return '<button type="button" data-val="'+a+'" class="'+(s.shadow===a?'is-active':'')+'">'+({none:'Geen',sm:'Zacht',md:'Middel',lg:'Groot'}[a])+'</button>'; }).join('')
    + '</div></div>';

  html += '<div class="pbe-field"><label>Animatie bij scrollen</label><select data-bind="settings.animation">'
    + [['none','Geen'],['fade-up','Fade omhoog'],['fade-in','Fade in'],['slide-left','Schuif van links'],['slide-right','Schuif van rechts'],['zoom-in','Inzoomen']]
      .map(function(o){ return '<option value="'+o[0]+'" '+(s.animation===o[0]?'selected':'')+'>'+o[1]+'</option>'; }).join('')
    + '</select></div>';

  html += '</div>';
  return html;
}

function contentFieldsHtml(block){
  var d = block.data, html = '<div class="pbe-group"><div class="pbe-group-title">Inhoud</div>';
  switch(block.type){
    case 'title':
      html += '<div class="pbe-field"><label>Kop-niveau</label><div class="pbe-seg" data-seg="data.level">'
        + ['h1','h2','h3'].map(function(l){ return '<button type="button" data-val="'+l+'" class="'+(d.level===l?'is-active':'')+'">'+l.toUpperCase()+'</button>'; }).join('') + '</div></div>';
      html += '<p style="font-size:.78rem;color:#8a7c6c">Tip: dubbelklik de titel in het canvas om de tekst te wijzigen.</p>';
      break;
    case 'text':
      html += '<p style="font-size:.78rem;color:#8a7c6c">Dubbelklik de tekst in het canvas om te bewerken. Selecteer tekst en gebruik Ctrl+B / Ctrl+I voor vet/cursief.</p>';
      break;
    case 'hero':
      html += imageFieldHtml(d.bgImage, 'bgImage', 'Achtergrondfoto');
      html += '<div class="pbe-field"><label>Overlay-donkerte</label><input type="range" min="0" max="100" data-bind="data.overlay" value="'+(d.overlay!=null?d.overlay:45)+'"></div>';
      html += '<div class="pbe-field"><label>Knop-link (URL)</label><input type="text" data-bind="data.buttonHref" value="'+esc(d.buttonHref||'')+'" placeholder="contact.php of https://..."></div>';
      html += '<p style="font-size:.78rem;color:#8a7c6c">Tip: dubbelklik titel, ondertitel of knoptekst in het canvas om te bewerken. Laat de knoptekst leeg om de knop op de site te verbergen.</p>';
      break;
    case 'image':
      html += imageFieldHtml(d.src, 'src', 'Foto');
      html += '<div class="pbe-field"><label>Alt-tekst (SEO)</label><input type="text" data-bind="data.alt" value="'+esc(d.alt||'')+'"></div>';
      html += '<div class="pbe-field"><label>Link (optioneel)</label><input type="text" data-bind="data.link" value="'+esc(d.link||'')+'" placeholder="https://..."></div>';
      html += '<div class="pbe-field"><label>Breedte</label><div class="pbe-seg" data-seg="data.width"><button type="button" data-val="contained" class="'+(d.width!=='full'?'is-active':'')+'">Passend</button><button type="button" data-val="full" class="'+(d.width==='full'?'is-active':'')+'">Volledig</button></div></div>';
      html += '<div class="pbe-field"><label>Aangepaste breedte (% — laat leeg voor automatisch)</label><div style="display:flex;gap:8px;align-items:center">'
        + '<input type="range" min="10" max="100" style="flex:1" data-bind="data.widthPct" value="'+(d.widthPct!==''&&d.widthPct!=null?d.widthPct:100)+'">'
        + '<input type="number" min="10" max="100" style="width:60px" data-bind="data.widthPct" value="'+esc(d.widthPct!=null?d.widthPct:'')+'"></div>'
        + '<button type="button" class="pbe-clear-btn" data-action="clear-field" data-target="data.widthPct" style="margin-top:4px">wis (automatisch)</button></div>';
      html += '<p style="font-size:.78rem;color:#8a7c6c">Tip: sleep de foto tegen de rand van een ander blok om ze naast elkaar te zetten, of gebruik hierboven een vaste breedte om de foto kleiner te maken. Dubbelklik het bijschrift om het te wijzigen.</p>';
      break;
    case 'gallery':
      html += '<div id="pbeGalleryList" class="pbe-gallery-list">' + (d.images||[]).map(function(img,i){
        var sizeBtn = d.layout!=='masonry' ? '<button type="button" class="pbe-gallery-size-btn'+(img.size==='large'?' is-active':'')+'" data-gallery-size="'+i+'" title="Grote tegel (2x zo groot in het raster)">&#9974;</button>' : '';
        return '<div class="pbe-gallery-item" data-idx="'+i+'"><img src="'+esc(imgSrc(img.src))+'"><input type="text" placeholder="Bijschrift" data-gallery-caption="'+i+'" value="'+esc(img.caption||'')+'">'+sizeBtn+'<button type="button" data-gallery-remove="'+i+'">&#10005;</button></div>';
      }).join('') + '</div>';
      html += '<button type="button" class="pbe-upload-btn" id="pbeGalleryAdd">+ Foto\'s toevoegen</button>';
      html += '<input type="file" id="pbeGalleryFile" accept="image/*" multiple style="display:none">';
      html += '<div class="pbe-row" style="margin-top:14px"><div class="pbe-field"><label>Kolommen</label><select data-bind="data.columns">'+[2,3,4].map(function(c){return '<option value="'+c+'" '+(d.columns==c?'selected':'')+'>'+c+'</option>';}).join('')+'</select></div>'
        + '<div class="pbe-field"><label>Layout</label><div class="pbe-seg" data-seg="data.layout"><button type="button" data-val="grid" class="'+(d.layout!=='masonry'?'is-active':'')+'">Grid</button><button type="button" data-val="masonry" class="'+(d.layout==='masonry'?'is-active':'')+'">Pinterest</button></div></div></div>';
      break;
    case 'video':
      html += '<div class="pbe-field"><label>YouTube / Vimeo-link of .mp4</label><input type="text" data-bind="data.url" value="'+esc(d.url||'')+'" placeholder="https://youtube.com/watch?v=..."></div>';
      break;
    case 'button':
      html += '<div class="pbe-field"><label>Link (URL)</label><input type="text" data-bind="data.href" value="'+esc(d.href||'')+'" placeholder="contact.php of https://..."></div>';
      html += '<div class="pbe-field"><label>Stijl</label><div class="pbe-seg" data-seg="data.style">'
        + ['solid','outline','ghost'].map(function(a){ return '<button type="button" data-val="'+a+'" class="'+(d.style===a?'is-active':'')+'">'+({solid:'Vol',outline:'Rand',ghost:'Tekst'}[a])+'</button>'; }).join('') + '</div></div>';
      html += '<div class="pbe-field"><label>Grootte</label><div class="pbe-seg" data-seg="data.size">'
        + ['sm','md','lg'].map(function(a){ return '<button type="button" data-val="'+a+'" class="'+(d.size===a?'is-active':'')+'">'+({sm:'S',md:'M',lg:'L'}[a])+'</button>'; }).join('') + '</div></div>';
      html += '<p style="font-size:.78rem;color:#8a7c6c">Tip: dubbelklik de knoptekst in het canvas om te wijzigen.</p>';
      break;
    case 'divider':
      html += '<div class="pbe-field"><label>Stijl</label><div class="pbe-seg" data-seg="data.style">'
        + ['line','dots','space'].map(function(a){ return '<button type="button" data-val="'+a+'" class="'+(d.style===a?'is-active':'')+'">'+({line:'Lijn',dots:'Stippen',space:'Ruimte'}[a])+'</button>'; }).join('') + '</div></div>';
      break;
    case 'quote':
      html += '<p style="font-size:.78rem;color:#8a7c6c">Dubbelklik de quote of naam in het canvas om te bewerken.</p>';
      break;
    case 'columns':
      html += '<div class="pbe-field"><label>Aantal kolommen</label><div class="pbe-seg" data-seg="data.count">'
        + [1,2,3,4].map(function(c){ return '<button type="button" data-val="'+c+'" class="'+((d.cols?d.cols.length:d.count)==c?'is-active':'')+'">'+c+'</button>'; }).join('') + '</div></div>';
      html += '<div class="pbe-field"><label>Tussenruimte (px)</label><input type="number" min="0" max="120" data-bind="data.gap" value="'+esc(d.gap!=null?d.gap:32)+'"></div>';
      html += '<p style="font-size:.78rem;color:#8a7c6c">Sleep blokken uit het paneel links direct in een kolom.</p>';
      break;
    case 'contact':
      html += '<div class="pbe-field"><label>Knoptekst</label><input type="text" data-bind="data.buttonText" value="'+esc(d.buttonText||'')+'"></div>';
      html += '<p style="font-size:.78rem;color:#8a7c6c">Dubbelklik de titel in het canvas om te wijzigen. Berichten komen terecht in de <code>messages</code>-tabel.</p>';
      break;
    case 'recent':
      html += '<div class="pbe-field"><label>Bron</label><select data-bind="data.source">'
        + [['mixed','Dieren + albums + blog (gemengd)'],['animals','Enkel dieren'],['albums','Enkel albums'],['posts','Enkel blogposts'],['pages','Enkel pagina\'s']]
          .map(function(o){ return '<option value="'+o[0]+'" '+(d.source===o[0]?'selected':'')+'>'+o[1]+'</option>'; }).join('')
        + '</select></div>';
      html += '<div class="pbe-field"><label>Aantal</label><input type="number" min="1" max="12" data-bind="data.count" value="'+(d.count!=null?d.count:3)+'"></div>';
      html += '<p style="font-size:.78rem;color:#8a7c6c">Toont automatisch de nieuwste gepubliceerde content, ververst vanzelf.</p>';
      break;
    case 'html':
      html += '<div class="pbe-field"><label>HTML-code</label><textarea data-bind="data.code" rows="10" style="font-family:monospace;font-size:.78rem">'+esc(d.code||'')+'</textarea></div>';
      html += '<p style="font-size:.78rem;color:#8a7c6c">Geavanceerd: wordt ongefilterd op de pagina geplaatst.</p>';
      break;
    case 'row':
      html += '<label class="pbe-check-inline"><input type="checkbox" data-bind="data.mobileStack" '+((!('mobileStack' in d)||d.mobileStack)?'checked':'')+'> Naast elkaar houden op mobiel</label>';
      html += '<div class="pbe-field" style="margin-top:14px"><label>Tussenruimte (px)</label><input type="number" min="0" max="120" data-bind="data.gap" value="'+(d.gap!=null?d.gap:24)+'"></div>';
      html += '<button type="button" class="pbe-upload-btn" id="pbeUnwrapRow" style="margin-top:10px">Rij opheffen (blokken terugzetten)</button>';
      html += '<p style="font-size:.78rem;color:#8a7c6c;margin-top:10px">Tip: sleep de deelstreep tussen de foto\'s/blokken om de breedte te verdelen, of gebruik de resize-handles wanneer een blok geselecteerd is.</p>';
      break;
  }
  html += '</div>';
  return html;
}
function imageFieldHtml(src, fieldKey, label){
  fieldKey = fieldKey || 'src';
  var html = '<div class="pbe-field"><label>'+esc(label||'Foto')+'</label>';
  if(src) html += '<img src="'+esc(imgSrc(src))+'" style="width:100%;border-radius:8px;margin-bottom:8px">';
  html += '<button type="button" class="pbe-upload-btn" data-image-field="'+fieldKey+'">'+(src?'Andere foto kiezen':'Foto uploaden')+'</button>';
  html += '<input type="file" accept="image/*" style="display:none" data-image-file="'+fieldKey+'">';
  if(src) html += '<button type="button" class="pbe-clear-btn" data-clear-image="'+fieldKey+'" style="margin-top:8px">Foto verwijderen</button>';
  html += '</div>';
  return html;
}

// generic bind: text/number/select/color/textarea inputs with data-bind
settingsEl.addEventListener('input', function(e){
  var el = e.target.closest('[data-bind]');
  if(!el) return;
  var block = blocksById[selectedId]; if(!block) return;
  var path = el.getAttribute('data-bind');
  var val = el.type === 'checkbox' ? el.checked : el.value;
  set(block, path, val);
  // sync any other input bound to the same path (e.g. color swatch + hex
  // text, or a range slider + its numeric twin)
  settingsEl.querySelectorAll('[data-bind="'+path+'"]').forEach(function(sib){ if(sib!==el) sib.value = val; });
  updateBlockDom(block.id);
});
settingsEl.addEventListener('click', function(e){
  var seg = e.target.closest('.pbe-seg button');
  if(seg){
    var block = blocksById[selectedId]; if(!block) return;
    var path = seg.parentElement.getAttribute('data-seg');
    var val = seg.getAttribute('data-val');
    if(path==='data.count'){
      resizeColumns(block, parseInt(val,10));
      renderSettingsPanel();
      updateBlockDom(block.id);
      return;
    }
    set(block, path, val);
    seg.parentElement.querySelectorAll('button').forEach(function(b){ b.classList.remove('is-active'); });
    seg.classList.add('is-active');
    updateBlockDom(block.id);
    return;
  }
  var clearBtn = e.target.closest('[data-action="clear-field"]');
  if(clearBtn){
    var block2 = blocksById[selectedId]; if(!block2) return;
    set(block2, clearBtn.getAttribute('data-target'), '');
    renderSettingsPanel();
    updateBlockDom(block2.id);
    return;
  }
  var galRemove = e.target.closest('[data-gallery-remove]');
  if(galRemove){
    var block3 = blocksById[selectedId]; if(!block3) return;
    block3.data.images.splice(parseInt(galRemove.getAttribute('data-gallery-remove'),10),1);
    renderSettingsPanel();
    updateBlockDom(block3.id);
    return;
  }
  var galSize = e.target.closest('[data-gallery-size]');
  if(galSize){
    var block4 = blocksById[selectedId]; if(!block4) return;
    var img4 = block4.data.images[parseInt(galSize.getAttribute('data-gallery-size'),10)];
    img4.size = img4.size==='large' ? '' : 'large';
    renderSettingsPanel();
    updateBlockDom(block4.id);
    return;
  }
  var unwrapBtn = e.target.closest('#pbeUnwrapRow');
  if(unwrapBtn){
    var rowBlock = blocksById[selectedId];
    if(!rowBlock || rowBlock.type!=='row') return;
    var idx = state.blocks.indexOf(rowBlock);
    if(idx===-1) return;
    var flat = [];
    rowBlock.data.cells.forEach(function(c){ flat = flat.concat(c.blocks||[]); });
    state.blocks.splice.apply(state.blocks, [idx,1].concat(flat));
    selectedId = null;
    renderCanvas();
    renderSettingsPanel();
    return;
  }
});
settingsEl.addEventListener('change', function(e){
  var galCap = e.target.closest('[data-gallery-caption]');
  if(galCap){
    var block = blocksById[selectedId]; if(!block) return;
    block.data.images[parseInt(galCap.getAttribute('data-gallery-caption'),10)].caption = galCap.value;
    updateBlockDom(block.id);
  }
});
function resizeColumns(block, count){
  var cols = block.data.cols || [];
  if(count > cols.length){
    while(cols.length < count) cols.push({blocks:[]});
  } else if(count < cols.length){
    var overflow = [];
    cols.slice(count).forEach(function(c){ overflow = overflow.concat(c.blocks||[]); });
    cols = cols.slice(0, count);
    cols[cols.length-1].blocks = (cols[cols.length-1].blocks||[]).concat(overflow);
  }
  block.data.cols = cols;
  block.data.count = count;
}

function bindImageUploadButtons(block){
  settingsEl.querySelectorAll('[data-image-field]').forEach(function(btn){
    var key = btn.getAttribute('data-image-field');
    var file = settingsEl.querySelector('[data-image-file="'+key+'"]');
    btn.addEventListener('click', function(){ file.click(); });
    file.addEventListener('change', function(){
      if(!file.files[0]) return;
      btn.textContent = 'Bezig met uploaden...';
      uploadImage(file.files[0]).then(function(path){
        block.data[key] = path;
        renderSettingsPanel();
        updateBlockDom(block.id);
      }).catch(function(err){ alert(err); btn.textContent = 'Opnieuw proberen'; });
    });
  });
  settingsEl.querySelectorAll('[data-clear-image]').forEach(function(btn){
    btn.addEventListener('click', function(){
      block.data[btn.getAttribute('data-clear-image')] = '';
      renderSettingsPanel();
      updateBlockDom(block.id);
    });
  });
}
function bindGalleryButtons(block){
  var addBtn = document.getElementById('pbeGalleryAdd');
  var file = document.getElementById('pbeGalleryFile');
  if(!addBtn || !file) return;
  addBtn.addEventListener('click', function(){ file.click(); });
  file.addEventListener('change', function(){
    var files = Array.prototype.slice.call(file.files);
    if(!files.length) return;
    addBtn.textContent = 'Bezig met uploaden...';
    Promise.all(files.map(uploadImage)).then(function(paths){
      paths.forEach(function(p){ block.data.images.push({src:p, alt:'', caption:''}); });
      renderSettingsPanel();
      updateBlockDom(block.id);
    }).catch(function(err){ alert(err); }).finally(function(){ addBtn.textContent = "+ Foto's toevoegen"; });
  });
}

function uploadImage(file){
  var fd = new FormData();
  fd.append('image', file);
  fd.append('csrf', PAGE.csrf);
  return fetch('upload-block-image.php', { method:'POST', body: fd, credentials:'same-origin' })
    .then(function(r){ return r.json(); })
    .then(function(j){ if(!j.ok) throw new Error(j.error||'Upload mislukt'); return j.path; });
}

/* ============================================================
   Save
   ============================================================ */
function markDirty(){
  dirty = true;
  saveStateEl.textContent = 'Niet opgeslagen wijzigingen…';
  scheduleAutosave();
}
var scheduleAutosave = debounce(function(){ saveNow(); }, 1500);

var TYPE_VIEW_PREFIX = { page:'../page.php?slug=', animal:'../animal.php?slug=', album:'../album.php?slug=', post:'../post.php?slug=' };
var contentType = PAGE.type || 'page';
var coverImage = PAGE.cover_image || '';

function collectPayload(){
  var payload = {
    csrf: PAGE.csrf, id: state.id, type: contentType,
    title: titleInput.value.trim() || state.title,
    slug: state.slug,
    meta_title: document.getElementById('pbeMetaTitle').value,
    meta_description: document.getElementById('pbeMetaDesc').value,
    published: document.getElementById('pbePublished').checked,
    blocks: state.blocks
  };
  if(contentType === 'page'){
    payload.show_in_nav = document.getElementById('pbeShowNav').checked;
    payload.is_homepage = document.getElementById('pbeIsHomepage').checked;
  } else {
    payload.cover_image = coverImage;
    payload.description = document.getElementById('pbeDescription').value;
  }
  return payload;
}
function saveNow(){
  if(!dirty) return;
  saveStateEl.textContent = 'Opslaan…';
  var payload = collectPayload();
  fetch('page-save.php', {
    method:'POST', credentials:'same-origin',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify(payload)
  }).then(function(r){ return r.json(); }).then(function(j){
    if(j.ok){
      dirty = false;
      state.slug = j.slug; state.title = payload.title;
      saveStateEl.textContent = 'Opgeslagen ✓';
      document.getElementById('pbeViewLink').href = TYPE_VIEW_PREFIX[contentType]+encodeURIComponent(j.slug);
    } else {
      saveStateEl.textContent = 'Fout: '+(j.error||'onbekend');
    }
  }).catch(function(){ saveStateEl.textContent = 'Opslaan mislukt (netwerk)'; });
}
document.getElementById('pbeSaveBtn').addEventListener('click', saveNow);
var watchedSettingsFields = [titleInput, document.getElementById('pbeMetaTitle'), document.getElementById('pbeMetaDesc'), document.getElementById('pbePublished')];
if(contentType === 'page'){
  watchedSettingsFields.push(document.getElementById('pbeShowNav'), document.getElementById('pbeIsHomepage'));
} else {
  var coverBtn = document.getElementById('pbeCoverUploadBtn');
  var coverFile = document.getElementById('pbeCoverFile');
  if(coverBtn && coverFile){
    coverBtn.addEventListener('click', function(){ coverFile.click(); });
    coverFile.addEventListener('change', function(){
      if(!coverFile.files[0]) return;
      coverBtn.textContent = 'Bezig met uploaden...';
      uploadImage(coverFile.files[0]).then(function(path){
        coverImage = path;
        coverBtn.textContent = 'Andere foto kiezen';
        markDirty();
      }).catch(function(err){ alert(err); coverBtn.textContent = 'Foto uploaden'; });
    });
  }
}
watchedSettingsFields.forEach(function(el){ el.addEventListener('input', markDirty); el.addEventListener('change', markDirty); });

window.addEventListener('beforeunload', function(e){
  if(dirty){ e.preventDefault(); e.returnValue=''; }
});

/* ============================================================
   Device toggle
   ============================================================ */
document.querySelectorAll('.pbe-device-toggle button').forEach(function(btn){
  btn.addEventListener('click', function(){
    document.querySelectorAll('.pbe-device-toggle button').forEach(function(b){ b.classList.remove('is-active'); });
    btn.classList.add('is-active');
    canvasEl.classList.toggle('is-mobile', btn.getAttribute('data-device')==='mobile');
  });
});

/* ============================================================
   Palette
   ============================================================ */
(function buildPalette(){
  var palette = document.getElementById('pbePalette');
  var html = '';
  GROUP_ORDER.forEach(function(group){
    var items = Object.keys(BLOCKS).filter(function(k){ return BLOCKS[k].group===group && !BLOCKS[k].hidden; });
    if(!items.length) return;
    html += '<div class="pbe-section-title">'+group+'</div>';
    items.forEach(function(type){
      var def = BLOCKS[type];
      html += '<div class="pbe-block-btn" data-new-type="'+type+'"><span class="ico">'+def.icon+'</span>'+def.label+'</div>';
    });
  });
  palette.innerHTML = html;
})();

/* ============================================================
   Init
   ============================================================ */
renderCanvas();
renderSettingsPanel();

})();
