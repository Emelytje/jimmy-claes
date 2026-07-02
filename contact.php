<?php require 'functions.php';
$sent=false; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_verify();
    $name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $message=trim($_POST['message']??'');
    if($name==='' || $message===''){
        $err='Vul minstens je naam en bericht in.';
    } elseif($email!=='' && !filter_var($email, FILTER_VALIDATE_EMAIL)){
        $err='Vul een geldig e-mailadres in of laat het veld leeg.';
    } else {
        $st=db()->prepare('INSERT INTO messages(name,email,message) VALUES(?,?,?)');
        $st->execute([$name,$email,$message]);
        $sent=true;
    }
}
header_html('Contact', 'Neem contact op voor vragen of een fotoshoot.');
?>
<main class="wrap"><h1>Contact</h1>
<?php if($sent): ?>
<div class="notice">Bedankt voor je bericht! We nemen zo snel mogelijk contact op.</div>
<?php else: ?>
<?php if($err) echo '<div class="notice">'.e($err).'</div>'; ?>
<form method="post" class="box" style="max-width:600px">
<?=csrf_field()?>
<label>Naam</label><input name="name" value="<?=e($_POST['name']??'')?>" required>
<label>E-mail (optioneel)</label><input type="email" name="email" value="<?=e($_POST['email']??'')?>">
<label>Bericht</label><textarea name="message" rows="6" required><?=e($_POST['message']??'')?></textarea>
<button>Versturen</button>
</form>
<?php endif; ?>
</main>
<?php footer_html(); ?>
