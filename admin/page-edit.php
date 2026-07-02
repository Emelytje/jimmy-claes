<?php
require __DIR__.'/inc.php';

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM pages WHERE id=?');
$st->execute([$id]);
$page = $st->fetch();
if(!$page){ header('Location: pages.php'); exit; }

$blocks = pb_decode_blocks($page['blocks']);
$initial = [
    'id' => (int)$page['id'],
    'title' => $page['title'],
    'slug' => $page['slug'],
    'meta_title' => $page['meta_title'],
    'meta_description' => $page['meta_description'],
    'published' => (bool)$page['published'],
    'show_in_nav' => (bool)$page['show_in_nav'],
    'blocks' => $blocks,
    'csrf' => csrf_token(),
];
?><!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($page['title'])?> - Pagebuilder</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500&family=Karla:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin pbe-body">

<div class="pbe">
  <div class="pbe-topbar">
    <a href="pages.php" title="Terug naar pagina's" style="color:#fff;font-size:1.1rem">&larr;</a>
    <input type="text" class="pbe-title" id="pbeTitleInput" value="<?=e($page['title'])?>" placeholder="Paginatitel">
    <div class="pbe-device-toggle">
      <button type="button" data-device="desktop" class="is-active">Desktop</button>
      <button type="button" data-device="mobile">Mobiel</button>
    </div>
    <div class="spacer"></div>
    <span class="pbe-savestate" id="pbeSaveState">Alles opgeslagen</span>
    <a href="../page.php?slug=<?=e($page['slug'])?>" id="pbeViewLink" target="_blank">Bekijk pagina &#8599;</a>
    <button type="button" class="a-btn a-btn-sm" id="pbeSettingsBtn">Instellingen</button>
    <button type="button" class="a-btn a-btn-sm" id="pbeSaveBtn">Opslaan</button>
  </div>

  <div class="pbe-col pbe-palette" id="pbePalette"></div>

  <div class="pbe-col pbe-canvas-wrap">
    <div class="pbe-canvas-scale" id="pbeCanvasScale">
      <div class="pbe-canvas pbe-sortable-zone" id="pbeCanvas"></div>
    </div>
  </div>

  <div class="pbe-col pbe-settings" id="pbeSettings"></div>
</div>

<div class="pbe-modal-backdrop" id="pbeSettingsModal">
  <div class="pbe-modal">
    <button type="button" class="pbe-modal-close" data-close-modal>&times;</button>
    <h2>Pagina-instellingen</h2>
    <label class="pbe-check"><input type="checkbox" id="pbePublished" <?=$page['published']?'checked':''?>> Pagina is live (gepubliceerd)</label>
    <label class="pbe-check"><input type="checkbox" id="pbeShowNav" <?=$page['show_in_nav']?'checked':''?>> Tonen in hoofdmenu</label>
    <div class="pbe-field"><label>SEO-titel</label><input type="text" id="pbeMetaTitle" value="<?=e($page['meta_title'])?>" placeholder="<?=e($page['title'])?>"></div>
    <div class="pbe-field"><label>SEO-omschrijving</label><textarea id="pbeMetaDesc" rows="3" placeholder="Korte omschrijving voor zoekmachines"><?=e($page['meta_description'])?></textarea></div>
    <p style="font-size:.78rem;color:#8a7c6c">URL: <code>/page.php?slug=<?=e($page['slug'])?></code> (past automatisch mee met de titel bij opslaan)</p>
  </div>
</div>

<script src="assets/sortable.min.js"></script>
<script>window.PBE_INITIAL = <?=json_encode($initial, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP)?>;</script>
<script src="assets/pagebuilder.js"></script>
<script>
(function(){
  var modal = document.getElementById('pbeSettingsModal');
  document.getElementById('pbeSettingsBtn').addEventListener('click', function(){ modal.classList.add('is-open'); });
  modal.addEventListener('click', function(e){ if(e.target===modal || e.target.closest('[data-close-modal]')) modal.classList.remove('is-open'); });
})();
</script>
</body>
</html>
