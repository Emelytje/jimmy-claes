<?php
require __DIR__.'/inc.php';
header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD']!=='POST'){ http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Methode niet toegestaan.']); exit; }

$ok = !empty($_POST['csrf']) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
if(!$ok){ http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Beveiligingscontrole mislukt. Herlaad de pagina.']); exit; }

try{
    $path = upload_image($_FILES['image'] ?? null);
    if(!$path){ echo json_encode(['ok'=>false,'error'=>'Geen geldig bestand ontvangen.']); exit; }
    echo json_encode(['ok'=>true,'path'=>$path]);
}catch(Exception $e){
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
