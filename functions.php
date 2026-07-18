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
require_once __DIR__.'/i18n.php';

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

// Gratis geocoding (OpenStreetMap Nominatim, geen API-sleutel nodig) van
// "stad, land" naar [lat, lng], voor de kaart met bezochte dierentuinen.
// Geeft null terug bij eender welk probleem (geen internet, niet gevonden,
// enz.) zodat een mislukte opzoeking de rest van de pagina nooit breekt —
// de admin kan het later gewoon opnieuw proberen door de zoo op te slaan.
function geocode_city_country($city, $country){
    $query = trim(trim($city).', '.trim($country), ', ');
    if($query === '') return null;
    $url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q='.rawurlencode($query);
    $userAgent = 'JimmyClaesSite/1.0 ('.(setting('contact_email','') ?: 'no-contact-set').')';
    $body = null;
    if(function_exists('curl_init')){
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['User-Agent: '.$userAgent],
            CURLOPT_TIMEOUT => 8,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
    } elseif(ini_get('allow_url_fopen')){
        $ctx = stream_context_create(['http' => ['header' => "User-Agent: $userAgent\r\n", 'timeout' => 8]]);
        $body = @file_get_contents($url, false, $ctx);
    }
    if(!$body) return null;
    $data = json_decode($body, true);
    if(!is_array($data) || !isset($data[0]['lat'], $data[0]['lon'])) return null;
    return [(float)$data[0]['lat'], (float)$data[0]['lon']];
}

// Automatisch de foto's uit een gekoppelde Google Drive-map ophalen, zodat
// je niet elke foto apart naar de site moet uploaden. Vereist een Google
// Drive API-key (GOOGLE_DRIVE_API_KEY in config.php) EN dat de map gedeeld
// is als "Iedereen met de link" — een kale API-key kan geen privé-mappen
// lezen. Zonder key of bij een niet-map-link blijft alles gewoon werken
// zoals voorheen (enkel de handmatige Drive-link-doorklik).
function drive_api_key(){
    return defined('GOOGLE_DRIVE_API_KEY') ? trim(GOOGLE_DRIVE_API_KEY) : '';
}

function drive_extract_folder_id($url){
    if(preg_match('~/folders/([a-zA-Z0-9_-]+)~', (string)$url, $m)) return $m[1];
    return null;
}

function drive_thumbnail_url($fileId, $size=1600){
    return 'https://drive.google.com/thumbnail?id='.rawurlencode($fileId).'&sz=w'.(int)$size;
}
function drive_file_view_url($fileId){
    return 'https://drive.google.com/file/d/'.rawurlencode($fileId).'/view';
}

function drive_http_get($url, $timeout=8){
    if(function_exists('curl_init')){
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout]);
        $body = curl_exec($ch);
        curl_close($ch);
        return $body ?: null;
    } elseif(ini_get('allow_url_fopen')){
        $ctx = stream_context_create(['http' => ['timeout' => $timeout]]);
        return @file_get_contents($url, false, $ctx) ?: null;
    }
    return null;
}

function drive_fetch_folder_images_raw($folderId){
    $key = drive_api_key();
    if($key === '' || !$folderId) return [];
    $images = [];
    $pageToken = '';
    $pages = 0;
    do{
        $q = rawurlencode("'".$folderId."' in parents and mimeType contains 'image/' and trashed=false");
        $url = 'https://www.googleapis.com/drive/v3/files?q='.$q
            .'&fields='.rawurlencode('nextPageToken,files(id,name)')
            .'&pageSize=100&key='.rawurlencode($key);
        if($pageToken !== '') $url .= '&pageToken='.rawurlencode($pageToken);
        $body = drive_http_get($url);
        if(!$body) break;
        $data = json_decode($body, true);
        if(!is_array($data) || !isset($data['files'])) break;
        foreach($data['files'] as $f){
            if(!empty($f['id'])) $images[] = ['id' => $f['id'], 'name' => $f['name'] ?? ''];
        }
        $pageToken = $data['nextPageToken'] ?? '';
        $pages++;
    } while($pageToken !== '' && $pages < 5);
    return $images;
}

const DRIVE_CACHE_TTL = 21600; // 6 uur — genoeg marge tegen Drive-quota, kort genoeg om nieuwe foto's snel te tonen

// Haalt de foto's van een Drive-map op, met caching in de DB. Bij een
// mislukte live-opvraging (quota, netwerk, tijdelijk probleem) valt dit
// terug op de laatst gekende cache in plaats van de sectie leeg te tonen.
function drive_get_folder_images($folderUrl){
    $folderId = drive_extract_folder_id($folderUrl);
    if(!$folderId || drive_api_key() === '') return [];
    try{
        db()->exec("CREATE TABLE IF NOT EXISTS drive_cache(folder_id VARCHAR(120) PRIMARY KEY, images_json MEDIUMTEXT, fetched_at INT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }catch(Exception $e){}

    $cachedRow = null;
    try{
        $st = db()->prepare('SELECT images_json, fetched_at FROM drive_cache WHERE folder_id=?');
        $st->execute([$folderId]);
        $cachedRow = $st->fetch();
        if($cachedRow && (time() - (int)$cachedRow['fetched_at']) < DRIVE_CACHE_TTL){
            $decoded = json_decode($cachedRow['images_json'], true);
            if(is_array($decoded)) return $decoded;
        }
    }catch(Exception $e){}

    $images = drive_fetch_folder_images_raw($folderId);
    if($images){
        try{
            $json = json_encode($images, JSON_UNESCAPED_UNICODE);
            db()->prepare('INSERT INTO drive_cache(folder_id, images_json, fetched_at) VALUES(?,?,?) ON DUPLICATE KEY UPDATE images_json=VALUES(images_json), fetched_at=VALUES(fetched_at)')
                ->execute([$folderId, $json, time()]);
        }catch(Exception $e){}
        return $images;
    }

    if($cachedRow){
        $decoded = json_decode($cachedRow['images_json'], true);
        if(is_array($decoded)) return $decoded;
    }
    return [];
}

// Downloadt één foto uit Drive naar uploads/, zodat een dier dat enkel via
// Drive gekoppeld is toch overal een miniatuur heeft (categorie-overzicht,
// "recent toegevoegd", admin-lijst...) — niet enkel op de eigen
// detailpagina, waar de Drive-galerij zelf al rechtstreeks toont. Faalt
// stil (null) bij eender welk probleem; de aanroeper beslist dan gewoon
// niets te veranderen.
function drive_download_image_to_uploads($fileId){
    $key = drive_api_key();
    if($key === '' || !$fileId) return null;
    $url = 'https://www.googleapis.com/drive/v3/files/'.rawurlencode($fileId).'?alt=media&key='.rawurlencode($key);
    $body = drive_http_get($url, 20);
    if(!$body || strlen($body) > 15*1024*1024) return null;

    $tmp = tempnam(sys_get_temp_dir(), 'drv');
    file_put_contents($tmp, $body);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $mime = @mime_content_type($tmp);
    if(!isset($allowed[$mime]) || !@getimagesize($tmp)){ @unlink($tmp); return null; }

    if(!is_dir(__DIR__.'/uploads')) mkdir(__DIR__.'/uploads', 0775, true);
    $name = date('YmdHis').'-'.bin2hex(random_bytes(4)).'.'.$allowed[$mime];
    $dest = __DIR__.'/uploads/'.$name;
    if(!@rename($tmp, $dest)){ @unlink($tmp); return null; }
    return 'uploads/'.$name;
}

// Als een dier via Drive gekoppeld is maar nog geen coverfoto heeft, wordt
// de eerste Drive-foto eenmalig gedownload en als coverfoto ingesteld —
// zodat het dier ook buiten zijn eigen pagina (categorie-overzicht, "recent
// toegevoegd", admin-lijst) een miniatuur heeft, niet enkel in de eigen
// Drive-galerij. Gebeurt maar één keer: zodra cover_image niet meer leeg
// is, wordt dit pad nooit meer uitgevoerd voor dat dier.
function drive_maybe_set_animal_cover($animal, $driveImages){
    if(!empty($animal['cover_image']) || !$driveImages) return;
    $path = drive_download_image_to_uploads($driveImages[0]['id']);
    if($path){
        try{ db()->prepare('UPDATE animals SET cover_image=? WHERE id=?')->execute([$path, $animal['id']]); }
        catch(Exception $e){}
    }
}

// Probeert uit een Drive-bestandsnaam te herkennen in welke (al bestaande)
// dierentuin de foto genomen is, bv. "Serpentarium Blankenberge - BELGIE -
// 06/07/2017.jpg" → zoo "Serpentarium Blankenberge". Maakt nooit zelf een
// nieuwe zoo aan (te veel kans op rommelige duplicaten door wisselende
// naamgeving) — enkel een match tegen een naam die al in de zoo-lijst
// staat, hoofdletterongevoelig en met een beetje speling (substring beide
// kanten) omdat foto-bestandsnamen niet altijd exact de zoo-titel gebruiken.
function drive_guess_zoo_id($filename){
    static $zoos = null;
    if($zoos === null){
        try{ $zoos = db()->query('SELECT id, title FROM zoos')->fetchAll(); }
        catch(Exception $e){ $zoos = []; }
    }
    if(!$zoos) return null;
    // Geen pathinfo() hier: Drive-bestandsnamen bevatten soms letterlijke
    // '/'-tekens (bv. een datum "06/07/2017.jpg"), die pathinfo() als
    // pad-scheidingstekens leest en zo alles vóór de laatste '/' wegkapt.
    $name = preg_replace('/\.[a-zA-Z0-9]{2,5}$/', '', (string)$filename);
    $parts = array_filter(array_map('trim', explode(' - ', $name)));
    foreach($parts as $part){
        if(mb_strlen($part) < 3) continue;
        foreach($zoos as $z){
            $zt = trim($z['title']);
            if($zt === '' || mb_strlen($zt) < 3) continue;
            if(mb_stripos($part, $zt) !== false || mb_stripos($zt, $part) !== false){
                return (int)$z['id'];
            }
        }
    }
    return null;
}

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

function header_html($title='', $description='', $canonical='', $head_extra='', $body_attrs=''){
    $font=setting('font','Georgia'); $primary=setting('primary_color','#7b5f46'); $accent=setting('accent_color','#eadfd2');
    echo '<!doctype html><html lang="'.e(current_lang()).'"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    $full_title = meta_tags($title, $description, $canonical);
    echo '<title>'.e($full_title).'</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500&family=Karla:wght@400;500;600;700&display=swap">';
    echo '<link rel="stylesheet" href="assets/style.css?v='.asset_v(__DIR__.'/assets/style.css').'"><style>:root{--primary:'.e($primary).';--accent:'.e($accent).';'.($font && $font!=='Georgia' ? "--font:'".e($font)."';" : '').'}</style>';
    if($head_extra) echo $head_extra;
    echo '</head><body'.($body_attrs ? ' '.$body_attrs : '').'>';
    echo '<button class="nav-toggle" type="button" aria-label="Menu" aria-expanded="false"><span></span></button>';
    $siteTitle = setting('site_title','Jimbo Animal Species of the World');
    $logo = setting('site_logo', '');
    $brandInner = $logo ? '<img src="'.e($logo).'" alt="'.e($siteTitle).'" class="brand-logo">' : e($siteTitle);
    echo '<header class="top"><a class="brand" href="index.php">'.$brandInner.'</a><nav><a href="index.php">'.t('nav_home').'</a>';
    echo nav_render_zoos();
    try{ foreach(db()->query('SELECT title,slug FROM pages WHERE published=1 AND show_in_nav=1 AND is_homepage=0 ORDER BY sort_order,title') as $p){ echo '<a href="page.php?slug='.e($p['slug']).'">'.e($p['title']).'</a>'; }}catch(Exception $e){}
    echo '<a href="contact.php">'.t('nav_contact').'</a>';
    echo lang_switch_html();
    echo '</nav></header>';
}
function footer_html(){
    $leaflet = !empty($GLOBALS['pb_needs_leaflet'])
        ? '<script src="assets/leaflet/leaflet.js?v='.asset_v(__DIR__.'/assets/leaflet/leaflet.js').'"></script>'
        .'<script src="assets/zoo-map.js?v='.asset_v(__DIR__.'/assets/zoo-map.js').'"></script>'
        : '';
    echo '<footer>© <a class="secret" href="login.php">'.date('Y').'</a> '.e(setting('site_title','Jimbo Animal Species of the World')).' &middot; '.t('footer_by').' <a href="https://myemitdreams.nl" target="_blank" rel="noopener">MyEmitdreams</a></footer>'.$leaflet.'<script src="assets/app.js?v='.asset_v(__DIR__.'/assets/app.js').'"></script></body></html>';
}
