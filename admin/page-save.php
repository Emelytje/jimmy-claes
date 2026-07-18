<?php
require __DIR__.'/inc.php';
header('Content-Type: application/json');

const PBS_TABLES = ['page'=>'pages', 'animal'=>'animals', 'album'=>'albums', 'post'=>'posts', 'category'=>'categories'];
const PBS_DESC_COLS = ['animal'=>'description', 'album'=>'description', 'post'=>'excerpt', 'category'=>'description'];
// Korte categorienamen zijn zonder context dubbelzinnig voor DeepL (bv.
// "Vissen" als diercategorie vs. de werkwoordsvorm "to fish").
const PBS_CATEGORY_TRANSLATE_CONTEXT = 'Diercategorie op een website over dierentuinen, zoals Zoogdieren, Vogels, Reptielen.';

// Loopt van $parentId omhoog via parent_id-links en geeft true terug als
// $id ergens in die keten voorkomt (voorkomt dat een categorie zichzelf of
// een eigen afstammeling als bovenliggende categorie krijgt).
function pbs_category_creates_cycle($id, $parentId){
    $guard = 0;
    while($parentId && $guard++ < 50){
        if((int)$parentId === (int)$id) return true;
        $st = db()->prepare('SELECT parent_id FROM categories WHERE id=?');
        $st->execute([$parentId]);
        $row = $st->fetch();
        if(!$row) break;
        $parentId = $row['parent_id'];
    }
    return false;
}

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
} elseif($type === 'category'){
    $cover_image = trim((string)($body['cover_image'] ?? ''));
    $description = trim((string)($body['description'] ?? ''));
    $parent_id = (int)($body['parent_id'] ?? 0) ?: null;
    if($parent_id && pbs_category_creates_cycle($id, $parent_id)) $parent_id = null;
    $st = db()->prepare('UPDATE categories SET title=?, slug=?, blocks=?, published=?, cover_image=?, description=?, parent_id=?, meta_title=?, meta_description=? WHERE id=?');
    $st->execute([$title, $slug, $blocksJson, $published, $cover_image, $description, $parent_id, $meta_title, $meta_description, $id]);
    auto_translate_field('categories', $id, 'title', $title, false, PBS_CATEGORY_TRANSLATE_CONTEXT);
    auto_translate_field('categories', $id, 'description', $description);
} else {
    $cover_image = trim((string)($body['cover_image'] ?? ''));
    $description = trim((string)($body['description'] ?? ''));
    $descCol = PBS_DESC_COLS[$type];
    if($type === 'animal'){
        $category_id = (int)($body['category_id'] ?? 0) ?: null;
        if(!pb_has_column('animals','drive_url')){ try{ db()->exec("ALTER TABLE animals ADD COLUMN drive_url VARCHAR(500) DEFAULT NULL"); }catch(Exception $e){} }
        $drive_url = trim((string)($body['drive_url'] ?? ''));
        if($drive_url !== '' && !preg_match('~^https?://~i', $drive_url)) $drive_url = 'https://'.$drive_url;
        $st = db()->prepare("UPDATE $table SET title=?, slug=?, blocks=?, published=?, cover_image=?, $descCol=?, category_id=?, drive_url=?, meta_title=?, meta_description=? WHERE id=?");
        $st->execute([$title, $slug, $blocksJson, $published, $cover_image, $description, $category_id, $drive_url !== '' ? $drive_url : null, $meta_title, $meta_description, $id]);
        // Diersoortnamen zijn Latijn en worden nooit vertaald — enkel de
        // beschrijving (indien ingevuld) kan een Engelse variant krijgen.
        auto_translate_field('animals', $id, $descCol, $description);
    } else {
        $st = db()->prepare("UPDATE $table SET title=?, slug=?, blocks=?, published=?, cover_image=?, $descCol=?, meta_title=?, meta_description=? WHERE id=?");
        $st->execute([$title, $slug, $blocksJson, $published, $cover_image, $description, $meta_title, $meta_description, $id]);
        auto_translate_field($table, $id, 'title', $title);
        auto_translate_field($table, $id, $descCol, $description);
    }
}

// Elke pagina/dier/categorie/album/post kan ook eigen pagebuilder-blokken
// hebben (titels, tekstvakken, citaten...) — die krijgen apart een
// gecachete Engelse versie, zie pb_get_translated_blocks() in blocks.php.
// Cache hier ongeldig maken (niet meteen vertalen): dat gebeurt lazy bij
// de eerste EN-weergave, zodat opslaan in de editor niet trager wordt.
pb_invalidate_blocks_translation($table, $id);

echo json_encode(['ok'=>true, 'slug'=>$slug]);
