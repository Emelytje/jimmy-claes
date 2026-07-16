<?php require 'functions.php'; header_html(t('nav_animals'), 'Bekijk al onze dieren.'); ?>
<main class="wrap"><h1><?=e(t('nav_animals'))?></h1><div class="grid">
<?php foreach(db()->query('SELECT * FROM animals WHERE published=1 ORDER BY sort_order,title') as $a): ?>
<article class="card"><a href="animal.php?slug=<?=e($a['slug'])?>"><?php if($a['cover_image']): ?><img src="<?=e($a['cover_image'])?>" alt=""><?php endif; ?></a><div class="pad"><h3><?=e($a['title'])?></h3><p><?=e(localized_field($a,'description'))?></p><a class="btn" href="animal.php?slug=<?=e($a['slug'])?>"><?=e(t('view_photos'))?></a></div></article>
<?php endforeach; ?>
</div></main>
<?php footer_html(); ?>
