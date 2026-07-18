<?php
/**
 * Vertaalt automatisch (via DeepL) de categorienaam + -omschrijving van elke
 * categorie die nog geen Engelse variant heeft — nodig voor bestaande
 * categorieën die al bestonden vóór automatische vertaling. Nieuwe/gewijzigde
 * categorieën worden voortaan al automatisch vertaald bij het opslaan (zie
 * auto_translate_field() in functions.php). Bestaande handmatige *_en-waarden
 * worden nooit overschreven. Soortnamen (dieren) blijven onvertaald — die
 * zijn al Latijn. Vereist een DEEPL_API_KEY in config.php.
 */
require __DIR__.'/inc.php';

// Korte categorienamen zijn zonder context dubbelzinnig voor DeepL (bv.
// "Vissen" als diercategorie vs. de werkwoordsvorm "to fish").
const AT_CATEGORY_TRANSLATE_CONTEXT = 'Diercategorie op een website over dierentuinen, zoals Zoogdieren, Vogels, Reptielen.';

$done = false;
$stats = ['translated' => 0, 'alreadyHad' => 0, 'skippedNoText' => 0];
$noKey = deepl_api_key() === '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && !$noKey){
    csrf_verify();

    $rows = db()->query('SELECT id, title, description FROM categories')->fetchAll();
    foreach($rows as $row){
        $hadTitle = pb_has_column('categories','title_en');
        $st = $hadTitle ? db()->prepare('SELECT title_en, description_en FROM categories WHERE id=?') : null;
        $existing = ['title_en' => null, 'description_en' => null];
        if($st){ $st->execute([$row['id']]); $existing = $st->fetch() ?: $existing; }

        $titleWasEmpty = empty($existing['title_en']);
        $descWasEmpty = empty($existing['description_en']);

        if(trim((string)$row['title']) !== '') auto_translate_field('categories', $row['id'], 'title', $row['title'], false, AT_CATEGORY_TRANSLATE_CONTEXT);
        if(trim((string)$row['description']) !== '') auto_translate_field('categories', $row['id'], 'description', $row['description']);

        if($titleWasEmpty && trim((string)$row['title']) !== '') $stats['translated']++;
        elseif(!$titleWasEmpty) $stats['alreadyHad']++;
        else $stats['skippedNoText']++;
    }

    $done = true;
}

admin_header(t('at_title'), '');
?>
<div class="a-card"><div class="a-card-pad">
<?php if($noKey): ?>
  <div class="notice"><?=e(t('at_no_key'))?></div>
<?php elseif($done): ?>
  <div class="notice">
    <?=sprintf(e(t('at_translated_summary')), $stats['translated'], $stats['alreadyHad'])?>
  </div>
  <p><a class="a-btn" href="content.php?type=category"><?=e(t('to_categories'))?></a> <a class="a-btn a-btn-ghost" href="../index.php?lang=en" target="_blank"><?=e(t('view_english_site'))?></a></p>
<?php else: ?>
  <h2 style="margin-top:0"><?=e(t('at_title'))?></h2>
  <p><?=e(t('at_explain'))?></p>
  <form method="post">
    <?=csrf_field()?>
    <button class="a-btn" type="submit"><?=e(t('at_add_btn'))?></button>
  </form>
<?php endif; ?>
</div></div>
<?php admin_footer(); ?>
