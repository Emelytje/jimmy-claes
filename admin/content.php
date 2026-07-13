<?php
require __DIR__.'/inc.php';

const CONTENT_TYPES = [
    'animal'   => ['table'=>'animals',    'label'=>'Dieren',      'singular'=>'dier',       'view'=>'../animal.php?slug=',   'nav'=>'animals'],
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

$q = trim($_GET['q'] ?? '');
$klasse = (int)($_GET['klasse'] ?? 0);

if($type === 'category'){
    $items = pbe_category_tree_flat();
    if($q !== ''){
        $items = array_values(array_filter($items, function($p) use ($q){ return stripos($p['title'], $q) !== false; }));
    }
} else {
    $sql = "SELECT * FROM $table WHERE 1=1";
    $params = [];
    if($q !== ''){ $sql .= " AND title LIKE ?"; $params[] = '%'.$q.'%'; }
    if($klasse){
        $ids = pb_category_descendant_ids($klasse);
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $sql .= " AND category_id IN ($ph)";
        $params = array_merge($params, $ids);
    }
    $sql .= " ORDER BY created_at DESC";
    $st = db()->prepare($sql);
    $st->execute($params);
    $items = $st->fetchAll();
}

// Hoofdcategorieën ("klassen") voor de filter — enkel zinvol bij dieren,
// zodat je bv. enkel amfibieën of enkel zoogdieren kan tonen.
$klassen = [];
if($type === 'animal'){
    try{ $klassen = db()->query('SELECT id, title FROM categories WHERE parent_id IS NULL ORDER BY sort_order, title')->fetchAll(); }
    catch(Exception $e){ $klassen = []; }
}

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
  <div class="a-card-pad">
    <form method="get" class="a-inline-form">
      <input type="hidden" name="type" value="<?=e($type)?>">
      <div class="a-field"><label>Zoeken op naam</label><input type="text" name="q" placeholder="Bv. hoornkikker" value="<?=e($q)?>"></div>
      <?php if($type === 'animal' && $klassen): ?>
      <div class="a-field">
        <label>Klasse</label>
        <select name="klasse">
          <option value="">Alle klassen</option>
          <?php foreach($klassen as $k): ?>
          <option value="<?=(int)$k['id']?>" <?=$klasse===(int)$k['id']?'selected':''?>><?=e($k['title'])?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <button class="a-btn" type="submit">Filteren</button>
      <?php if($q !== '' || $klasse): ?><a class="a-btn a-btn-ghost" href="content.php?type=<?=e($type)?>">Wis filter</a><?php endif; ?>
    </form>
  </div>
</div>

<div class="a-card">
<?php if($q !== '' || $klasse): ?>
  <div class="a-card-pad" style="padding-bottom:0;color:var(--ink-soft)"><?=count($items)?> resultaat/resultaten gevonden.</div>
<?php endif; ?>
<?php if($items): ?>
  <table class="a-table">
    <tr><th>Titel</th><th>Link</th><th>Status</th><th>Bezoeken</th><th>Aangemaakt</th><th></th></tr>
    <?php foreach($items as $p): ?>
    <tr>
      <td data-label="Titel"><strong><?=$type==='category' ? str_repeat('&mdash; ', $p['depth']) : ''?><?=e($p['title'])?></strong></td>
      <td data-label="Link"><code><?=e($info['view'].$p['slug'])?></code></td>
      <td data-label="Status">
        <form method="post" style="display:inline">
          <?=csrf_field()?><input type="hidden" name="action" value="toggle_published"><input type="hidden" name="id" value="<?=$p['id']?>">
          <button type="submit" class="a-pill <?=$p['published']?'a-pill-live':'a-pill-draft'?>" style="border:none;cursor:pointer"><?=$p['published']?'Live':'Concept'?></button>
        </form>
      </td>
      <td data-label="Bezoeken"><?=(int)($p['views']??0)?></td>
      <td data-label="Aangemaakt"><?=e(date('d-m-Y H:i',strtotime($p['created_at'])))?></td>
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
