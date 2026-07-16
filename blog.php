<?php require 'functions.php'; header_html(t('blog_title'), 'Verhalen en nieuws over onze dierenfotografie.');
?>
<main class="wrap"><h1><?=e(t('blog_title'))?></h1><div class="grid">
<?php foreach(db()->query('SELECT * FROM posts WHERE published=1 ORDER BY created_at DESC') as $p): ?>
<article class="card"><a href="post.php?slug=<?=e($p['slug'])?>"><?php if($p['cover_image']): ?><img src="<?=e($p['cover_image'])?>" alt=""><?php endif; ?></a><div class="pad"><h3><?=e($p['title'])?></h3><p><?=e($p['excerpt'])?></p><a class="btn" href="post.php?slug=<?=e($p['slug'])?>"><?=e(t('read_more'))?></a></div></article>
<?php endforeach; ?>
</div></main>
<?php footer_html(); ?>
