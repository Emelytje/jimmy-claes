<?php require 'functions.php'; $slug=$_GET['slug']??''; $st=db()->prepare('SELECT * FROM albums WHERE slug=? AND published=1'); $st->execute([$slug]); $a=$st->fetch(); if(!$a){ http_response_code(404); die('Pagina niet gevonden'); }
track_view('albums', $a['id']);
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
<section class="hero"><div><h1><?=e($a['title'])?></h1><p><?=nl2br(e($a['description']))?></p></div></section>
<main class="wrap"><div class="gallery <?=e($a['layout'])?>">
<?php $st=db()->prepare('SELECT * FROM album_photos WHERE album_id=? ORDER BY sort_order,id DESC'); $st->execute([$a['id']]); foreach($st as $p): ?>
<figure class="photo"><img src="<?=e($p['image_path'])?>" alt="<?=e($p['title'])?>"><figcaption class="caption"><strong><?=e($p['title'])?></strong><br><?=nl2br(e($p['caption']))?></figcaption></figure>
<?php endforeach; ?>
</div></main><?php footer_html(); ?>
