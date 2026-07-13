<?php
/**
 * One-time herstel-script voor de dieren-categorieboom. Vergelijkt de
 * volledige boom in de database met de kloppende structuur (dezelfde die
 * seed-taxonomy.php gebruikt om te importeren) en herstelt elke categorie
 * die per ongeluk op het hoofdniveau is komen te staan in plaats van
 * genest op de juiste plek — bv. na het per ongeluk verwijderen van een
 * bovenliggende categorie, waarbij kinderen naar boven verhuizen. Amfibieën,
 * Reptielen, Vissen, Vogels en Zoogdieren blijven zelf wél op het
 * hoofdniveau (dat is gewenst, rechtstreeks in de navigatie). Voegt ook
 * duplicaten van dezelfde titel/ouder-combinatie samen (kinderen + dieren
 * verhuizen mee, niets gaat verloren) en verwijdert een overbodige
 * "Gewervelde dieren"/"Gwervelden"-koepel. Veilig om opnieuw te draaien.
 */
require __DIR__.'/inc.php';

const FIX_WRAPPER_TITLES = ['Gewervelde dieren', 'Gwervelden'];
const FIX_TOP_LEVEL_TITLES = ['Amfibieën', 'Reptielen', 'Vissen', 'Vogels', 'Zoogdieren'];

$tree = require __DIR__.'/taxonomy-tree-data.php';

// Bouwt een titel => bovenliggende titel-kaart uit de kloppende boom (enkel
// voor niet-top-niveau categorieën — top-niveau titels staan er niet in).
function fix_build_parent_map($node, $parentTitle, &$map){
    foreach($node as $name => $value){
        if($parentTitle !== null) $map[$name] = $parentTitle;
        $isSpeciesList = is_array($value) && array_keys($value) === range(0, count($value) - 1);
        if(!$isSpeciesList){
            fix_build_parent_map($value, $name, $map);
        }
    }
}
$parentMap = [];
foreach($tree as $topTitle => $children){
    fix_build_parent_map($children, $topTitle, $parentMap);
}

$done = false;
$totalMerged = 0;
$totalReparented = 0;
$wrapperRemoved = false;

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    csrf_verify();

    // 1. Verwijder de overbodige koepel-categorie(ën) — haar kinderen
    // verhuizen eerst naar het hoofdniveau (worden daarna in stap 2 op hun
    // juiste plek gezet, samen met alle andere verdwaalde categorieën).
    $ph = implode(',', array_fill(0, count(FIX_WRAPPER_TITLES), '?'));
    $st = db()->prepare("SELECT id FROM categories WHERE parent_id IS NULL AND title IN ($ph)");
    $st->execute(FIX_WRAPPER_TITLES);
    foreach($st->fetchAll() as $row){
        $wid = (int)$row['id'];
        db()->prepare('UPDATE categories SET parent_id=NULL WHERE parent_id=?')->execute([$wid]);
        db()->prepare('UPDATE animals SET category_id=NULL WHERE category_id=?')->execute([$wid]);
        db()->prepare('DELETE FROM categories WHERE id=?')->execute([$wid]);
        $wrapperRemoved = true;
    }

    // 2. Elke categorie die op het hoofdniveau staat maar volgens de boom
    // ergens genest hoort te zijn, verplaatsen we naar haar juiste ouder
    // (die zelf ook ergens op het hoofdniveau moet bestaan, anders wordt
    // ze hier gewoon overgeslagen — kan in een volgende ronde alsnog).
    $rows = db()->query('SELECT id, title FROM categories WHERE parent_id IS NULL')->fetchAll();
    foreach($rows as $row){
        $title = $row['title'];
        if(!isset($parentMap[$title])) continue; // hoort terecht op hoofdniveau, of onbekende titel
        $expectedParentTitle = $parentMap[$title];
        $pst = db()->prepare('SELECT id FROM categories WHERE title=? ORDER BY (parent_id IS NULL) ASC, id ASC LIMIT 1');
        $pst->execute([$expectedParentTitle]);
        $parentRow = $pst->fetch();
        if(!$parentRow) continue; // ouder bestaat nog niet, overslaan
        db()->prepare('UPDATE categories SET parent_id=? WHERE id=?')->execute([(int)$parentRow['id'], (int)$row['id']]);
        $totalReparented++;
    }

    // 3. Dubbele categorieën (zelfde titel + zelfde ouder) samenvoegen —
    // kinderen en dieren verhuizen naar de oudste, de rest wordt verwijderd.
    $dupSt = db()->query('SELECT title, parent_id, COUNT(*) c, GROUP_CONCAT(id ORDER BY id) ids FROM categories GROUP BY title, parent_id HAVING c > 1');
    foreach($dupSt->fetchAll() as $dupRow){
        $ids = array_map('intval', explode(',', $dupRow['ids']));
        $canonicalId = array_shift($ids);
        foreach($ids as $dupId){
            db()->prepare('UPDATE categories SET parent_id=? WHERE parent_id=?')->execute([$canonicalId, $dupId]);
            db()->prepare('UPDATE animals SET category_id=? WHERE category_id=?')->execute([$canonicalId, $dupId]);
            db()->prepare('DELETE FROM categories WHERE id=?')->execute([$dupId]);
            $totalMerged++;
        }
    }

    // 4. Alles publiceren zodat de herstelde structuur meteen zichtbaar is.
    db()->exec('UPDATE categories SET published=1 WHERE published=0');

    $done = true;
}

admin_header('Taxonomieboom herstellen', '');
?>
<div class="a-card"><div class="a-card-pad">
<?php if($done): ?>
  <div class="notice">Klaar. <?=$totalReparented?> categorie(ën) terug op hun juiste plek gezet, <?=$totalMerged?> dubbele categorie(ën) samengevoegd (niets is verloren gegaan)<?php if($wrapperRemoved): ?>, en de overbodige "Gewervelde dieren"/"Gwervelden"-koepel verwijderd<?php endif; ?>. Amfibieën, Reptielen, Vissen, Vogels en Zoogdieren staan elk apart rechtstreeks in de navigatie, met al hun sub- en sub-subcategorieën netjes genest eronder.</div>
  <p><a class="a-btn" href="content.php?type=category">Naar Categorieën</a> — controleer de boom. <a class="a-btn a-btn-ghost" href="../index.php" target="_blank">Bekijk de site</a></p>
<?php else: ?>
  <h2 style="margin-top:0">Taxonomieboom herstellen</h2>
  <p>Vergelijkt je huidige categorieën met de kloppende structuur en zet elke categorie die per ongeluk los op het hoofdniveau is komen te staan (bv. na het verwijderen van een bovenliggende categorie) weer op haar juiste, geneste plek — onder de juiste sub- en sub-subcategorie. Amfibieën, Reptielen, Vissen, Vogels en Zoogdieren blijven zelf gewoon op het hoofdniveau staan, rechtstreeks in de navigatie. Voegt ook dubbele categorieën samen (niets gaat verloren) en verwijdert een overbodige "Gewervelde dieren"-koepel als die nog bestaat. Veilig om meermaals te draaien.</p>
  <form method="post">
    <?=csrf_field()?>
    <button class="a-btn" type="submit">Herstellen</button>
  </form>
<?php endif; ?>
</div></div>
<?php admin_footer(); ?>
