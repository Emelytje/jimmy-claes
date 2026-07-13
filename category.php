<?php require 'functions.php';
$slug = $_GET['slug'] ?? '';
$st = db()->prepare('SELECT * FROM categories WHERE slug=? AND published=1');
$st->execute([$slug]);
$c = $st->fetch();
if(!$c){ http_response_code(404); die('Pagina niet gevonden'); }
track_view('categories', $c['id']);
$blocks = pb_decode_blocks($c['blocks'] ?? null);
$ctx = ['category_id' => (int)$c['id']];
$classColor = pb_class_theme_color((int)$c['id']);
if($blocks){
    $fontHref = pb_google_fonts_link_href(pb_font_families_used($blocks));
    $headExtra = $fontHref ? '<link rel="stylesheet" href="'.e($fontHref).'">' : '';
    header_html($c['meta_title'] ?: $c['title'], $c['meta_description'] ?: $c['description'], '', $headExtra);
    echo '<section class="hero"'.($classColor ? ' style="background:'.e($classColor).'"' : '').'><div><h1>'.e($c['title']).'</h1></div></section>';
    echo pb_render_back_button();
    echo '<main class="pb-page pb-animal-photos">'.render_blocks($blocks, 0, $ctx).'</main>';
    footer_html();
    exit;
}
header_html($c['meta_title'] ?: $c['title'], $c['meta_description'] ?: $c['description']); ?>
<section class="hero"<?=$classColor ? ' style="background:'.e($classColor).'"' : ''?>><div><h1><?=e($c['title'])?></h1><?php if($c['description']): ?><p><?=nl2br(e($c['description']))?></p><?php endif; ?></div></section>
<?=pb_render_back_button()?>
<main class="wrap"><?=pb_render_subcategories([], $ctx)?></main>
<?php footer_html(); ?>
