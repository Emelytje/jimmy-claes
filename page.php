<?php
require 'functions.php';
$slug = $_GET['slug'] ?? '';
$st = db()->prepare('SELECT * FROM pages WHERE slug=? AND published=1');
$st->execute([$slug]);
$page = $st->fetch();
if(!$page){ http_response_code(404); die('Pagina niet gevonden'); }
track_view('pages', $page['id']);

$blocks = pb_decode_blocks($page['blocks']);
$fontHref = pb_google_fonts_link_href(pb_font_families_used($blocks));
$headExtra = $fontHref ? '<link rel="stylesheet" href="'.e($fontHref).'">' : '';

header_html($page['meta_title'] ?: $page['title'], $page['meta_description'] ?: '', '', $headExtra);
?>
<main class="pb-page">
<?php
if(isset($_GET['msg']) && $_GET['msg']==='sent') echo '<div class="wrap" style="padding-bottom:0"><div class="notice">Bedankt voor je bericht! We nemen zo snel mogelijk contact op.</div></div>';
if(isset($_GET['msg']) && $_GET['msg']==='error') echo '<div class="wrap" style="padding-bottom:0"><div class="notice" style="background:var(--danger-bg);color:var(--danger)">Vul minstens je naam en bericht in (en een geldig e-mailadres indien ingevuld).</div></div>';
echo render_blocks($blocks);
?>
</main>
<?php footer_html(); ?>
