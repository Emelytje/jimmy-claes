<?php
require __DIR__.'/inc.php';

$fields = ['site_title','intro_title','intro_text','primary_color','accent_color','font','meta_description','contact_email'];
$classColorMap = pb_class_color_map();
$classColorLabels = current_lang() === 'en' ? [
    'homepage'      => 'Homepage',
    'gewervelde'    => 'Vertebrates (entry page)',
    'vissen'        => 'Fish',
    'vogels'        => 'Birds',
    'reptielen'     => 'Reptiles',
    'zoogdieren'    => 'Mammals',
    'amfibieën'     => 'Amphibians',
    'ongewervelde'  => 'Invertebrates',
    'spinachtigen'  => 'Arachnids',
    'schijfkwallen' => 'Moon jellyfish',
] : [
    'homepage'      => 'Homepage',
    'gewervelde'    => 'Gewervelde (ingangspagina)',
    'vissen'        => 'Vissen',
    'vogels'        => 'Vogels',
    'reptielen'     => 'Reptielen',
    'zoogdieren'    => 'Zoogdieren',
    'amfibieën'     => 'Amfibieën',
    'ongewervelde'  => 'Ongewervelde',
    'spinachtigen'  => 'Spinachtigen',
    'schijfkwallen' => 'Schijfkwallen',
];
$saved = false;
$accountSaved = false;
$accountError = '';

if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_verify();
    $action = $_POST['action'] ?? 'site';

    if($action === 'account'){
        $newUsername = trim($_POST['username'] ?? '');
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $newPassword2 = (string)($_POST['new_password_confirm'] ?? '');

        $st = db()->prepare('SELECT * FROM users WHERE id=?');
        $st->execute([$_SESSION['admin_id']]);
        $me = $st->fetch();

        if(!$me || !password_verify($currentPassword, $me['password_hash'])){
            $accountError = t('err_wrong_current_password');
        } elseif($newUsername === ''){
            $accountError = t('err_username_empty');
        } elseif($newPassword !== '' && strlen($newPassword) < 8){
            $accountError = t('err_password_too_short');
        } elseif($newPassword !== $newPassword2){
            $accountError = t('err_password_mismatch');
        } else {
            $chk = db()->prepare('SELECT id FROM users WHERE username=? AND id<>?');
            $chk->execute([$newUsername, $me['id']]);
            if($chk->fetch()){
                $accountError = t('err_username_taken');
            } else {
                if($newPassword !== ''){
                    db()->prepare('UPDATE users SET username=?, password_hash=? WHERE id=?')
                        ->execute([$newUsername, password_hash($newPassword, PASSWORD_DEFAULT), $me['id']]);
                } else {
                    db()->prepare('UPDATE users SET username=? WHERE id=?')->execute([$newUsername, $me['id']]);
                }
                $accountSaved = true;
            }
        }
    } else {
        foreach($fields as $f){
            $val = trim($_POST[$f] ?? '');
            if(($f==='primary_color' || $f==='accent_color') && $val!==''){
                if(!preg_match('/^#[0-9a-fA-F]{6}$/', $val)) $val = $f==='primary_color' ? '#7b5f46' : '#eadfd2';
            }
            if($f==='contact_email' && $val!=='' && !filter_var($val, FILTER_VALIDATE_EMAIL)){
                $val = setting('contact_email','');
            }
            set_setting($f, $val);
        }
        if(!empty($_POST['remove_logo'])){
            set_setting('site_logo', '');
        } elseif(!empty($_FILES['site_logo']['tmp_name'])){
            try{
                $path = upload_image($_FILES['site_logo']);
                if($path) set_setting('site_logo', $path);
            }catch(Exception $e){}
        }
        foreach($classColorMap as [$key, $default]){
            $val = trim($_POST[$key] ?? '');
            if(!preg_match('/^#[0-9a-fA-F]{6}$/', $val)) $val = $default;
            set_setting($key, $val);
        }
        $saved = true;
    }
}

$values = [];
foreach($fields as $f) $values[$f] = setting($f, '');
$currentLogo = setting('site_logo', '');

$currentUser = null;
if(!empty($_SESSION['admin_id'])){
    $st = db()->prepare('SELECT username FROM users WHERE id=?');
    $st->execute([$_SESSION['admin_id']]);
    $currentUser = $st->fetch();
}

admin_header(t('admin_settings'), 'settings');
?>
<?php if($saved): ?><div class="notice" style="margin-bottom:20px"><?=e(t('settings_saved'))?></div><?php endif; ?>
<div class="a-card">
  <div class="a-card-pad">
    <form method="post" enctype="multipart/form-data">
      <?=csrf_field()?>
      <input type="hidden" name="action" value="site">

      <div class="a-field">
        <label><?=e(t('site_logo_label'))?></label>
        <?php if($currentLogo): ?>
          <div style="margin-bottom:8px"><img src="../<?=e($currentLogo)?>" alt="" style="max-height:70px;max-width:220px;display:block"></div>
          <label class="pbe-check-inline" style="font-weight:normal"><input type="checkbox" name="remove_logo" value="1"> <?=e(t('remove_logo_label'))?></label>
        <?php endif; ?>
        <input type="file" name="site_logo" accept="image/png,image/jpeg,image/webp,image/gif">
        <p style="font-size:.78rem;color:#8a7c6c;margin-top:4px"><?=e(t('site_logo_hint'))?></p>
      </div>

      <div class="a-field"><label><?=e(t('site_name'))?></label><input type="text" name="site_title" value="<?=e($values['site_title'])?>"></div>
      <div class="a-field"><label><?=e(t('intro_title_label'))?></label><input type="text" name="intro_title" value="<?=e($values['intro_title'])?>"></div>
      <div class="a-field"><label><?=e(t('intro_text_label'))?></label><textarea name="intro_text" rows="3"><?=e($values['intro_text'])?></textarea></div>
      <div class="a-field"><label><?=e(t('seo_desc_label'))?></label><textarea name="meta_description" rows="2"><?=e($values['meta_description'])?></textarea></div>
      <div class="a-field"><label><?=e(t('contact_email_label'))?></label><input type="email" name="contact_email" value="<?=e($values['contact_email'])?>" placeholder="jij@voorbeeld.nl"><p style="font-size:.78rem;color:#8a7c6c;margin-top:4px"><?=e(t('contact_email_hint'))?></p></div>

      <div class="pbe-row" style="display:flex;gap:20px">
        <div class="a-field" style="flex:1"><label><?=e(t('primary_color'))?></label><input type="color" name="primary_color" value="<?=e($values['primary_color'] ?: '#7b5f46')?>" style="height:44px;width:100%;border:1.5px solid var(--a-line);border-radius:8px;padding:2px;cursor:pointer"></div>
        <div class="a-field" style="flex:1"><label><?=e(t('accent_color'))?></label><input type="color" name="accent_color" value="<?=e($values['accent_color'] ?: '#eadfd2')?>" style="height:44px;width:100%;border:1.5px solid var(--a-line);border-radius:8px;padding:2px;cursor:pointer"></div>
      </div>

      <div class="a-field">
        <label><?=e(t('heading_font'))?></label>
        <select name="font">
          <?php foreach([(current_lang()==='en'?'Georgia (default)':'Georgia (standaard)')=>'Georgia','Fraunces'=>'Fraunces','Playfair Display'=>'Playfair Display','Cormorant Garamond'=>'Cormorant Garamond','Lora'=>'Lora','Merriweather'=>'Merriweather'] as $label=>$val): ?>
          <option value="<?=e($val)?>" <?=$values['font']===$val || ($values['font']==='' && $val==='Georgia') ? 'selected':''?>><?=e($label)?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <h2 style="margin-top:28px"><?=e(t('class_banner_colors'))?></h2>
      <p style="color:#8a7c6c;font-size:.9rem;margin-top:-8px"><?=e(t('class_banner_colors_desc'))?></p>
      <div class="pbe-row" style="display:flex;flex-wrap:wrap;gap:20px">
        <?php foreach($classColorMap as $key => [$settingKey, $default]): ?>
        <div class="a-field" style="flex:1;min-width:160px">
          <label><?=e($classColorLabels[$key] ?? $key)?></label>
          <input type="color" name="<?=e($settingKey)?>" value="<?=e(setting($settingKey, $default) ?: '#ffffff')?>" style="height:44px;width:100%;border:1.5px solid var(--a-line);border-radius:8px;padding:2px;cursor:pointer">
        </div>
        <?php endforeach; ?>
      </div>

      <button class="a-btn" type="submit" style="margin-top:20px"><?=e(t('save'))?></button>
    </form>
  </div>
</div>

<div class="a-card">
  <div class="a-card-pad">
    <h2 style="margin-top:0"><?=e(t('login_credentials'))?></h2>
    <?php if($accountSaved): ?><div class="notice" style="margin-bottom:16px"><?=e(t('credentials_updated'))?></div><?php endif; ?>
    <?php if($accountError): ?><div class="notice" style="margin-bottom:16px"><?=e($accountError)?></div><?php endif; ?>
    <form method="post">
      <?=csrf_field()?>
      <input type="hidden" name="action" value="account">

      <div class="a-field"><label><?=e(t('login_username'))?></label><input type="text" name="username" value="<?=e($currentUser['username'] ?? '')?>" required></div>
      <div class="a-field"><label><?=e(t('current_password'))?></label><input type="password" name="current_password" placeholder="<?=e(t('current_password_hint'))?>" required></div>
      <div class="a-field"><label><?=e(t('new_password'))?></label><input type="password" name="new_password" placeholder="<?=e(t('new_password_hint'))?>"></div>
      <div class="a-field"><label><?=e(t('new_password_confirm'))?></label><input type="password" name="new_password_confirm" placeholder="<?=e(t('new_password_confirm_hint'))?>"></div>

      <button class="a-btn" type="submit"><?=e(t('save_credentials'))?></button>
    </form>
  </div>
</div>
<?php admin_footer(); ?>
