<?php require 'functions.php'; header('Content-Type: application/xml; charset=utf-8');
$base = setting('site_url', '') ?: (isset($_SERVER['HTTPS'])?'https://':'http://').($_SERVER['HTTP_HOST']??'');
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
echo '<url><loc>'.e($base.'/index.php').'</loc></url>';
echo '<url><loc>'.e($base.'/blog.php').'</loc></url>';
echo '<url><loc>'.e($base.'/albums.php').'</loc></url>';
echo '<url><loc>'.e($base.'/contact.php').'</loc></url>';
foreach(db()->query('SELECT slug FROM animals WHERE published=1') as $a){ echo '<url><loc>'.e($base.'/animal.php?slug='.$a['slug']).'</loc></url>'; }
foreach(db()->query('SELECT slug FROM posts WHERE published=1') as $p){ echo '<url><loc>'.e($base.'/post.php?slug='.$p['slug']).'</loc></url>'; }
foreach(db()->query('SELECT slug FROM albums WHERE published=1') as $al){ echo '<url><loc>'.e($base.'/album.php?slug='.$al['slug']).'</loc></url>'; }
echo '</urlset>';
