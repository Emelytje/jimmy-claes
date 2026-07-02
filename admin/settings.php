<?php
require __DIR__.'/inc.php';

$fields = ['site_title','intro_title','intro_text','primary_color','accent_color','font','meta_description'];
$saved = false;

if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_verify();
    foreach($fields as $f){
        $val = trim($_POST[$f] ?? '');
        if(($f==='primary_color' || $f==='accent_color') && $val!==''){
            if(!preg_match('/^#[0-9a-fA-F]{6}$/', $val)) $val = $f==='primary_color' ? '#7b5f46' : '#eadfd2';
        }
        set_setting($f, $val);
    }
    $saved = true;
}

$values = [];
foreach($fields as $f) $values[$f] = setting($f, '');

admin_header('Site-instellingen', 'settings');
?>
<?php if($saved): ?><div class="notice" style="margin-bottom:20px">Instellingen opgeslagen.</div><?php endif; ?>
<div class="a-card">
  <div class="a-card-pad">
    <form method="post">
      <?=csrf_field()?>

      <div class="a-field"><label>Sitenaam</label><input type="text" name="site_title" value="<?=e($values['site_title'])?>"></div>
      <div class="a-field"><label>Introtitel (homepage)</label><input type="text" name="intro_title" value="<?=e($values['intro_title'])?>"></div>
      <div class="a-field"><label>Introtekst (homepage)</label><textarea name="intro_text" rows="3"><?=e($values['intro_text'])?></textarea></div>
      <div class="a-field"><label>SEO-omschrijving (standaard)</label><textarea name="meta_description" rows="2"><?=e($values['meta_description'])?></textarea></div>

      <div class="pbe-row" style="display:flex;gap:20px">
        <div class="a-field" style="flex:1"><label>Hoofdkleur</label><input type="color" name="primary_color" value="<?=e($values['primary_color'] ?: '#7b5f46')?>" style="height:44px;width:100%;border:1.5px solid var(--a-line);border-radius:8px;padding:2px;cursor:pointer"></div>
        <div class="a-field" style="flex:1"><label>Accentkleur</label><input type="color" name="accent_color" value="<?=e($values['accent_color'] ?: '#eadfd2')?>" style="height:44px;width:100%;border:1.5px solid var(--a-line);border-radius:8px;padding:2px;cursor:pointer"></div>
      </div>

      <div class="a-field">
        <label>Lettertype (koppen)</label>
        <select name="font">
          <?php foreach(['Georgia (standaard)'=>'Georgia','Fraunces'=>'Fraunces','Playfair Display'=>'Playfair Display','Cormorant Garamond'=>'Cormorant Garamond','Lora'=>'Lora','Merriweather'=>'Merriweather'] as $label=>$val): ?>
          <option value="<?=e($val)?>" <?=$values['font']===$val || ($values['font']==='' && $val==='Georgia') ? 'selected':''?>><?=e($label)?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <button class="a-btn" type="submit">Opslaan</button>
    </form>
  </div>
</div>
<?php admin_footer(); ?>
