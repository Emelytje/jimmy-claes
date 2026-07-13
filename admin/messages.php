<?php
require __DIR__.'/inc.php';

if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_verify();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if($action==='delete'){
        db()->prepare('DELETE FROM messages WHERE id=?')->execute([$id]);
    } elseif($action==='toggle_read'){
        db()->prepare('UPDATE messages SET is_read = 1-is_read WHERE id=?')->execute([$id]);
    }
    header('Location: messages.php'); exit;
}

$items = db()->query('SELECT * FROM messages ORDER BY created_at DESC')->fetchAll();
admin_header('Berichten', 'messages');
?>
<p style="font-size:.85rem;color:#8a7c6c;margin-top:-10px">Berichten via het contactformulier komen hier altijd terecht, ook als de e-mailmelding (indien ingesteld bij Site-instellingen) niet aankomt.</p>
<div class="a-card">
<?php if($items): ?>
  <div class="a-table-wrap"><table class="a-table">
    <tr><th>Naam</th><th>E-mail</th><th>Bericht</th><th>Datum</th><th>Status</th><th></th></tr>
    <?php foreach($items as $m): ?>
    <tr>
      <td data-label="Naam"><strong><?=e($m['name'])?></strong></td>
      <td data-label="E-mail"><?=$m['email'] ? '<a href="mailto:'.e($m['email']).'">'.e($m['email']).'</a>' : '<span style="color:#8a7c6c">(niet opgegeven)</span>'?></td>
      <td data-label="Bericht" style="max-width:340px;white-space:normal"><?=nl2br(e($m['message']))?></td>
      <td data-label="Datum"><?=e(date('d-m-Y H:i',strtotime($m['created_at'])))?></td>
      <td data-label="Status">
        <form method="post" style="display:inline">
          <?=csrf_field()?><input type="hidden" name="action" value="toggle_read"><input type="hidden" name="id" value="<?=$m['id']?>">
          <button type="submit" class="a-pill <?=$m['is_read']?'a-pill-live':'a-pill-draft'?>" style="border:none;cursor:pointer"><?=$m['is_read']?'Gelezen':'Nieuw'?></button>
        </form>
      </td>
      <td class="row-actions">
        <form method="post" onsubmit="return confirm('Bericht van \'<?=e(addslashes($m['name']))?>\' definitief verwijderen?');" style="display:inline">
          <?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$m['id']?>">
          <button type="submit" class="a-btn a-btn-sm a-btn-danger">Verwijder</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table></div>
<?php else: ?>
  <div class="a-empty"><h3>Nog geen berichten</h3><p>Berichten via het contactformulier verschijnen hier.</p></div>
<?php endif; ?>
</div>
<?php admin_footer(); ?>
