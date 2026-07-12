<?php require 'functions.php'; $slug=$_GET['slug']??''; $st=db()->prepare('SELECT * FROM animals WHERE slug=? AND published=1'); $st->execute([$slug]); $a=$st->fetch(); if(!$a){ http_response_code(404); die('Pagina niet gevonden'); }
track_view('animals', $a['id']);
$blocks = pb_decode_blocks($a['blocks'] ?? null);
if($blocks){
    $fontHref = pb_google_fonts_link_href(pb_font_families_used($blocks));
    $headExtra = $fontHref ? '<link rel="stylesheet" href="'.e($fontHref).'">' : '';
    header_html($a['meta_title'] ?: $a['title'], $a['meta_description'] ?: $a['description'], '', $headExtra);
    echo '<main class="pb-page">'.render_blocks($blocks).'</main>';
    footer_html();
    exit;
}
header_html($a['meta_title'] ?: $a['title'], $a['meta_description'] ?: $a['description']); ?>
<?php
$breadcrumbChain = !empty($a['category_id']) ? pb_category_ancestors((int)$a['category_id']) : [];
$st = db()->prepare('SELECT * FROM photos WHERE animal_id=? ORDER BY sort_order,id DESC');
$st->execute([$a['id']]);
$photos = $st->fetchAll();
?>
<?=pb_render_breadcrumb($breadcrumbChain, $a['title'])?>
<section class="hero"><div><h1><?=e($a['title'])?></h1><?php if($a['description']): ?><p><?=nl2br(e($a['description']))?></p><?php endif; ?></div></section>
<main class="wrap">
<?php if($photos): ?>
<div class="gallery <?=e($a['layout'])?>">
<?php foreach($photos as $p): ?>
<figure class="photo"><img src="<?=e($p['image_path'])?>" alt="<?=e($p['title'])?>"><figcaption class="caption"><strong><?=e($p['title'])?></strong><br><?=nl2br(e($p['caption']))?></figcaption></figure>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="pb-empty-photos"><p>Nog geen foto's van <?=e($a['title'])?> — binnenkort meer!</p></div>
<?php endif; ?>
</main><?php footer_html(); ?>
