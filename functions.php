<?php
session_start();
if (file_exists(__DIR__.'/config.php')) require_once __DIR__.'/config.php';
require_once __DIR__.'/blocks.php';

function installed(){ return defined('DB_HOST') && file_exists(__DIR__.'/config.php'); }
function db(){
    static $pdo; if($pdo) return $pdo;
    if(!installed()){ header('Location: install.php'); exit; }
    $dsn='mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';
    $pdo=new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    return $pdo;
}
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function asset_v($absPath){ $v = @filemtime($absPath); return $v ?: time(); }
function setting($key,$default=''){
    try{ $st=db()->prepare('SELECT value FROM settings WHERE name=?'); $st->execute([$key]); $r=$st->fetch(); return $r?$r['value']:$default; }catch(Exception $e){ return $default; }
}
function set_setting($key,$value){ $st=db()->prepare('INSERT INTO settings(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)'); $st->execute([$key,$value]); }
function notify_contact_message($name, $email, $message){
    $to = setting('contact_email', '');
    if($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) return;
    $site = setting('site_title', 'Dieren door de lens');
    $subject = 'Nieuw contactbericht via '.$site;
    $body = "Naam: $name\nE-mail: ".($email ?: '(niet opgegeven)')."\n\nBericht:\n$message";
    $headers = "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= 'From: '.$site.' <no-reply@'.($_SERVER['HTTP_HOST'] ?? 'localhost').">\r\n";
    if($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) $headers .= 'Reply-To: '.$email."\r\n";
    try{ @mail($to, $subject, $body, $headers); }catch(Exception $e){}
}
function track_view($table, $id){
    $allowed = ['pages','animals','albums','posts'];
    if(!in_array($table, $allowed, true) || !$id) return;
    try{ db()->prepare("UPDATE $table SET views = views + 1 WHERE id = ?")->execute([(int)$id]); }catch(Exception $e){}
}
function is_admin(){ return !empty($_SESSION['admin_id']); }
function require_admin(){ if(!is_admin()){ header('Location: ../login.php'); exit; } }
function slugify($text){ $text=strtolower(trim($text)); $text=preg_replace('/[^a-z0-9]+/','-',$text); return trim($text,'-') ?: 'item'; }

// ---- CSRF protection ----
function csrf_token(){
    if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(){ return '<input type="hidden" name="csrf" value="'.e(csrf_token()).'">'; }
function csrf_verify(){
    if($_SERVER['REQUEST_METHOD']==='POST'){
        $ok = !empty($_POST['csrf']) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
        if(!$ok){ http_response_code(400); die('Beveiligingscontrole mislukt (csrf). Ga terug, herlaad de pagina en probeer opnieuw.'); }
    }
}

function upload_image($file){
    if(empty($file['tmp_name']) || $file['error']!==UPLOAD_ERR_OK) return null;
    $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
    $mime=mime_content_type($file['tmp_name']);
    if(!isset($allowed[$mime])) throw new Exception('Alleen jpg, png, webp of gif toegelaten.');
    if(!@getimagesize($file['tmp_name'])) throw new Exception('Bestand is geen geldige afbeelding.');
    if($file['size'] > 8*1024*1024) throw new Exception('Foto is te groot. Max 8 MB.');
    if(!is_dir(__DIR__.'/uploads')) mkdir(__DIR__.'/uploads',0775,true);
    $name=date('YmdHis').'-'.bin2hex(random_bytes(4)).'.'.$allowed[$mime];
    $dest=__DIR__.'/uploads/'.$name;
    if(!move_uploaded_file($file['tmp_name'],$dest)) throw new Exception('Upload mislukt.');
    return 'uploads/'.$name;
}

// ---- SEO / meta ----
function meta_tags($title='', $description='', $canonical=''){
    $site=setting('site_title','Dieren door de lens');
    $desc=$description ?: setting('meta_description','Dierenfotografie: verhalen en beelden per dier.');
    $full_title = $title ? $title.' - '.$site : $site;
    echo '<meta name="description" content="'.e($desc).'">';
    echo '<meta property="og:title" content="'.e($full_title).'">';
    echo '<meta property="og:description" content="'.e($desc).'">';
    echo '<meta property="og:type" content="website">';
    echo '<meta name="twitter:card" content="summary_large_image">';
    if($canonical) echo '<link rel="canonical" href="'.e($canonical).'">';
    return $full_title;
}

function header_html($title='', $description='', $canonical='', $head_extra=''){
    $font=setting('font','Georgia'); $primary=setting('primary_color','#7b5f46'); $accent=setting('accent_color','#eadfd2');
    echo '<!doctype html><html lang="nl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    $full_title = meta_tags($title, $description, $canonical);
    echo '<title>'.e($full_title).'</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500&family=Karla:wght@400;500;600;700&display=swap">';
    echo '<link rel="stylesheet" href="assets/style.css?v='.asset_v(__DIR__.'/assets/style.css').'"><style>:root{--primary:'.e($primary).';--accent:'.e($accent).';'.($font && $font!=='Georgia' ? "--font:'".e($font)."';" : '').'}</style>';
    if($head_extra) echo $head_extra;
    echo '</head><body>';
    echo '<button class="nav-toggle" type="button" aria-label="Menu" aria-expanded="false"><span></span></button>';
    echo '<header class="top"><a class="brand" href="index.php">'.e(setting('site_title','Dieren door de lens')).'</a><nav><a href="index.php">Home</a>';
    echo '<div class="nav-dropdown"><a href="animals.php" class="nav-dropdown-toggle">Dieren</a><div class="nav-dropdown-menu">';
    try{ foreach(db()->query('SELECT title,slug FROM animals WHERE published=1 ORDER BY sort_order,title') as $a){ echo '<a href="animal.php?slug='.e($a['slug']).'">'.e($a['title']).'</a>'; }}catch(Exception $e){}
    echo '</div></div>';
    echo '<a href="albums.php">Albums</a>';
    echo '<a href="blog.php">Blog</a>';
    try{ foreach(db()->query('SELECT title,slug FROM pages WHERE published=1 AND show_in_nav=1 ORDER BY sort_order,title') as $p){ echo '<a href="page.php?slug='.e($p['slug']).'">'.e($p['title']).'</a>'; }}catch(Exception $e){}
    echo '<a href="contact.php">Contact</a>';
    echo '</nav></header>';
}
function footer_html(){ echo '<footer>© <a class="secret" href="login.php">'.date('Y').'</a> '.e(setting('site_title','Dieren door de lens')).' &middot; Website door <a href="https://myemitdreams.nl" target="_blank" rel="noopener">MyEmitdreams</a></footer><script src="assets/app.js?v='.asset_v(__DIR__.'/assets/app.js').'"></script></body></html>'; }
