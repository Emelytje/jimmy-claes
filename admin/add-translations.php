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

function at_column_exists($table, $column){
    $st = db()->prepare("SHOW COLUMNS FROM $table LIKE ?");
    $st->execute([$column]);
    return (bool)$st->fetch();
}
function at_ensure_column($table, $column, $ddl){
    if(!at_column_exists($table, $column)){
        db()->exec("ALTER TABLE $table ADD COLUMN $column $ddl");
        return true;
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

admin_header('Vertalingen toevoegen (NL/EN)', '');
?>
<div class="a-card"><div class="a-card-pad">
<?php if($done): ?>
  <div class="notice">
    Klaar.<?php if($stats['columnsAdded']): ?> Database aangepast voor tweetaligheid.<?php endif; ?>
    <?=$stats['translated']?> categorienaam/namen vertaald, <?=$stats['alreadyHad']?> hadden al een Engelse naam (ongemoeid gelaten)<?php if($stats['noMatch']): ?>, <?=$stats['noMatch']?> herkende ik niet uit de standaardboom (zelf aangemaakte categorie?) — die kan je los vertalen bij het bewerken van die categorie.<?php else: ?>.<?php endif; ?>
  </div>
  <p><a class="a-btn" href="content.php?type=category">Naar Categorieën</a> <a class="a-btn a-btn-ghost" href="../index.php?lang=en" target="_blank">Bekijk de Engelse site</a></p>
<?php else: ?>
  <h2 style="margin-top:0">Vertalingen toevoegen (NL/EN)</h2>
  <p>De site heeft nu een taalknop (NL/EN) rechtsboven. Deze knop hier vult automatisch de Engelse naam in voor elke categorie die overeenkomt met de standaard taxonomieboom (bv. "Vissen" → "Fish"). Dieren-titels worden niet aangepast — dat zijn al Latijnse soortnamen. Veilig om te herdraaien: bestaande Engelse namen (ook zelf ingevulde) worden nooit overschreven.</p>
  <form method="post">
    <?=csrf_field()?>
    <button class="a-btn" type="submit">Vertalingen toevoegen</button>
  </form>
<?php endif; ?>
</div></div>
<?php admin_footer(); ?>
