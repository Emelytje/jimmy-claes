<?php
// Gratis hosting (bv. InfinityFree) heeft de gedeelde systeem-tmp-map voor
// sessies soms niet betrouwbaar beschikbaar, waardoor ingelogde admins
// random weer uitgelogd raken. Gebruik in plaats daarvan een eigen,
// gegarandeerd schrijfbare map binnen het project.
$__sessionDir = __DIR__.'/.sessions';
if(!is_dir($__sessionDir)) @mkdir($__sessionDir, 0700);
if(is_dir($__sessionDir) && is_writable($__sessionDir)){
    session_save_path($__sessionDir);
    if(!file_exists($__sessionDir.'/.htaccess')) @file_put_contents($__sessionDir.'/.htaccess', "Require all denied\n");
}
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
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

// Bouwt een ingesprongen lijst van alle categorieën voor een <select>, met
// optioneel een uitgesloten id + zijn afstammelingen (om te voorkomen dat een
// categorie zichzelf of een eigen kind als bovenliggende categorie kiest).
function pbe_category_options($selectedId, $excludeId=null){
    try{ $rows = db()->query('SELECT id, title, parent_id FROM categories ORDER BY sort_order, title')->fetchAll(); }
    catch(Exception $e){ return ''; }
    $byParent = [];
    foreach($rows as $r){ $byParent[(int)$r['parent_id']][] = $r; }
    $excludeIds = [];
    if($excludeId){
        $stack = [(int)$excludeId];
        while($stack){
            $cur = array_pop($stack); $excludeIds[$cur] = true;
            foreach(($byParent[$cur] ?? []) as $child){ $stack[] = (int)$child['id']; }
        }
    }
    $html = '';
    $walk = function($parentId, $depth) use (&$walk, &$html, $byParent, $selectedId, $excludeIds){
        foreach(($byParent[$parentId] ?? []) as $row){
            if(isset($excludeIds[(int)$row['id']])) continue;
            $html .= '<option value="'.(int)$row['id'].'" '.((int)$selectedId===(int)$row['id']?'selected':'').'>'.str_repeat('&mdash; ', $depth).e($row['title']).'</option>';
            $walk((int)$row['id'], $depth+1);
        }
    };
    $walk(0, 0);
    return $html;
}

// Geeft alle categorieën terug als platte lijst in boomvolgorde, elk met een
// toegevoegde 'depth' sleutel — handig om ingesprongen te tonen in een tabel.
function pbe_category_tree_flat(){
    try{ $rows = db()->query('SELECT * FROM categories ORDER BY sort_order, title')->fetchAll(); }
    catch(Exception $e){ return []; }
    $byParent = [];
    foreach($rows as $r){ $byParent[(int)$r['parent_id']][] = $r; }
    $out = [];
    $walk = function($parentId, $depth) use (&$walk, &$out, $byParent){
        foreach(($byParent[$parentId] ?? []) as $row){
            $row['depth'] = $depth;
            $out[] = $row;
            $walk((int)$row['id'], $depth+1);
        }
    };
    $walk(0, 0);
    return $out;
}
function setting($key,$default=''){
    try{ $st=db()->prepare('SELECT value FROM settings WHERE name=?'); $st->execute([$key]); $r=$st->fetch(); return $r?$r['value']:$default; }catch(Exception $e){ return $default; }
}
function set_setting($key,$value){ $st=db()->prepare('INSERT INTO settings(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)'); $st->execute([$key,$value]); }
function notify_contact_message($name, $email, $message){
    $to = setting('contact_email', '');
    if($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) return;
    $site = setting('site_title', 'Jimbo Animal Species of the World');
    $subject = 'Nieuw contactbericht via '.$site;
    $body = "Naam: $name\nE-mail: ".($email ?: '(niet opgegeven)')."\n\nBericht:\n$message";
    $headers = "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= 'From: '.$site.' <no-reply@'.($_SERVER['HTTP_HOST'] ?? 'localhost').">\r\n";
    if($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) $headers .= 'Reply-To: '.$email."\r\n";
    try{ @mail($to, $subject, $body, $headers); }catch(Exception $e){}
}
function track_view($table, $id){
    $allowed = ['pages','animals','albums','posts','categories'];
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

// De hoofdnav toont geen dierenklassen meer (die zitten nu achter de
// Gewervelde/Ongewervelde-knoppen op de homepage) maar links naar externe
// dierentuinen, beheerd via admin/zoos.php.
function nav_render_zoos(){
    $html = '';
    try{
        $rows = db()->query('SELECT title, url FROM zoos WHERE published=1 ORDER BY sort_order, title')->fetchAll();
        foreach($rows as $z){
            $html .= '<a href="'.e($z['url']).'" target="_blank" rel="noopener">'.e($z['title']).'</a>';
        }
    }catch(Exception $e){}
    return $html;
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
    $site=setting('site_title','Jimbo Animal Species of the World');
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
    echo '<header class="top"><a class="brand" href="index.php">'.e(setting('site_title','Jimbo Animal Species of the World')).'</a><nav><a href="index.php">Home</a>';
    echo nav_render_zoos();
    try{ foreach(db()->query('SELECT title,slug FROM pages WHERE published=1 AND show_in_nav=1 AND is_homepage=0 ORDER BY sort_order,title') as $p){ echo '<a href="page.php?slug='.e($p['slug']).'">'.e($p['title']).'</a>'; }}catch(Exception $e){}
    echo '<a href="contact.php">Contact</a>';
    echo '</nav></header>';
}
function footer_html(){ echo '<footer>© <a class="secret" href="login.php">'.date('Y').'</a> '.e(setting('site_title','Jimbo Animal Species of the World')).' &middot; Website door <a href="https://myemitdreams.nl" target="_blank" rel="noopener">MyEmitdreams</a></footer><script src="assets/app.js?v='.asset_v(__DIR__.'/assets/app.js').'"></script></body></html>'; }
