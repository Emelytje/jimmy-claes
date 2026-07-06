<?php
require __DIR__.'/inc.php';

$total = (int)db()->query('SELECT COUNT(*) c FROM pages')->fetch()['c'];
$live = (int)db()->query('SELECT COUNT(*) c FROM pages WHERE published=1')->fetch()['c'];
$draft = $total - $live;
$recent = db()->query('SELECT * FROM pages ORDER BY updated_at DESC LIMIT 6')->fetchAll();

$contentTotal = (int)db()->query("SELECT
    (SELECT COUNT(*) FROM pages) + (SELECT COUNT(*) FROM animals) +
    (SELECT COUNT(*) FROM albums) + (SELECT COUNT(*) FROM posts) c")->fetch()['c'];
$topViewed = db()->query("
    SELECT 'page' AS type, id, title, slug, views FROM pages
    UNION ALL SELECT 'animal', id, title, slug, views FROM animals
    UNION ALL SELECT 'album', id, title, slug, views FROM albums
    UNION ALL SELECT 'post', id, title, slug, views FROM posts
    ORDER BY views DESC LIMIT 5
")->fetchAll();
$typeUrls = ['page'=>'page.php?slug=', 'animal'=>'animal.php?slug=', 'album'=>'album.php?slug=', 'post'=>'post.php?slug='];
$typeLabels = ['page'=>"Pagina", 'animal'=>'Dier', 'album'=>'Album', 'post'=>'Blogpost'];

admin_header('Dashboard', 'index');
?>
<div class="a-stats">
  <div class="a-stat"><div class="num"><?=$total?></div><div class="label">Pagina's totaal</div></div>
  <div class="a-stat"><div class="num"><?=$live?></div><div class="label">Live</div></div>
  <div class="a-stat"><div class="num"><?=$draft?></div><div class="label">Concepten</div></div>
  <div class="a-stat"><div class="num"><?=$contentTotal?></div><div class="label">Alle content</div></div>
</div>

<div class="a-card">
  <div class="a-card-pad">
    <h2 style="margin:0 0 4px">Meest bekeken</h2>
    <p style="margin:0 0 16px;color:#8a7c6c;font-size:.9rem">Over alle pagina's, dieren, albums en blogposts.</p>
    <?php if($topViewed && $topViewed[0]['views'] > 0): ?>
    <table class="a-table">
      <tr><th>Titel</th><th>Type</th><th>Bezoeken</th><th></th></tr>
      <?php foreach($topViewed as $t): if($t['views']<1) continue; ?>
      <tr>
        <td><strong><?=e($t['title'])?></strong></td>
        <td><?=e($typeLabels[$t['type']])?></td>
        <td><?=(int)$t['views']?></td>
        <td class="row-actions"><a class="a-btn a-btn-sm a-btn-ghost" href="../<?=$typeUrls[$t['type']].e($t['slug'])?>" target="_blank">Bekijk</a></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php else: ?>
    <p style="color:#8a7c6c;font-size:.9rem;margin:0">Nog geen bezoeken geregistreerd.</p>
    <?php endif; ?>
  </div>
</div>

<div class="a-card">
  <div class="a-card-pad" style="display:flex;align-items:center;justify-content:space-between">
    <div>
      <h2 style="margin:0 0 4px">Recent bewerkt</h2>
      <p style="margin:0;color:#8a7c6c;font-size:.9rem">De laatste pagina's waar je aan werkte.</p>
    </div>
    <a class="a-btn" href="pages.php">Alle pagina's &rarr;</a>
  </div>
  <?php if($recent): ?>
  <table class="a-table">
    <tr><th>Titel</th><th>Status</th><th>Bijgewerkt</th><th></th></tr>
    <?php foreach($recent as $p): ?>
    <tr>
      <td><strong><?=e($p['title'])?></strong></td>
      <td><span class="a-pill <?=$p['published']?'a-pill-live':'a-pill-draft'?>"><?=$p['published']?'Live':'Concept'?></span></td>
      <td><?=e(date('d-m-Y H:i',strtotime($p['updated_at'])))?></td>
      <td class="row-actions"><a class="a-btn a-btn-sm a-btn-ghost" href="page-edit.php?id=<?=$p['id']?>">Bewerken</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php else: ?>
  <div class="a-empty">
    <h3>Nog geen pagina's</h3>
    <p>Bouw je eerste pagina met de drag-and-drop pagebuilder.</p>
    <a class="a-btn" href="pages.php">+ Nieuwe pagina</a>
  </div>
  <?php endif; ?>
</div>
<?php admin_footer(); ?>
