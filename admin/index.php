<?php
require __DIR__.'/inc.php';

$total = (int)db()->query('SELECT COUNT(*) c FROM pages')->fetch()['c'];
$live = (int)db()->query('SELECT COUNT(*) c FROM pages WHERE published=1')->fetch()['c'];
$draft = $total - $live;
$recent = db()->query('SELECT * FROM pages ORDER BY updated_at DESC LIMIT 6')->fetchAll();

admin_header('Dashboard', 'index');
?>
<div class="a-stats">
  <div class="a-stat"><div class="num"><?=$total?></div><div class="label">Pagina's totaal</div></div>
  <div class="a-stat"><div class="num"><?=$live?></div><div class="label">Live</div></div>
  <div class="a-stat"><div class="num"><?=$draft?></div><div class="label">Concepten</div></div>
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
