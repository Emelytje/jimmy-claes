<?php require 'functions.php';

// Geen echte "Gewervelde"-categorie in de database (bewust — dat wilden we
// niet), enkel een vaste ingangspagina die naar de 5 echte klassen linkt.
const GEWERVELDE_CLASS_TITLES = ['Amfibieën', 'Reptielen', 'Vissen', 'Vogels', 'Zoogdieren'];

$catEnCols = pb_has_column('categories','title_en') ? ', title_en, description_en' : '';
$ph = implode(',', array_fill(0, count(GEWERVELDE_CLASS_TITLES), '?'));
$st = db()->prepare("SELECT id, title, slug, description, cover_image$catEnCols FROM categories WHERE parent_id IS NULL AND published=1 AND title IN ($ph) ORDER BY FIELD(title, $ph)");
$st->execute(array_merge(GEWERVELDE_CLASS_TITLES, GEWERVELDE_CLASS_TITLES));
$rows = $st->fetchAll();

[$gewerveldeColorKey, $gewerveldeColorDefault] = pb_class_color_map()['gewervelde'];
$classColor = setting($gewerveldeColorKey, $gewerveldeColorDefault);

header_html(t('vertebrates'), 'Amphibians, reptiles, fish, birds and mammals.');
?>
<section class="hero" style="background:<?=e($classColor)?>"><div><h1><?=e(t('vertebrates'))?></h1></div></section>
<?=pb_render_back_button()?>
<main class="wrap">
<?php if(!$rows): ?>
<p style="text-align:center;color:var(--ink-soft)"><?=e(t('no_classes_yet'))?></p>
<?php else: ?>
<div class="grid pb-cat-grid">
<?php foreach($rows as $r):
    $url = 'category.php?slug='.$r['slug'];
    $photo = $r['cover_image'] ?: pb_category_random_photo((int)$r['id']);
    $rowColor = pb_class_theme_color((int)$r['id']);
    $desc = localized_field($r, 'description');
?>
<article class="card pb-cat-grid-card"><a href="<?=e($url)?>"><?php if($photo): ?><img src="<?=e($photo)?>" alt="" loading="lazy"><?php else: ?><div class="pb-cat-grid-noimg"<?=$rowColor ? ' style="background:'.e($rowColor).'"' : ''?>></div><?php endif; ?></a><div class="pad"><h3><?=e(localized_field($r,'title'))?></h3><?php if($desc): ?><p><?=e($desc)?></p><?php endif; ?><a class="btn" href="<?=e($url)?>"><?=e(t('discover'))?></a></div></article>
<?php endforeach; ?>
</div>
<?php endif; ?>
</main>
<?php footer_html(); ?>
