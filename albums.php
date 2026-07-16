<?php require 'functions.php'; header_html(t('albums_title'), 'Bekijk onze fotoalbums per thema.'); ?>
<main class="wrap"><h1><?=e(t('albums_title'))?></h1><div class="grid">
<?php foreach(db()->query('SELECT * FROM albums WHERE published=1 ORDER BY sort_order,title') as $al): ?>
<article class="card"><a href="album.php?slug=<?=e($al['slug'])?>"><?php if($al['cover_image']): ?><img src="<?=e($al['cover_image'])?>" alt=""><?php endif; ?></a><div class="pad"><h3><?=e($al['title'])?></h3><p><?=e($al['description'])?></p><a class="btn" href="album.php?slug=<?=e($al['slug'])?>"><?=e(t('view_album'))?></a></div></article>
<?php endforeach; ?>
</div></main>
<?php footer_html(); ?>
