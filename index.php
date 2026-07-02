<?php require 'functions.php'; header_html('', setting('meta_description')); ?>
<section class="hero"><div><h1><?=e(setting('intro_title','Dieren door de lens'))?></h1><p><?=e(setting('intro_text','Een zachte fotografieplek met verhalen, beelden en pagina’s per dier.'))?></p></div></section>
<main class="wrap"><h2>Pagina’s per dier</h2><div class="grid">
<?php foreach(db()->query('SELECT * FROM animals WHERE published=1 ORDER BY sort_order,title') as $a): ?>
<article class="card"><a href="animal.php?slug=<?=e($a['slug'])?>"><?php if($a['cover_image']): ?><img src="<?=e($a['cover_image'])?>" alt=""><?php endif; ?></a><div class="pad"><h3><?=e($a['title'])?></h3><p><?=e($a['description'])?></p><a class="btn" href="animal.php?slug=<?=e($a['slug'])?>">Bekijk foto’s</a></div></article>
<?php endforeach; ?>
</div></main><?php footer_html(); ?>
