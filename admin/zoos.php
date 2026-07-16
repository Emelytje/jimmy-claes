<?php
require __DIR__.'/inc.php';

db()->exec("CREATE TABLE IF NOT EXISTS zoos(id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(160) NOT NULL, url VARCHAR(500) NOT NULL, sort_order INT DEFAULT 0, published TINYINT DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function zoos_normalize_url($url){
    $url = trim($url);
    if($url !== '' && !preg_match('~^https?://~i', $url)) $url = 'https://'.$url;
    return $url;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if($action==='create'){
        $title = trim($_POST['title'] ?? '');
        $url = zoos_normalize_url($_POST['url'] ?? '');
        if($title!=='' && $url!==''){
            $st = db()->prepare('INSERT INTO zoos(title,url) VALUES(?,?)');
            $st->execute([$title, $url]);
        }
    } elseif($action==='update'){
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $url = zoos_normalize_url($_POST['url'] ?? '');
        if($id && $title!=='' && $url!==''){
            $st = db()->prepare('UPDATE zoos SET title=?, url=? WHERE id=?');
            $st->execute([$title, $url, $id]);
        }
    } elseif($action==='delete'){
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM zoos WHERE id=?')->execute([$id]);
    } elseif($action==='toggle_published'){
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare('UPDATE zoos SET published = 1-published WHERE id=?')->execute([$id]);
    } elseif($action==='move_up' || $action==='move_down'){
        $id = (int)($_POST['id'] ?? 0);
        $all = db()->query('SELECT id, sort_order FROM zoos ORDER BY sort_order, id')->fetchAll();
        $idx = null;
        foreach($all as $i=>$row){ if((int)$row['id']===$id){ $idx = $i; break; } }
        if($idx !== null){
            $swapIdx = $action==='move_up' ? $idx-1 : $idx+1;
            if($swapIdx >= 0 && $swapIdx < count($all)){
                $a = $all[$idx]; $b = $all[$swapIdx];
                $soA = (int)$a['sort_order']; $soB = (int)$b['sort_order'];
                if($soA === $soB){ $soA = $idx; $soB = $swapIdx; }
                db()->prepare('UPDATE zoos SET sort_order=? WHERE id=?')->execute([$soB, $a['id']]);
                db()->prepare('UPDATE zoos SET sort_order=? WHERE id=?')->execute([$soA, $b['id']]);
            }
        }
    }
    header('Location: zoos.php'); exit;
}

$zoos = db()->query('SELECT * FROM zoos ORDER BY sort_order, id')->fetchAll();
admin_header(t('admin_zoos'), 'zoos');
?>
<div class="a-card">
  <div class="a-card-pad">
    <h2 style="margin-top:0">Dierentuinen in de hoofdnavigatie</h2>
    <p style="color:#8a7c6c;font-size:.9rem">Deze links vervangen de dierenklassen bovenaan de site. De klassen (Amfibieën, Ongewervelde, enz.) zijn nu bereikbaar via de knoppen op de homepage.</p>
    <form method="post" class="a-inline-form">
      <?=csrf_field()?>
      <input type="hidden" name="action" value="create">
      <div class="a-field"><label>Naam</label><input type="text" name="title" placeholder="Bijv. Zoo Antwerpen" required></div>
      <div class="a-field"><label>Website (URL)</label><input type="text" name="url" placeholder="https://www.zooantwerpen.be" required></div>
      <button class="a-btn" type="submit">+ Toevoegen</button>
    </form>
  </div>
</div>

<div class="a-card">
<?php if($zoos): ?>
  <div class="a-table-wrap"><table class="a-table">
    <tr><th colspan="2">Naam / URL</th><th>Status</th><th></th></tr>
    <?php foreach($zoos as $i=>$z): ?>
    <tr>
      <td data-label="Naam / URL" colspan="2">
        <form method="post" class="a-inline-form" style="gap:8px">
          <?=csrf_field()?>
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?=$z['id']?>">
          <div class="a-field"><input type="text" name="title" value="<?=e($z['title'])?>" required></div>
          <div class="a-field"><input type="text" name="url" value="<?=e($z['url'])?>" required></div>
          <button type="submit" class="a-btn a-btn-sm a-btn-ghost"><?=e(t('save'))?></button>
        </form>
      </td>
      <td data-label="Status">
        <form method="post" style="display:inline">
          <?=csrf_field()?><input type="hidden" name="action" value="toggle_published"><input type="hidden" name="id" value="<?=$z['id']?>">
          <button type="submit" class="a-pill <?=$z['published']?'a-pill-live':'a-pill-draft'?>" style="border:none;cursor:pointer"><?=$z['published']?t('live'):t('hidden')?></button>
        </form>
      </td>
      <td class="row-actions">
        <form method="post" style="display:inline">
          <?=csrf_field()?><input type="hidden" name="action" value="move_up"><input type="hidden" name="id" value="<?=$z['id']?>">
          <button type="submit" class="a-btn a-btn-sm a-btn-ghost" <?=$i===0?'disabled':''?>>&uarr;</button>
        </form>
        <form method="post" style="display:inline">
          <?=csrf_field()?><input type="hidden" name="action" value="move_down"><input type="hidden" name="id" value="<?=$z['id']?>">
          <button type="submit" class="a-btn a-btn-sm a-btn-ghost" <?=$i===count($zoos)-1?'disabled':''?>>&darr;</button>
        </form>
        <form method="post" onsubmit="return confirm('\'<?=e(addslashes($z['title']))?>\' verwijderen uit de navigatie?');" style="display:inline">
          <?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$z['id']?>">
          <button type="submit" class="a-btn a-btn-sm a-btn-danger"><?=e(t('delete'))?></button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table></div>
<?php else: ?>
  <div class="a-empty"><h3>Nog geen dierentuinen</h3><p>Voeg hierboven je eerste link toe, bijv. Zoo Antwerpen.</p></div>
<?php endif; ?>
</div>
<?php admin_footer(); ?>
