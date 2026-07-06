<?php
require_once __DIR__.'/../functions.php';
require_admin();

function admin_header($title, $active=''){
    echo '<!doctype html><html lang="nl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>'.e($title).' - Beheer - Dieren door de lens</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500&family=Karla:wght@400;500;600;700&display=swap">';
    echo '<link rel="stylesheet" href="../assets/style.css"><link rel="stylesheet" href="assets/admin.css">';
    echo '</head><body class="admin">';
    echo '<div class="admin-shell">';
    echo '<aside class="admin-side"><a class="admin-brand" href="index.php">Dieren door de lens</a><nav>';
    $links = [
        'index'=>['index.php','Dashboard'],
        'pages'=>['pages.php','Pagina\'s'],
        'animals'=>['content.php?type=animal','Dieren'],
        'albums'=>['content.php?type=album','Albums'],
        'posts'=>['content.php?type=post','Blog'],
        'settings'=>['settings.php','Site-instellingen'],
    ];
    foreach($links as $key=>$l){ echo '<a href="'.e($l[0]).'" class="'.($active===$key?'is-active':'').'">'.e($l[1]).'</a>'; }
    echo '</nav><div class="admin-side-bottom"><a href="../index.php" target="_blank">Bekijk site &#8599;</a><a href="logout.php">Uitloggen</a></div></aside>';
    echo '<main class="admin-main"><div class="admin-topbar"><h1>'.e($title).'</h1></div><div class="admin-content">';
}
function admin_footer(){
    echo '</div></main></div></body></html>';
}
