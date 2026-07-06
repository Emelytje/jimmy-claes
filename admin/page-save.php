<?php
require __DIR__.'/inc.php';
header('Content-Type: application/json');

const PBS_TABLES = ['page'=>'pages', 'animal'=>'animals', 'album'=>'albums', 'post'=>'posts'];

if($_SERVER['REQUEST_METHOD']!=='POST'){ http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Methode niet toegestaan.']); exit; }

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if(!is_array($body)){ http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Ongeldige data.']); exit; }

$csrf = $body['csrf'] ?? '';
if(empty($csrf) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)){
    http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Beveiligingscontrole mislukt. Herlaad de pagina.']); exit;
}

$type = $body['type'] ?? 'page';
if(!isset(PBS_TABLES[$type])) $type = 'page';
$table = PBS_TABLES[$type];

$id = (int)($body['id'] ?? 0);
$title = trim((string)($body['title'] ?? ''));
$slug = slugify($body['slug'] ?? $title);
$meta_title = trim((string)($body['meta_title'] ?? ''));
$meta_description = trim((string)($body['meta_description'] ?? ''));
$published = !empty($body['published']) ? 1 : 0;
$blocks = $body['blocks'] ?? [];

if($id<=0 || $title===''){ http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Titel is verplicht.']); exit; }
if(!is_array($blocks)){ $blocks = []; }

$blocksJson = pb_encode_blocks($blocks);
if(strlen($blocksJson) > 3*1024*1024){ http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Pagina-inhoud is te groot.']); exit; }

$chk = db()->prepare("SELECT id FROM $table WHERE slug=? AND id<>?");
$chk->execute([$slug, $id]);
if($chk->fetch()){
    $base = $slug; $i = 2;
    $chk2 = db()->prepare("SELECT id FROM $table WHERE slug=? AND id<>?");
    while(true){ $slug = $base.'-'.$i; $chk2->execute([$slug,$id]); if(!$chk2->fetch()) break; $i++; }
}

if($type === 'page'){
    $show_in_nav = !empty($body['show_in_nav']) ? 1 : 0;
    $is_homepage = !empty($body['is_homepage']) ? 1 : 0;
    if($is_homepage) db()->exec('UPDATE pages SET is_homepage=0');
    $st = db()->prepare('UPDATE pages SET title=?, slug=?, blocks=?, published=?, show_in_nav=?, is_homepage=?, meta_title=?, meta_description=? WHERE id=?');
    $st->execute([$title, $slug, $blocksJson, $published, $show_in_nav, $is_homepage, $meta_title, $meta_description, $id]);
} else {
    $cover_image = trim((string)($body['cover_image'] ?? ''));
    $st = db()->prepare("UPDATE $table SET title=?, slug=?, blocks=?, published=?, cover_image=?, meta_title=?, meta_description=? WHERE id=?");
    $st->execute([$title, $slug, $blocksJson, $published, $cover_image, $meta_title, $meta_description, $id]);
}

echo json_encode(['ok'=>true, 'slug'=>$slug]);
