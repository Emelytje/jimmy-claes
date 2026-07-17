<?php require 'functions.php';
$slug=$_GET['slug']??''; $st=db()->prepare('SELECT * FROM posts WHERE slug=? AND published=1'); $st->execute([$slug]); $p=$st->fetch();
if(!$p){ http_response_code(404); die(t('page_not_found')); }
track_view('posts', $p['id']);
$blocks = pb_decode_blocks($p['blocks'] ?? null);
if($blocks){
    $headExtra = pb_page_head_extra($blocks);
    header_html($p['meta_title'] ?: $p['title'], $p['meta_description'] ?: $p['excerpt'], '', $headExtra);
    echo '<main class="pb-page">'.render_blocks($blocks).'</main>';
    footer_html();
    exit;
}
header_html($p['meta_title'] ?: $p['title'], $p['meta_description'] ?: $p['excerpt']);
?>
<main class="wrap">
<article style="max-width:760px;margin:auto">
<h1><?=e($p['title'])?></h1>
<?php if($p['cover_image']): ?><img src="<?=e($p['cover_image'])?>" style="width:100%;border-radius:20px;margin-bottom:20px"><?php endif; ?>
<div><?=nl2br(e($p['content']))?></div>
</article>
</main>
<?php footer_html(); ?>
