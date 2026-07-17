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
admin_header(t('admin_messages'), 'messages');
?>
<p style="font-size:.85rem;color:#8a7c6c;margin-top:-10px"><?=e(t('messages_desc'))?></p>
<div class="a-card">
<?php if($items): ?>
  <div class="a-table-wrap"><table class="a-table">
    <tr><th><?=e(t('name_label'))?></th><th><?=e(t('email_label'))?></th><th><?=e(t('message_label'))?></th><th><?=e(t('date_label'))?></th><th><?=e(t('status'))?></th><th></th></tr>
    <?php foreach($items as $m): ?>
    <tr>
      <td data-label="<?=e(t('name_label'))?>"><strong><?=e($m['name'])?></strong></td>
      <td data-label="<?=e(t('email_label'))?>"><?=$m['email'] ? '<a href="mailto:'.e($m['email']).'">'.e($m['email']).'</a>' : '<span style="color:#8a7c6c">'.e(t('not_provided')).'</span>'?></td>
      <td data-label="<?=e(t('message_label'))?>" style="max-width:340px;white-space:normal"><?=nl2br(e($m['message']))?></td>
      <td data-label="<?=e(t('date_label'))?>"><?=e(date('d-m-Y H:i',strtotime($m['created_at'])))?></td>
      <td data-label="<?=e(t('status'))?>">
        <form method="post" style="display:inline">
          <?=csrf_field()?><input type="hidden" name="action" value="toggle_read"><input type="hidden" name="id" value="<?=$m['id']?>">
          <button type="submit" class="a-pill <?=$m['is_read']?'a-pill-live':'a-pill-draft'?>" style="border:none;cursor:pointer"><?=$m['is_read']?t('read_label'):t('new_label')?></button>
        </form>
      </td>
      <td class="row-actions">
        <form method="post" onsubmit="return confirm('<?=e(t('confirm_delete_message_prefix'))?><?=e(addslashes($m['name']))?><?=e(t('confirm_delete_message_suffix'))?>');" style="display:inline">
          <?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$m['id']?>">
          <button type="submit" class="a-btn a-btn-sm a-btn-danger"><?=e(t('delete'))?></button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table></div>
<?php else: ?>
  <div class="a-empty"><h3><?=e(t('no_messages_yet'))?></h3><p><?=e(t('messages_appear_here'))?></p></div>
<?php endif; ?>
</div>
<?php admin_footer(); ?>
