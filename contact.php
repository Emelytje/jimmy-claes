<?php require 'functions.php';
$sent=false; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_verify();
    $name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $message=trim($_POST['message']??'');
    if($name==='' || $message===''){
        $err=t('fill_required');
    } elseif($email!=='' && !filter_var($email, FILTER_VALIDATE_EMAIL)){
        $err=t('fill_valid_email');
    } else {
        $st=db()->prepare('INSERT INTO messages(name,email,message) VALUES(?,?,?)');
        $st->execute([$name,$email,$message]);
        notify_contact_message($name, $email, $message);
        $sent=true;
    }
}
header_html(t('nav_contact'), 'Neem contact op voor vragen of een fotoshoot.');
?>
<main class="wrap"><h1><?=e(t('nav_contact'))?></h1>
<?php if($sent): ?>
<div class="notice"><?=e(t('thanks_message'))?></div>
<?php else: ?>
<?php if($err) echo '<div class="notice">'.e($err).'</div>'; ?>
<form method="post" class="box" style="max-width:600px">
<?=csrf_field()?>
<label><?=e(t('form_name'))?></label><input name="name" value="<?=e($_POST['name']??'')?>" required>
<label><?=e(t('form_email_optional'))?></label><input type="email" name="email" value="<?=e($_POST['email']??'')?>">
<label><?=e(t('form_message'))?></label><textarea name="message" rows="6" required><?=e($_POST['message']??'')?></textarea>
<button><?=e(t('form_send'))?></button>
</form>
<?php endif; ?>
</main>
<?php footer_html(); ?>
