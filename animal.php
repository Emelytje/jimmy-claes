<?php require 'functions.php'; $slug=$_GET['slug']??''; $st=db()->prepare('SELECT * FROM animals WHERE slug=? AND published=1'); $st->execute([$slug]); $a=$st->fetch(); if(!$a){ http_response_code(404); die(t('page_not_found')); }
track_view('animals', $a['id']);
$blocks = pb_decode_blocks($a['blocks'] ?? null);
$classColor = !empty($a['category_id']) ? pb_class_theme_color((int)$a['category_id']) : '';
// Soortnamen zijn al Latijn en worden nooit vertaald — enkel de beschrijving
// (indien ingevuld) kan een Engelse variant hebben.
$descLocalized = localized_field($a, 'description');
if($blocks){
    $fontHref = pb_google_fonts_link_href(pb_font_families_used($blocks));
    $headExtra = $fontHref ? '<link rel="stylesheet" href="'.e($fontHref).'">' : '';
    header_html($a['meta_title'] ?: $a['title'], $a['meta_description'] ?: $descLocalized, '', $headExtra);
    echo '<section class="hero"'.($classColor ? ' style="background:'.e($classColor).'"' : '').'><div><h1>'.e($a['title']).'</h1></div></section>';
    echo pb_render_back_button();
    echo '<main class="pb-page pb-animal-photos">'.render_blocks($blocks).'</main>';
    footer_html();
    exit;
}
header_html($a['meta_title'] ?: $a['title'], $a['meta_description'] ?: $descLocalized); ?>
<?php
$st = db()->prepare('SELECT * FROM photos WHERE animal_id=? ORDER BY sort_order,id DESC');
$st->execute([$a['id']]);
$photos = $st->fetchAll();
?>
<section class="hero"<?=$classColor ? ' style="background:'.e($classColor).'"' : ''?>><div><h1><?=e($a['title'])?></h1><?php if($descLocalized): ?><p><?=nl2br(e($descLocalized))?></p><?php endif; ?></div></section>
<?=pb_render_back_button()?>
<main class="wrap pb-animal-photos">
<?php if($photos): ?>
<div class="gallery <?=e($a['layout'])?>">
<?php foreach($photos as $p): ?>
<figure class="photo"><img src="<?=e($p['image_path'])?>" alt="<?=e($p['title'])?>"><figcaption class="caption"><strong><?=e($p['title'])?></strong><br><?=nl2br(e($p['caption']))?></figcaption></figure>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="pb-empty-photos"><p><?=e(t('no_photos_yet'))?> <?=e($a['title'])?> — <?=e(t('more_soon'))?></p></div>
<?php endif; ?>
</main><?php footer_html(); ?>
