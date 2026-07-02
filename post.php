<?php require 'functions.php';
$slug=$_GET['slug']??''; $st=db()->prepare('SELECT * FROM posts WHERE slug=? AND published=1'); $st->execute([$slug]); $p=$st->fetch();
if(!$p){ http_response_code(404); die('Pagina niet gevonden'); }
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
