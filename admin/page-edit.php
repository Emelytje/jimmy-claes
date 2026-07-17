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
$typeLabels = ['page'=>t('type_page'), 'animal'=>t('type_animal'), 'album'=>t('type_album'), 'post'=>t('type_post'), 'category'=>t('type_category')];
$typeLabel = $typeLabels[$type];

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare("SELECT * FROM {$typeInfo['table']} WHERE id=?");
$st->execute([$id]);
$page = $st->fetch();
if(!$page){ header('Location: '.$typeInfo['list']); exit; }

$blocks = pb_decode_blocks($page['blocks'] ?? null);
$usingFallbackPreview = false;
if(!$blocks && $type !== 'page'){
    $blocks = pb_default_blocks_for($type, $page);
    $usingFallbackPreview = (bool)$blocks;
}
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
    'drive_url' => $type === 'animal' ? ($page['drive_url'] ?? '') : '',
    'blocks' => $blocks,
    'csrf' => csrf_token(),
    'lang' => current_lang(),
];

$zoosForPicker = [];
try{
    $zooCols = 'id, title'.(pb_has_column('zoos','city') ? ', city' : '').(pb_has_column('zoos','country') ? ', country' : '');
    $zoosForPicker = db()->query("SELECT $zooCols FROM zoos WHERE published=1 ORDER BY sort_order, title")->fetchAll();
    foreach($zoosForPicker as &$z){ $z['label'] = zoo_label($z); }
    unset($z);
}catch(Exception $e){}
?><!doctype html>
<html lang="<?=e(current_lang())?>">
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
    <a href="<?=e($typeInfo['list'])?>" title="<?=e(t('back'))?>" style="color:#fff;font-size:1.1rem">&larr;</a>
    <input type="text" class="pbe-title" id="pbeTitleInput" value="<?=e($page['title'])?>" placeholder="<?=e(t('title_placeholder'))?>">
    <div class="pbe-device-toggle">
      <button type="button" data-device="desktop" class="is-active"><?=e(t('desktop'))?></button>
      <button type="button" data-device="mobile"><?=e(t('mobile'))?></button>
    </div>
    <div class="spacer"></div>
    <span class="pbe-savestate" id="pbeSaveState"><?=e(t('all_saved'))?></span>
    <a href="<?=e($typeInfo['view'].$page['slug'])?>" id="pbeViewLink" target="_blank"><?=e(t('view_type'))?> <?=mb_strtolower(e($typeLabel))?> &#8599;</a>
    <button type="button" class="a-btn a-btn-sm" id="pbeSettingsBtn"><?=e(t('settings_btn'))?></button>
    <button type="button" class="a-btn a-btn-sm" id="pbeSaveBtn"><?=e(t('save'))?></button>
  </div>

  <div class="pbe-col pbe-palette" id="pbePalette"></div>

  <div class="pbe-col pbe-canvas-wrap">
    <?php if($usingFallbackPreview): ?>
    <div class="notice" style="margin:12px 16px 0"><?=e(t('fallback_preview_notice'))?></div>
    <?php endif; ?>
    <div class="pbe-canvas-scale" id="pbeCanvasScale">
      <div class="pbe-canvas pbe-sortable-zone" id="pbeCanvas"></div>
    </div>
  </div>

  <div class="pbe-col pbe-settings" id="pbeSettings"></div>
</div>

<div class="pbe-modal-backdrop" id="pbeSettingsModal">
  <div class="pbe-modal">
    <button type="button" class="pbe-modal-close" data-close-modal>&times;</button>
    <h2><?=e($typeLabel)?><?=e(t('type_settings'))?></h2>
    <label class="pbe-check"><input type="checkbox" id="pbePublished" <?=$page['published']?'checked':''?>> <?=e(t('live_published'))?></label>
    <?php if($type==='page'): ?>
    <label class="pbe-check"><input type="checkbox" id="pbeShowNav" <?=$page['show_in_nav']?'checked':''?>> <?=e(t('show_in_main_menu'))?></label>
    <label class="pbe-check"><input type="checkbox" id="pbeIsHomepage" <?=!empty($page['is_homepage'])?'checked':''?>> <?=e(t('set_as_homepage'))?></label>
    <p style="font-size:.78rem;color:#8a7c6c;margin-top:-8px"><?=e(t('homepage_hint'))?></p>
    <?php else: ?>
    <div class="pbe-field">
      <label><?=e(t('cover_photo_label'))?></label>
      <?php if(!empty($page['cover_image'])): ?><img src="../<?=e($page['cover_image'])?>" style="width:100%;border-radius:8px;margin-bottom:8px"><?php endif; ?>
      <div class="pbe-dropzone" id="pbeCoverDropzone">
        <button type="button" class="pbe-upload-btn" id="pbeCoverUploadBtn"><?=!empty($page['cover_image'])?e(t('choose_other_photo')):e(t('upload_photo'))?></button>
        <input type="file" accept="image/*" style="display:none" id="pbeCoverFile">
      </div>
    </div>
    <div class="pbe-field">
      <label><?=e(t('short_description'))?></label>
      <textarea id="pbeDescription" rows="3" placeholder="<?=e(t('short_description_placeholder'))?>"><?=e($page[$typeInfo['desc_col']] ?? '')?></textarea>
    </div>
    <p style="font-size:.78rem;color:#8a7c6c;margin-top:-8px"><?=e(t('short_vs_seo_hint'))?></p>
    <?php if($type==='category'): ?>
    <div class="pbe-field">
      <label><?=e(t('parent_category'))?></label>
      <select id="pbeParentCategory">
        <option value=""><?=e(t('none_top_level'))?></option>
        <?=pbe_category_options($page['parent_id'] ?? null, $page['id'])?>
      </select>
    </div>
    <?php endif; ?>
    <?php if($type==='animal'): ?>
    <div class="pbe-field">
      <label><?=e(t('category_label'))?></label>
      <select id="pbeAnimalCategory">
        <option value=""><?=e(t('no_category'))?></option>
        <?=pbe_category_options($page['category_id'] ?? null)?>
      </select>
    </div>
    <div class="pbe-field">
      <label><?=e(t('drive_url_label'))?></label>
      <input type="text" id="pbeDriveUrl" value="<?=e($page['drive_url'] ?? '')?>" placeholder="https://drive.google.com/drive/folders/...">
      <p style="font-size:.78rem;color:#8a7c6c;margin-top:4px"><?=e(t('drive_url_hint'))?></p>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    <div class="pbe-field"><label><?=e(t('seo_title'))?></label><input type="text" id="pbeMetaTitle" value="<?=e($page['meta_title'])?>" placeholder="<?=e($page['title'])?>"></div>
    <div class="pbe-field"><label><?=e(t('seo_description'))?></label><textarea id="pbeMetaDesc" rows="3" placeholder="<?=e(t('seo_description_placeholder'))?>"><?=e($page['meta_description'])?></textarea></div>
    <p style="font-size:.78rem;color:#8a7c6c">URL: <code><?=e($typeInfo['view'].$page['slug'])?></code> (<?=e(t('url_autoupdate_hint'))?>)</p>
  </div>
</div>

<script src="assets/sortable.min.js?v=<?=asset_v(__DIR__.'/assets/sortable.min.js')?>"></script>
<script>window.PBE_INITIAL = <?=json_encode($initial, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP)?>;
window.PBE_ZOOS = <?=json_encode($zoosForPicker, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP)?>;</script>
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
