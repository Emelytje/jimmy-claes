<?php
require 'functions.php';
$slug = $_GET['slug'] ?? '';
$st = db()->prepare('SELECT * FROM pages WHERE slug=? AND published=1');
$st->execute([$slug]);
$page = $st->fetch();
if(!$page){ http_response_code(404); die(t('page_not_found')); }
track_view('pages', $page['id']);

$blocks = pb_get_translated_blocks('pages', $page['id'], pb_decode_blocks($page['blocks']));
$headExtra = pb_page_head_extra($blocks);

header_html($page['meta_title'] ?: $page['title'], $page['meta_description'] ?: '', '', $headExtra);
?>
<main class="pb-page">
<?php
if(isset($_GET['msg']) && $_GET['msg']==='sent') echo '<div class="wrap" style="padding-bottom:0"><div class="notice">'.e(t('thanks_message')).'</div></div>';
if(isset($_GET['msg']) && $_GET['msg']==='error') echo '<div class="wrap" style="padding-bottom:0"><div class="notice" style="background:var(--danger-bg);color:var(--danger)">'.e(t('fill_required')).'</div></div>';
echo render_blocks($blocks);
?>
</main>
<?php footer_html(); ?>
