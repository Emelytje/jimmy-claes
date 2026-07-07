<?php
require __DIR__.'/inc.php';

const PBE_TYPES = [
    'page'     => ['table'=>'pages',      'list'=>'pages.php',                 'view'=>'../page.php?slug=',   'label'=>"Pagina", 'desc_col'=>null],
    'animal'   => ['table'=>'animals',    'list'=>'content.php?type=animal',   'view'=>'../animal.php?slug=', 'label'=>'Dier', 'desc_col'=>'description'],
    'album'    => ['table'=>'albums',     'list'=>'content.php?type=album',    'view'=>'../album.php?slug=',  'label'=>'Album', 'desc_col'=>'description'],
    'post'     => ['table'=>'posts',      'list'=>'content.php?type=post',     'view'=>'../post.php?slug=',   'label'=>'Blogpost', 'desc_col'=>'excerpt'],
    'category' => ['table'=>'categories', 'list'=>'content.php?type=category', 'view'=>'../category.php?slug=', 'label'=>'Categorie', 'desc_col'=>'description'],
];


$type = $_GET['type'] ?? 'page';
if(!isset(PBE_TYPES[$type])) $type = 'page';
$typeInfo = PBE_TYPES[$type];

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare("SELECT * FROM {$typeInfo['table']} WHERE id=?");
$st->execute([$id]);
$page = $st->fetch();
if(!$page){ header('Location: '.$typeInfo['list']); exit; }

$blocks = pb_decode_blocks($page['blocks'] ?? null);
$initial = [
    'type' => $type,
    'id' => (int)$page['id'],
    'title' => $page['title'],
    'slug' => $page['slug'],
    'meta_title' => $page['meta_title'],
    'meta_description' => $page['meta_description'],
    'published' => (bool)$page['published'],
    'show_in_nav' => (bool)($page['show_in_nav'] ?? false),
    'is_homepage' => (bool)($page['is_homepage'] ?? false),
    'cover_image' => $page['cover_image'] ?? '',
    'description' => $typeInfo['desc_col'] ? ($page[$typeInfo['desc_col']] ?? '') : '',
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
<link rel="stylesheet" href="../assets/style.css?v=<?=asset_v(__DIR__.'/../assets/style.css')?>">
<link rel="stylesheet" href="assets/admin.css?v=<?=asset_v(__DIR__.'/assets/admin.css')?>">
</head>
<body class="admin pbe-body">

<div class="pbe">
  <div class="pbe-topbar">
    <a href="<?=e($typeInfo['list'])?>" title="Terug" style="color:#fff;font-size:1.1rem">&larr;</a>
    <input type="text" class="pbe-title" id="pbeTitleInput" value="<?=e($page['title'])?>" placeholder="Titel">
    <div class="pbe-device-toggle">
      <button type="button" data-device="desktop" class="is-active">Desktop</button>
      <button type="button" data-device="mobile">Mobiel</button>
    </div>
    <div class="spacer"></div>
    <span class="pbe-savestate" id="pbeSaveState">Alles opgeslagen</span>
    <a href="<?=e($typeInfo['view'].$page['slug'])?>" id="pbeViewLink" target="_blank">Bekijk <?=strtolower(e($typeInfo['label']))?> &#8599;</a>
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
    <h2><?=e($typeInfo['label'])?>-instellingen</h2>
    <label class="pbe-check"><input type="checkbox" id="pbePublished" <?=$page['published']?'checked':''?>> Live (gepubliceerd)</label>
    <?php if($type==='page'): ?>
    <label class="pbe-check"><input type="checkbox" id="pbeShowNav" <?=$page['show_in_nav']?'checked':''?>> Tonen in hoofdmenu</label>
    <label class="pbe-check"><input type="checkbox" id="pbeIsHomepage" <?=!empty($page['is_homepage'])?'checked':''?>> Instellen als homepage</label>
    <p style="font-size:.78rem;color:#8a7c6c;margin-top:-8px">Als homepage vervangt deze pagina de standaard-voorpagina volledig.</p>
    <?php else: ?>
    <div class="pbe-field">
      <label>Coverfoto (gebruikt in overzichten)</label>
      <?php if(!empty($page['cover_image'])): ?><img src="../<?=e($page['cover_image'])?>" style="width:100%;border-radius:8px;margin-bottom:8px"><?php endif; ?>
      <button type="button" class="pbe-upload-btn" id="pbeCoverUploadBtn"><?=!empty($page['cover_image'])?'Andere foto kiezen':'Foto uploaden'?></button>
      <input type="file" accept="image/*" style="display:none" id="pbeCoverFile">
    </div>
    <div class="pbe-field">
      <label>Korte omschrijving</label>
      <textarea id="pbeDescription" rows="3" placeholder="Tekst die zichtbaar is onder de titel in overzichten (bv. op de homepage)"><?=e($page[$typeInfo['desc_col']] ?? '')?></textarea>
    </div>
    <p style="font-size:.78rem;color:#8a7c6c;margin-top:-8px">Dit is andere, zichtbare tekst dan de SEO-omschrijving hieronder (die is enkel voor zoekmachines).</p>
    <?php if($type==='category'): ?>
    <div class="pbe-field">
      <label>Bovenliggende categorie</label>
      <select id="pbeParentCategory">
        <option value="">Geen (hoofdcategorie)</option>
        <?=pbe_category_options($page['parent_id'] ?? null, $page['id'])?>
      </select>
    </div>
    <?php endif; ?>
    <?php if($type==='animal'): ?>
    <div class="pbe-field">
      <label>Categorie</label>
      <select id="pbeAnimalCategory">
        <option value="">Geen categorie</option>
        <?=pbe_category_options($page['category_id'] ?? null)?>
      </select>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    <div class="pbe-field"><label>SEO-titel</label><input type="text" id="pbeMetaTitle" value="<?=e($page['meta_title'])?>" placeholder="<?=e($page['title'])?>"></div>
    <div class="pbe-field"><label>SEO-omschrijving</label><textarea id="pbeMetaDesc" rows="3" placeholder="Korte omschrijving voor zoekmachines"><?=e($page['meta_description'])?></textarea></div>
    <p style="font-size:.78rem;color:#8a7c6c">URL: <code><?=e($typeInfo['view'].$page['slug'])?></code> (past automatisch mee met de titel bij opslaan)</p>
  </div>
</div>

<script src="assets/sortable.min.js?v=<?=asset_v(__DIR__.'/assets/sortable.min.js')?>"></script>
<script>window.PBE_INITIAL = <?=json_encode($initial, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP)?>;</script>
<script src="assets/pagebuilder.js?v=<?=asset_v(__DIR__.'/assets/pagebuilder.js')?>"></script>
<script>
(function(){
  var modal = document.getElementById('pbeSettingsModal');
  document.getElementById('pbeSettingsBtn').addEventListener('click', function(){ modal.classList.add('is-open'); });
  modal.addEventListener('click', function(e){ if(e.target===modal || e.target.closest('[data-close-modal]')) modal.classList.remove('is-open'); });
})();
</script>
</body>
</html>
