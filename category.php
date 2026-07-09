<?php require 'functions.php';
$slug = $_GET['slug'] ?? '';
$st = db()->prepare('SELECT * FROM categories WHERE slug=? AND published=1');
$st->execute([$slug]);
$c = $st->fetch();
if(!$c){ http_response_code(404); die('Pagina niet gevonden'); }
track_view('categories', $c['id']);
$blocks = pb_decode_blocks($c['blocks'] ?? null);
$ctx = ['category_id' => (int)$c['id']];
if($blocks){
    $fontHref = pb_google_fonts_link_href(pb_font_families_used($blocks));
    $headExtra = $fontHref ? '<link rel="stylesheet" href="'.e($fontHref).'">' : '';
    header_html($c['meta_title'] ?: $c['title'], $c['meta_description'] ?: $c['description'], '', $headExtra);
    echo '<main class="pb-page">'.render_blocks($blocks, 0, $ctx).'</main>';
    footer_html();
    exit;
}
header_html($c['meta_title'] ?: $c['title'], $c['meta_description'] ?: $c['description']); ?>
<?=pb_render_breadcrumb(pb_category_ancestors($c['id']))?>
<section class="hero"><div><h1><?=e($c['title'])?></h1><?php if($c['description']): ?><p><?=nl2br(e($c['description']))?></p><?php endif; ?></div></section>
<main class="wrap"><?=pb_render_subcategories([], $ctx)?></main>
<?php footer_html(); ?>
