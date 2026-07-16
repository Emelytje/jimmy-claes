<?php
require __DIR__.'/inc.php';

if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if($action==='create'){
        $title = trim($_POST['title'] ?? '');
        if($title!==''){
            $slug = slugify($title);
            $base = $slug; $i = 2;
            $chk = db()->prepare('SELECT COUNT(*) c FROM pages WHERE slug=?');
            while(true){ $chk->execute([$slug]); if((int)$chk->fetch()['c']===0) break; $slug = $base.'-'.$i; $i++; }
            $st = db()->prepare('INSERT INTO pages(title,slug,blocks,published,show_in_nav) VALUES(?,?,?,0,0)');
            $st->execute([$title, $slug, '[]']);
            header('Location: page-edit.php?id='.db()->lastInsertId()); exit;
        }
    } elseif($action==='delete'){
        $id = (int)($_POST['id'] ?? 0);
        $st = db()->prepare('DELETE FROM pages WHERE id=?'); $st->execute([$id]);
    } elseif($action==='toggle_published'){
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare('UPDATE pages SET published = 1-published WHERE id=?')->execute([$id]);
    } elseif($action==='toggle_nav'){
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare('UPDATE pages SET show_in_nav = 1-show_in_nav WHERE id=?')->execute([$id]);
    }
    header('Location: pages.php'); exit;
}

$pages = db()->query('SELECT * FROM pages ORDER BY sort_order, updated_at DESC')->fetchAll();

$homepageNeedsMigration = false;
foreach($pages as $p){
    if((int)$p['is_homepage'] === 1 && !pb_decode_blocks($p['blocks'] ?? null)){ $homepageNeedsMigration = true; break; }
}

admin_header("Pagina's", 'pages');
?>
<?php if($homepageNeedsMigration): ?>
<div class="notice" style="margin-bottom:20px">Je homepage draait nog op de vaste basisopmaak, niet op blokken — daarom lijkt "Home" leeg in de editor. <a href="migrate-homepage-to-blocks.php">Zet dit één keer om naar blokken</a> om de homepage net als elke andere pagina te kunnen bewerken (bv. om het "Gewervelde / Ongewervelde"-blok toe te voegen).</div>
<?php endif; ?>
<div class="a-card">
  <div class="a-card-pad">
    <form method="post" class="a-inline-form">
      <?=csrf_field()?>
      <input type="hidden" name="action" value="create">
      <div class="a-field"><label>Titel van de nieuwe pagina</label><input type="text" name="title" placeholder="Bijv. Over ons" required></div>
      <button class="a-btn" type="submit">+ Pagina aanmaken</button>
    </form>
  </div>
</div>

<div class="a-card">
<?php if($pages): ?>
  <div class="a-table-wrap"><table class="a-table">
    <tr><th>Titel</th><th>Slug</th><th>Status</th><th>In menu</th><th>Bijgewerkt</th><th></th></tr>
    <?php foreach($pages as $p): ?>
    <tr>
      <td data-label="Titel"><strong><?=e($p['title'])?></strong></td>
      <td data-label="Slug"><code>/page.php?slug=<?=e($p['slug'])?></code></td>
      <td data-label="Status">
        <form method="post" style="display:inline">
          <?=csrf_field()?><input type="hidden" name="action" value="toggle_published"><input type="hidden" name="id" value="<?=$p['id']?>">
          <button type="submit" class="a-pill <?=$p['published']?'a-pill-live':'a-pill-draft'?>" style="border:none;cursor:pointer"><?=$p['published']?'Live':'Concept'?></button>
        </form>
      </td>
      <td data-label="In menu">
        <form method="post" style="display:inline">
          <?=csrf_field()?><input type="hidden" name="action" value="toggle_nav"><input type="hidden" name="id" value="<?=$p['id']?>">
          <button type="submit" class="a-pill <?=$p['show_in_nav']?'a-pill-live':'a-pill-draft'?>" style="border:none;cursor:pointer"><?=$p['show_in_nav']?'Ja':'Nee'?></button>
        </form>
      </td>
      <td data-label="Bijgewerkt"><?=e(date('d-m-Y H:i',strtotime($p['updated_at'])))?></td>
      <td class="row-actions">
        <?php if($p['published']): ?><a class="a-btn a-btn-sm a-btn-ghost" href="../page.php?slug=<?=e($p['slug'])?>" target="_blank">Bekijk</a><?php endif; ?>
        <a class="a-btn a-btn-sm" href="page-edit.php?id=<?=$p['id']?>">Bewerken</a>
        <form method="post" onsubmit="return confirm('Pagina \'<?=e(addslashes($p['title']))?>\' definitief verwijderen?');" style="display:inline">
          <?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$p['id']?>">
          <button type="submit" class="a-btn a-btn-sm a-btn-danger">Verwijder</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table></div>
<?php else: ?>
  <div class="a-empty"><h3>Nog geen pagina's</h3><p>Maak hierboven je eerste pagina aan.</p></div>
<?php endif; ?>
</div>
<?php admin_footer(); ?>
