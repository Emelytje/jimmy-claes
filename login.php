<?php require 'functions.php'; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_verify();
    $st=db()->prepare('SELECT * FROM users WHERE username=?'); $st->execute([$_POST['username']]); $u=$st->fetch();
    if($u && password_verify($_POST['password'],$u['password_hash'])){ session_regenerate_id(true); $_SESSION['admin_id']=$u['id']; header('Location: admin/index.php'); exit; }
    $err=t('login_wrong');
}
?>
<!doctype html><html lang="<?=e(current_lang())?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login</title><link rel="stylesheet" href="assets/style.css"></head><body><main class="login"><form class="box" method="post"><h1><?=e(t('login_title'))?></h1><?php if(isset($_GET['installed'])) echo '<div class="notice">'.e(t('install_done')).'</div>'; if($err) echo '<div class="notice">'.e($err).'</div>'; echo csrf_field(); ?><input name="username" placeholder="<?=e(t('login_username'))?>" required><input name="password" type="password" placeholder="<?=e(t('login_password'))?>" required><button><?=e(t('login_button'))?></button></form><div style="text-align:center;margin-top:16px"><?=lang_switch_html()?></div></main></body></html>
