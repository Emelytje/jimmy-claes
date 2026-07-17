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
    } elseif($action==='update_title_en' && $type==='category'){
        $id = (int)($_POST['id'] ?? 0);
        $titleEn = trim($_POST['title_en'] ?? '');
        if(!pb_has_column('categories','title_en')) db()->exec('ALTER TABLE categories ADD COLUMN title_en VARCHAR(160) DEFAULT NULL');
        if($id) db()->prepare('UPDATE categories SET title_en=? WHERE id=?')->execute([$titleEn !== '' ? $titleEn : null, $id]);
    } elseif($action==='update_drive_url' && $type==='animal'){
        $id = (int)($_POST['id'] ?? 0);
        $driveUrl = trim($_POST['drive_url'] ?? '');
        if($driveUrl !== '' && !preg_match('~^https?://~i', $driveUrl)) $driveUrl = 'https://'.$driveUrl;
        if(!pb_has_column('animals','drive_url')) db()->exec('ALTER TABLE animals ADD COLUMN drive_url VARCHAR(500) DEFAULT NULL');
        if($id) db()->prepare('UPDATE animals SET drive_url=? WHERE id=?')->execute([$driveUrl !== '' ? $driveUrl : null, $id]);
    }
    header('Location: content.php?type='.$type); exit;
}

$q = trim($_GET['q'] ?? '');
$klasse = (int)($_GET['klasse'] ?? 0);
$photoFilter = $_GET['photos'] ?? '';
if(!in_array($photoFilter, ['with','without'], true)) $photoFilter = '';

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

    // Categorienaam per dier ophalen zodat gelijknamige soorten (bv.
    // Lathamus discolor, bewust twee keer in de boom) in de lijst uit elkaar
    // te houden zijn.
    if($type === 'animal' && $items){
        $catEnCol = pb_has_column('categories','title_en') ? ', title_en' : '';
        $catNames = [];
        foreach(db()->query("SELECT id, title$catEnCol FROM categories") as $c){ $catNames[(int)$c['id']] = localized_field($c, 'title'); }
        foreach($items as &$it){ $it['category_title'] = $it['category_id'] ? ($catNames[(int)$it['category_id']] ?? '') : ''; }
        unset($it);

        // Foto-aantal per dier: telt zowel de oude photos-tabel als foto's
        // die in blokken zitten (image/gallery/slideshow), zodat "geen
        // foto's"-filter ook klopt voor dieren die via de pagebuilder
        // bewerkt zijn in plaats van de oude manier.
        $ids = array_column($items, 'id');
        $legacyCounts = [];
        if($ids){
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $st = db()->prepare("SELECT animal_id, COUNT(*) c FROM photos WHERE animal_id IN ($ph) GROUP BY animal_id");
            $st->execute($ids);
            foreach($st as $row) $legacyCounts[(int)$row['animal_id']] = (int)$row['c'];
        }
        foreach($items as &$it){
            $blocksCount = pb_count_blocks_images(pb_decode_blocks($it['blocks'] ?? null));
            $it['photo_count'] = ($legacyCounts[(int)$it['id']] ?? 0) + $blocksCount;
        }
        unset($it);

        if($photoFilter === 'without') $items = array_values(array_filter($items, function($it){ return $it['photo_count'] === 0; }));
        elseif($photoFilter === 'with') $items = array_values(array_filter($items, function($it){ return $it['photo_count'] > 0; }));
    }
}

// Hoofdcategorieën ("klassen") voor de filter — enkel zinvol bij dieren,
// zodat je bv. enkel amfibieën of enkel zoogdieren kan tonen.
$klassen = [];
if($type === 'animal'){
    try{ $klassen = db()->query('SELECT id, title FROM categories WHERE parent_id IS NULL ORDER BY sort_order, title')->fetchAll(); }
    catch(Exception $e){ $klassen = []; }
}

$typeLabel = $type === 'animal' ? t('admin_animals') : t('admin_categories');
admin_header($typeLabel, $info['nav']);
?>
<div class="a-card">
  <div class="a-card-pad">
    <form method="post" class="a-inline-form">
      <?=csrf_field()?>
      <input type="hidden" name="action" value="create">
      <div class="a-field"><label><?=e($type === 'animal' ? t('new_animal_title_label') : t('new_category_title_label'))?></label><input type="text" name="title" placeholder="<?=e(t('title_placeholder'))?>" required></div>
      <?php if($type === 'category'): ?>
      <div class="a-field">
        <label><?=e(t('parent_category'))?></label>
        <select name="parent_id">
          <option value=""><?=e(t('none_top_level'))?></option>
          <?=pbe_category_options(null)?>
        </select>
      </div>
      <?php endif; ?>
      <button class="a-btn" type="submit"><?=e(t('create_btn'))?></button>
    </form>
  </div>
</div>

<div class="a-card">
  <div class="a-card-pad">
    <form method="get" class="a-inline-form">
      <input type="hidden" name="type" value="<?=e($type)?>">
      <div class="a-field"><label><?=e(t('search_by_name'))?></label><input type="text" name="q" placeholder="Bv. hoornkikker" value="<?=e($q)?>"></div>
      <?php if($type === 'animal' && $klassen): ?>
      <div class="a-field">
        <label><?=e(t('class_label'))?></label>
        <select name="klasse">
          <option value=""><?=e(t('all_classes'))?></option>
          <?php foreach($klassen as $k): ?>
          <option value="<?=(int)$k['id']?>" <?=$klasse===(int)$k['id']?'selected':''?>><?=e($k['title'])?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <?php if($type === 'animal'): ?>
      <div class="a-field">
        <label><?=e(t('photos_label'))?></label>
        <select name="photos">
          <option value=""><?=e(t('all_photo_states'))?></option>
          <option value="with" <?=$photoFilter==='with'?'selected':''?>><?=e(t('with_photos_filter_label'))?></option>
          <option value="without" <?=$photoFilter==='without'?'selected':''?>><?=e(t('no_photos_filter_label'))?></option>
        </select>
      </div>
      <?php endif; ?>
      <button class="a-btn" type="submit"><?=e(t('filter'))?></button>
      <?php if($q !== '' || $klasse || $photoFilter !== ''): ?><a class="a-btn a-btn-ghost" href="content.php?type=<?=e($type)?>"><?=e(t('clear_filter'))?></a><?php endif; ?>
      <?php if($type === 'animal'): ?><a class="a-btn a-btn-ghost" href="bulk-drive-links.php" style="margin-left:auto"><?=e(t('bulk_drive_links_btn'))?></a><?php endif; ?>
    </form>
  </div>
</div>

<div class="a-card">
<?php if($q !== '' || $klasse): ?>
  <div class="a-card-pad" style="padding-bottom:0;color:var(--ink-soft)"><?=count($items)?> resultaat/resultaten gevonden.</div>
<?php endif; ?>
<?php if($items): ?>
  <div class="a-table-wrap"><table class="a-table">
    <tr><th><?=e(t('title_label'))?></th><?php if($type==='animal'): ?><th><?=e(t('category_label'))?></th><th><?=e(t('photos_label'))?></th><th><?=e(t('drive_url_label_short'))?></th><?php endif; ?><?php if($type==='category'): ?><th><?=e(t('english_name'))?></th><?php endif; ?><th><?=e(t('link'))?></th><th><?=e(t('status'))?></th><th><?=e(t('visits'))?></th><th><?=e(t('created'))?></th><th></th></tr>
    <?php foreach($items as $p): ?>
    <tr>
      <td data-label="<?=e(t('title_label'))?>"><strong><?=$type==='category' ? str_repeat('&mdash; ', $p['depth']) : ''?><?=e($p['title'])?></strong></td>
      <?php if($type==='animal'): ?>
      <td data-label="<?=e(t('category_label'))?>"><?=$p['category_title'] !== '' ? e($p['category_title']) : '<span style="color:var(--ink-soft)">'.e(t('none_dash')).'</span>'?></td>
      <td data-label="<?=e(t('photos_label'))?>"><?php if($p['photo_count']===0): ?><span class="a-pill a-pill-draft"><?=e(t('no_photos_pill'))?></span><?php else: ?><?=(int)$p['photo_count']?><?php endif; ?></td>
      <td data-label="<?=e(t('drive_url_label_short'))?>">
        <form method="post" class="a-inline-form" style="gap:6px">
          <?=csrf_field()?><input type="hidden" name="action" value="update_drive_url"><input type="hidden" name="id" value="<?=$p['id']?>">
          <input type="text" name="drive_url" value="<?=e($p['drive_url'] ?? '')?>" placeholder="https://drive.google.com/..." style="min-width:160px">
          <button type="submit" class="a-btn a-btn-sm a-btn-ghost">✓</button>
        </form>
      </td>
      <?php endif; ?>
      <?php if($type==='category'): ?>
      <td data-label="<?=e(t('english_name'))?>">
        <form method="post" class="a-inline-form" style="gap:6px">
          <?=csrf_field()?><input type="hidden" name="action" value="update_title_en"><input type="hidden" name="id" value="<?=$p['id']?>">
          <input type="text" name="title_en" value="<?=e($p['title_en'] ?? '')?>" placeholder="bv. Fish" style="min-width:140px">
          <button type="submit" class="a-btn a-btn-sm a-btn-ghost">✓</button>
        </form>
      </td>
      <?php endif; ?>
      <td data-label="<?=e(t('link'))?>"><code><?=e($info['view'].$p['slug'])?></code></td>
      <td data-label="<?=e(t('status'))?>">
        <form method="post" style="display:inline">
          <?=csrf_field()?><input type="hidden" name="action" value="toggle_published"><input type="hidden" name="id" value="<?=$p['id']?>">
          <button type="submit" class="a-pill <?=$p['published']?'a-pill-live':'a-pill-draft'?>" style="border:none;cursor:pointer"><?=$p['published']?t('live'):t('draft')?></button>
        </form>
      </td>
      <td data-label="<?=e(t('visits'))?>"><?=(int)($p['views']??0)?></td>
      <td data-label="<?=e(t('created'))?>"><?=e(date('d-m-Y H:i',strtotime($p['created_at'])))?></td>
      <td class="row-actions">
        <?php if($p['published']): ?><a class="a-btn a-btn-sm a-btn-ghost" href="<?=e($info['view'].$p['slug'])?>" target="_blank"><?=e(t('view'))?></a><?php endif; ?>
        <a class="a-btn a-btn-sm" href="page-edit.php?type=<?=e($type)?>&id=<?=$p['id']?>"><?=e(t('edit'))?></a>
        <form method="post" onsubmit="return confirm('<?=e(ucfirst($info['singular']))?> \'<?=e(addslashes($p['title']))?><?=e(t('confirm_delete_content_suffix'))?>');" style="display:inline">
          <?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$p['id']?>">
          <button type="submit" class="a-btn a-btn-sm a-btn-danger"><?=e(t('delete'))?></button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table></div>
<?php else: ?>
  <div class="a-empty"><h3><?=e(t('no_items_yet_prefix'))?><?=e(mb_strtolower($typeLabel))?></h3><p><?=e(t('create_first_hint'))?></p></div>
<?php endif; ?>
</div>
<?php admin_footer(); ?>
