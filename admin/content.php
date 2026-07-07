<?php
require __DIR__.'/inc.php';

const CONTENT_TYPES = [
    'animal'   => ['table'=>'animals',    'label'=>'Dieren',      'singular'=>'dier',       'view'=>'../animal.php?slug=',   'nav'=>'animals'],
    'album'    => ['table'=>'albums',     'label'=>'Albums',      'singular'=>'album',      'view'=>'../album.php?slug=',    'nav'=>'albums'],
    'post'     => ['table'=>'posts',      'label'=>'Blog',        'singular'=>'blogpost',   'view'=>'../post.php?slug=',     'nav'=>'posts'],
    'category' => ['table'=>'categories', 'label'=>'Categorieën', 'singular'=>'categorie',  'view'=>'../category.php?slug=', 'nav'=>'categories'],
];

$type = $_GET['type'] ?? '';
if(!isset(CONTENT_TYPES[$type])){ header('Location: index.php'); exit; }
$info = CONTENT_TYPES[$type];
$table = $info['table'];

if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if($action==='create'){
        $title = trim($_POST['title'] ?? '');
        if($title!==''){
            $slug = slugify($title);
            $base = $slug; $i = 2;
            $chk = db()->prepare("SELECT COUNT(*) c FROM $table WHERE slug=?");
            while(true){ $chk->execute([$slug]); if((int)$chk->fetch()['c']===0) break; $slug = $base.'-'.$i; $i++; }
            if($type === 'category'){
                $parent_id = (int)($_POST['parent_id'] ?? 0) ?: null;
                $st = db()->prepare('INSERT INTO categories(title,slug,parent_id,blocks,published) VALUES(?,?,?,?,0)');
                $st->execute([$title, $slug, $parent_id, '[]']);
            } else {
                $st = db()->prepare("INSERT INTO $table(title,slug,blocks,published) VALUES(?,?,?,0)");
                $st->execute([$title, $slug, '[]']);
            }
            header('Location: page-edit.php?type='.$type.'&id='.db()->lastInsertId()); exit;
        }
    } elseif($action==='delete'){
        $id = (int)($_POST['id'] ?? 0);
        if($type === 'category' && $id){
            $st = db()->prepare('SELECT parent_id FROM categories WHERE id=?');
            $st->execute([$id]);
            $parentOfDeleted = $st->fetch()['parent_id'] ?? null;
            db()->prepare('UPDATE categories SET parent_id=? WHERE parent_id=?')->execute([$parentOfDeleted, $id]);
            db()->prepare('UPDATE animals SET category_id=NULL WHERE category_id=?')->execute([$id]);
        }
        db()->prepare("DELETE FROM $table WHERE id=?")->execute([$id]);
    } elseif($action==='toggle_published'){
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare("UPDATE $table SET published = 1-published WHERE id=?")->execute([$id]);
    }
    header('Location: content.php?type='.$type); exit;
}

$items = $type === 'category' ? pbe_category_tree_flat() : db()->query("SELECT * FROM $table ORDER BY created_at DESC")->fetchAll();
admin_header($info['label'], $info['nav']);
?>
<div class="a-card">
  <div class="a-card-pad">
    <form method="post" class="a-inline-form">
      <?=csrf_field()?>
      <input type="hidden" name="action" value="create">
      <div class="a-field"><label>Titel van het nieuwe <?=e($info['singular'])?></label><input type="text" name="title" placeholder="Titel" required></div>
      <?php if($type === 'category'): ?>
      <div class="a-field">
        <label>Bovenliggende categorie</label>
        <select name="parent_id">
          <option value="">Geen (hoofdcategorie)</option>
          <?=pbe_category_options(null)?>
        </select>
      </div>
      <?php endif; ?>
      <button class="a-btn" type="submit">+ Aanmaken</button>
    </form>
  </div>
</div>

<div class="a-card">
<?php if($items): ?>
  <table class="a-table">
    <tr><th>Titel</th><th>Link</th><th>Status</th><th>Bezoeken</th><th>Aangemaakt</th><th></th></tr>
    <?php foreach($items as $p): ?>
    <tr>
      <td><strong><?=$type==='category' ? str_repeat('&mdash; ', $p['depth']) : ''?><?=e($p['title'])?></strong></td>
      <td><code><?=e($info['view'].$p['slug'])?></code></td>
      <td>
        <form method="post" style="display:inline">
          <?=csrf_field()?><input type="hidden" name="action" value="toggle_published"><input type="hidden" name="id" value="<?=$p['id']?>">
          <button type="submit" class="a-pill <?=$p['published']?'a-pill-live':'a-pill-draft'?>" style="border:none;cursor:pointer"><?=$p['published']?'Live':'Concept'?></button>
        </form>
      </td>
      <td><?=(int)($p['views']??0)?></td>
      <td><?=e(date('d-m-Y H:i',strtotime($p['created_at'])))?></td>
      <td class="row-actions">
        <?php if($p['published']): ?><a class="a-btn a-btn-sm a-btn-ghost" href="<?=e($info['view'].$p['slug'])?>" target="_blank">Bekijk</a><?php endif; ?>
        <a class="a-btn a-btn-sm" href="page-edit.php?type=<?=e($type)?>&id=<?=$p['id']?>">Bewerken</a>
        <form method="post" onsubmit="return confirm('<?=e(ucfirst($info['singular']))?> \'<?=e(addslashes($p['title']))?>\' definitief verwijderen?');" style="display:inline">
          <?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$p['id']?>">
          <button type="submit" class="a-btn a-btn-sm a-btn-danger">Verwijder</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
<?php else: ?>
  <div class="a-empty"><h3>Nog geen <?=e(strtolower($info['label']))?></h3><p>Maak hierboven de eerste aan.</p></div>
<?php endif; ?>
</div>
<?php admin_footer(); ?>
