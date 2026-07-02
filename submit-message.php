<?php
require 'functions.php';
csrf_verify();

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');
$redirect = $_POST['redirect'] ?? 'index.php';

// Alleen lokale paden toestaan als redirect (geen open-redirect naar externe sites).
if(!preg_match('#^/[A-Za-z0-9_\-./?=&%]*$#', $redirect) && !preg_match('#^[a-z0-9_\-]+\.php(\?.*)?$#i', $redirect)){
    $redirect = 'index.php';
}
$sep = str_contains($redirect, '?') ? '&' : '?';

if($name === '' || $message === ''){
    header('Location: '.$redirect.$sep.'msg=error'); exit;
}
if($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)){
    header('Location: '.$redirect.$sep.'msg=error'); exit;
}

$st = db()->prepare('INSERT INTO messages(name,email,message) VALUES(?,?,?)');
$st->execute([$name, $email, $message]);

header('Location: '.$redirect.$sep.'msg=sent'); exit;
