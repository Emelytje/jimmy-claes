<?php
/**
 * Zet de site tweetalig: voegt title_en/description_en kolommen toe aan
 * categories en animals (zelf-herstellend, veilig te herdraaien), en vult
 * de Engelse categorienaam automatisch in voor elke categorie die matcht
 * met de vaste vertaaltabel (category-translations.php). Bestaande
 * handmatige *_en-waarden worden nooit overschreven. Soortnamen (dieren)
 * blijven onvertaald — die zijn al Latijn.
 */
require __DIR__.'/inc.php';

// Vaste lijst toegelaten tabel/kolomnamen — $table/$column komen nooit uit
// een request, maar dit blokkeert per ongeluk hergebruik met request-data
// later, en houdt de SQL-interpolatie hieronder aantoonbaar veilig.
const AT_ALLOWED_TABLES = ['categories', 'animals'];
const AT_ALLOWED_COLUMNS = ['title_en', 'description_en'];

function at_column_exists($table, $column){
    if(!in_array($table, AT_ALLOWED_TABLES, true) || !in_array($column, AT_ALLOWED_COLUMNS, true)) return false;
    $st = db()->prepare("SHOW COLUMNS FROM $table LIKE ?");
    $st->execute([$column]);
    return (bool)$st->fetch();
}
function at_ensure_column($table, $column, $ddl){
    if(!in_array($table, AT_ALLOWED_TABLES, true) || !in_array($column, AT_ALLOWED_COLUMNS, true)) return false;
    if(!at_column_exists($table, $column)){
        try{
            db()->exec("ALTER TABLE $table ADD COLUMN $column $ddl");
            return true;
        }catch(Exception $e){
            // Kolom bestaat intussen al (dubbele klik/race), of hosting laat
            // geen ALTER toe — geen fatale fout, gewoon negeren en verder
            // met wat er al kan.
            return false;
        }
    }
    return false;
}

// Categorienamen die bewust dubbel voorkomen (zelfde titel, andere plek in
// de boom) hebben een andere Engelse vertaling per bovenliggende categorie —
// een simpele titel->vertaling-tabel kan dat niet uitdrukken.
const CATEGORY_TRANSLATION_OVERRIDES = [
    "Lori's" => ['Papegaaiachtigen' => 'Lorikeets', 'Primaten' => 'Lorises'],
];

$done = false;
$stats = ['columnsAdded' => 0, 'translated' => 0, 'alreadyHad' => 0, 'noMatch' => 0];

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    csrf_verify();

    foreach([
        ['categories', 'title_en', 'VARCHAR(160) DEFAULT NULL'],
        ['categories', 'description_en', 'TEXT DEFAULT NULL'],
        ['animals', 'title_en', 'VARCHAR(160) DEFAULT NULL'],
        ['animals', 'description_en', 'TEXT DEFAULT NULL'],
    ] as [$table, $col, $ddl]){
        if(at_ensure_column($table, $col, $ddl)) $stats['columnsAdded']++;
    }

    $dict = require __DIR__.'/category-translations.php';
    $catNames = [];
    foreach(db()->query('SELECT id, title FROM categories') as $c){ $catNames[(int)$c['id']] = $c['title']; }

    $rows = db()->query('SELECT id, title, parent_id, title_en FROM categories')->fetchAll();
    foreach($rows as $row){
        if(!empty($row['title_en'])){ $stats['alreadyHad']++; continue; }
        $english = null;
        if(isset(CATEGORY_TRANSLATION_OVERRIDES[$row['title']])){
            $parentTitle = $row['parent_id'] ? ($catNames[(int)$row['parent_id']] ?? '') : '';
            $english = CATEGORY_TRANSLATION_OVERRIDES[$row['title']][$parentTitle] ?? null;
        }
        if($english === null){
            $english = $dict[$row['title']] ?? null;
        }
        if($english === null){ $stats['noMatch']++; continue; }
        db()->prepare('UPDATE categories SET title_en=? WHERE id=?')->execute([$english, $row['id']]);
        $stats['translated']++;
    }

    $done = true;
}

admin_header(t('at_title'), '');
?>
<div class="a-card"><div class="a-card-pad">
<?php if($done): ?>
  <div class="notice">
    <?=e(t('at_done'))?><?php if($stats['columnsAdded']): ?><?=e(t('at_db_updated'))?><?php endif; ?>
    <?=sprintf(e(t('at_translated_summary')), $stats['translated'], $stats['alreadyHad'])?><?php if($stats['noMatch']): ?><?=sprintf(e(t('at_nomatch_summary')), $stats['noMatch'])?><?php else: ?>.<?php endif; ?>
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
