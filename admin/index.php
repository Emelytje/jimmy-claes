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
$typeLabels = ['page'=>t('type_page'), 'animal'=>t('type_animal'), 'album'=>t('type_album'), 'post'=>t('type_post')];
$unreadMessages = (int)db()->query('SELECT COUNT(*) c FROM messages WHERE is_read=0')->fetch()['c'];

admin_header(t('admin_dashboard'), 'index');
?>
<div class="a-stats">
  <div class="a-stat"><div class="num"><?=$total?></div><div class="label"><?=e(t('pages_total'))?></div></div>
  <div class="a-stat"><div class="num"><?=$live?></div><div class="label"><?=e(t('live'))?></div></div>
  <div class="a-stat"><div class="num"><?=$draft?></div><div class="label"><?=e(t('draft'))?></div></div>
  <div class="a-stat"><div class="num"><?=$contentTotal?></div><div class="label"><?=e(t('content_total'))?></div></div>
  <a class="a-stat" href="messages.php" style="text-decoration:none;color:inherit"><div class="num"><?=$unreadMessages?></div><div class="label"><?=e(t('new_messages'))?></div></a>
</div>

<div class="a-card">
  <div class="a-card-pad">
    <h2 style="margin:0 0 4px"><?=e(t('most_viewed'))?></h2>
    <p style="margin:0 0 16px;color:#8a7c6c;font-size:.9rem"><?=e(t('most_viewed_desc'))?></p>
    <?php if($topViewed && $topViewed[0]['views'] > 0): ?>
    <div class="a-table-wrap"><table class="a-table">
      <tr><th><?=e(t('title_label'))?></th><th><?=e(t('type_label'))?></th><th><?=e(t('visits'))?></th><th></th></tr>
      <?php foreach($topViewed as $tv): if($tv['views']<1) continue; ?>
      <tr>
        <td data-label="<?=e(t('title_label'))?>"><strong><?=e($tv['title'])?></strong></td>
        <td data-label="<?=e(t('type_label'))?>"><?=e($typeLabels[$tv['type']])?></td>
        <td data-label="<?=e(t('visits'))?>"><?=(int)$tv['views']?></td>
        <td class="row-actions"><a class="a-btn a-btn-sm a-btn-ghost" href="../<?=$typeUrls[$tv['type']].e($tv['slug'])?>" target="_blank"><?=e(t('view'))?></a></td>
      </tr>
      <?php endforeach; ?>
    </table></div>
    <?php else: ?>
    <p style="color:#8a7c6c;font-size:.9rem;margin:0"><?=e(t('no_visits_yet'))?></p>
    <?php endif; ?>
  </div>
</div>

<div class="a-card">
  <div class="a-card-pad" style="display:flex;align-items:center;justify-content:space-between">
    <div>
      <h2 style="margin:0 0 4px"><?=e(t('recently_edited'))?></h2>
      <p style="margin:0;color:#8a7c6c;font-size:.9rem"><?=e(t('recently_edited_desc'))?></p>
    </div>
    <a class="a-btn" href="pages.php"><?=e(t('admin_pages'))?> &rarr;</a>
  </div>
  <?php if($recent): ?>
  <div class="a-table-wrap"><table class="a-table">
    <tr><th><?=e(t('title_label'))?></th><th><?=e(t('status'))?></th><th><?=e(t('updated'))?></th><th></th></tr>
    <?php foreach($recent as $p): ?>
    <tr>
      <td data-label="<?=e(t('title_label'))?>"><strong><?=e($p['title'])?></strong></td>
      <td data-label="<?=e(t('status'))?>"><span class="a-pill <?=$p['published']?'a-pill-live':'a-pill-draft'?>"><?=$p['published']?t('live'):t('draft')?></span></td>
      <td data-label="<?=e(t('updated'))?>"><?=e(date('d-m-Y H:i',strtotime($p['updated_at'])))?></td>
      <td class="row-actions"><a class="a-btn a-btn-sm a-btn-ghost" href="page-edit.php?id=<?=$p['id']?>"><?=e(t('edit'))?></a></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
  <?php else: ?>
  <div class="a-empty">
    <h3><?=e(t('no_pages_yet'))?></h3>
    <p><?=e(t('build_first_page'))?></p>
    <a class="a-btn" href="pages.php"><?=e(t('new_page_btn'))?></a>
  </div>
  <?php endif; ?>
</div>
<?php admin_footer(); ?>
