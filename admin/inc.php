<?php
require_once __DIR__.'/../functions.php';
require_admin();

function admin_header($title, $active=''){
    echo '<!doctype html><html lang="'.e(current_lang()).'"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>'.e($title).' - Beheer - '.e(setting('site_title','Jimbo Animal Species of the World')).'</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500&family=Karla:wght@400;500;600;700&display=swap">';
    echo '<link rel="stylesheet" href="../assets/style.css?v='.asset_v(__DIR__.'/../assets/style.css').'"><link rel="stylesheet" href="assets/admin.css?v='.asset_v(__DIR__.'/assets/admin.css').'">';
    echo '</head><body class="admin">';
    echo '<div class="admin-shell">';
    echo '<aside class="admin-side"><a class="admin-brand" href="index.php">'.e(setting('site_title','Jimbo Animal Species of the World')).'</a><nav>';
    $links = [
        'index'=>['index.php', t('admin_dashboard')],
        'pages'=>['pages.php', t('admin_pages')],
        'animals'=>['content.php?type=animal', t('admin_animals')],
        'categories'=>['content.php?type=category', t('admin_categories')],
        'zoos'=>['zoos.php', t('admin_zoos')],
        'messages'=>['messages.php', t('admin_messages')],
        'settings'=>['settings.php', t('admin_settings')],
    ];
    foreach($links as $key=>$l){ echo '<a href="'.e($l[0]).'" class="'.($active===$key?'is-active':'').'">'.e($l[1]).'</a>'; }
    echo '</nav><div class="admin-side-bottom">'.lang_switch_html().' <a href="../index.php" target="_blank">'.e(t('admin_view_site')).' &#8599;</a><a href="logout.php">'.e(t('admin_logout')).'</a></div></aside>';
    echo '<main class="admin-main"><div class="admin-topbar"><h1>'.e($title).'</h1></div><div class="admin-content">';
}
function admin_footer(){
    echo '</div></main></div></body></html>';
}
