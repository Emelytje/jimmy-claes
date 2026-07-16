<?php
require __DIR__.'/inc.php';

$fields = ['site_title','intro_title','intro_text','primary_color','accent_color','font','meta_description','contact_email'];
$classColorMap = pb_class_color_map();
$classColorLabels = [
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
            $accountError = 'Huidig wachtwoord is onjuist.';
        } elseif($newUsername === ''){
            $accountError = 'Gebruikersnaam mag niet leeg zijn.';
        } elseif($newPassword !== '' && strlen($newPassword) < 8){
            $accountError = 'Nieuw wachtwoord moet minstens 8 tekens zijn.';
        } elseif($newPassword !== $newPassword2){
            $accountError = 'Nieuw wachtwoord komt niet overeen met de bevestiging.';
        } else {
            $chk = db()->prepare('SELECT id FROM users WHERE username=? AND id<>?');
            $chk->execute([$newUsername, $me['id']]);
            if($chk->fetch()){
                $accountError = 'Die gebruikersnaam is al in gebruik.';
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

$currentUser = null;
if(!empty($_SESSION['admin_id'])){
    $st = db()->prepare('SELECT username FROM users WHERE id=?');
    $st->execute([$_SESSION['admin_id']]);
    $currentUser = $st->fetch();
}

admin_header('Site-instellingen', 'settings');
?>
<?php if($saved): ?><div class="notice" style="margin-bottom:20px">Instellingen opgeslagen.</div><?php endif; ?>
<div class="a-card">
  <div class="a-card-pad">
    <form method="post">
      <?=csrf_field()?>
      <input type="hidden" name="action" value="site">

      <div class="a-field"><label>Sitenaam</label><input type="text" name="site_title" value="<?=e($values['site_title'])?>"></div>
      <div class="a-field"><label>Introtitel (homepage)</label><input type="text" name="intro_title" value="<?=e($values['intro_title'])?>"></div>
      <div class="a-field"><label>Introtekst (homepage)</label><textarea name="intro_text" rows="3"><?=e($values['intro_text'])?></textarea></div>
      <div class="a-field"><label>SEO-omschrijving (standaard)</label><textarea name="meta_description" rows="2"><?=e($values['meta_description'])?></textarea></div>
      <div class="a-field"><label>Contact-e-mailadres</label><input type="email" name="contact_email" value="<?=e($values['contact_email'])?>" placeholder="jij@voorbeeld.nl"><p style="font-size:.78rem;color:#8a7c6c;margin-top:4px">Berichten via het contactformulier worden hierheen gemaild.</p></div>

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

      <h2 style="margin-top:28px">Bannerkleuren per dierklasse</h2>
      <p style="color:#8a7c6c;font-size:.9rem;margin-top:-8px">De achtergrondkleur boven de titel op categorie- en dierenpagina's, per klasse (geldt ook voor alle sub-categorieën eronder).</p>
      <div class="pbe-row" style="display:flex;flex-wrap:wrap;gap:20px">
        <?php foreach($classColorMap as $key => [$settingKey, $default]): ?>
        <div class="a-field" style="flex:1;min-width:160px">
          <label><?=e($classColorLabels[$key] ?? $key)?></label>
          <input type="color" name="<?=e($settingKey)?>" value="<?=e(setting($settingKey, $default))?>" style="height:44px;width:100%;border:1.5px solid var(--a-line);border-radius:8px;padding:2px;cursor:pointer">
        </div>
        <?php endforeach; ?>
      </div>

      <button class="a-btn" type="submit" style="margin-top:20px">Opslaan</button>
    </form>
  </div>
</div>

<div class="a-card">
  <div class="a-card-pad">
    <h2 style="margin-top:0">Inloggegevens</h2>
    <?php if($accountSaved): ?><div class="notice" style="margin-bottom:16px">Inloggegevens bijgewerkt.</div><?php endif; ?>
    <?php if($accountError): ?><div class="notice" style="margin-bottom:16px"><?=e($accountError)?></div><?php endif; ?>
    <form method="post">
      <?=csrf_field()?>
      <input type="hidden" name="action" value="account">

      <div class="a-field"><label>Gebruikersnaam</label><input type="text" name="username" value="<?=e($currentUser['username'] ?? '')?>" required></div>
      <div class="a-field"><label>Huidig wachtwoord</label><input type="password" name="current_password" placeholder="Vereist om te bevestigen" required></div>
      <div class="a-field"><label>Nieuw wachtwoord</label><input type="password" name="new_password" placeholder="Laat leeg om wachtwoord niet te wijzigen"></div>
      <div class="a-field"><label>Nieuw wachtwoord (bevestig)</label><input type="password" name="new_password_confirm" placeholder="Herhaal nieuw wachtwoord"></div>

      <button class="a-btn" type="submit">Inloggegevens opslaan</button>
    </form>
  </div>
</div>
<?php admin_footer(); ?>
