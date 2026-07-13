<?php
/**
 * One-time herstel-script voor de dieren-categorieboom. Zet Amfibieën,
 * Reptielen, Vissen, Vogels en Zoogdieren elk als eigen, aparte
 * hoofdcategorie (rechtstreeks in de navigatie, niet onder een
 * "Gewervelde dieren"-koepel), voegt duplicaten van diezelfde titels samen
 * (kinderen + dieren verhuizen mee, niets gaat verloren) en verwijdert de
 * overbodige "Gewervelde dieren"/"Gwervelden"-koepel zelf (haar eigen
 * kinderen, als die er nog onverwacht zijn, verhuizen dan naar het
 * hoofdniveau in plaats van verloren te gaan). Veilig om opnieuw te draaien.
 */
require __DIR__.'/inc.php';

const FIX_WRAPPER_TITLES = ['Gewervelde dieren', 'Gwervelden'];
const FIX_CLASS_TITLES = ['Amfibieën', 'Reptielen', 'Vissen', 'Vogels', 'Zoogdieren'];

// Voegt alle categorieën met een van de gegeven titels samen tot één
// categorie (kinderen + dieren verhuizen mee naar de overblijvende), zet
// die op $forceParentId (of laat ongemoeid als null), en geeft het aantal
// samengevoegde duplicaten + het id van de overgebleven categorie terug.
function fix_merge_by_title($titles, $forceParentId = null){
    $ph = implode(',', array_fill(0, count($titles), '?'));
    $st = db()->prepare("SELECT id, title, parent_id FROM categories WHERE title IN ($ph)");
    $st->execute($titles);
    $rows = $st->fetchAll();
    if(!$rows){
        return ['id' => null, 'merged' => 0];
    }
    $preferredTitle = $titles[0];
    usort($rows, function($a, $b) use ($forceParentId, $preferredTitle){
        $aTitle = $a['title'] === $preferredTitle; $bTitle = $b['title'] === $preferredTitle;
        if($aTitle !== $bTitle) return $aTitle ? -1 : 1;
        $aMatch = $forceParentId !== null && (int)$a['parent_id'] === $forceParentId;
        $bMatch = $forceParentId !== null && (int)$b['parent_id'] === $forceParentId;
        if($aMatch !== $bMatch) return $aMatch ? -1 : 1;
        return $a['id'] <=> $b['id'];
    });
    $canonicalId = (int)$rows[0]['id'];
    $merged = 0;
    for($i = 1; $i < count($rows); $i++){
        $dupId = (int)$rows[$i]['id'];
        if($dupId === $canonicalId) continue;
        db()->prepare('UPDATE categories SET parent_id=? WHERE parent_id=?')->execute([$canonicalId, $dupId]);
        db()->prepare('UPDATE animals SET category_id=? WHERE category_id=?')->execute([$canonicalId, $dupId]);
        db()->prepare('DELETE FROM categories WHERE id=?')->execute([$dupId]);
        $merged++;
    }
    $setParent = $forceParentId !== null ? ', parent_id=?' : '';
    $params = [$preferredTitle];
    if($forceParentId !== null) $params[] = $forceParentId;
    $params[] = $canonicalId;
    db()->prepare("UPDATE categories SET title=?, published=1$setParent WHERE id=?")->execute($params);
    return ['id' => $canonicalId, 'merged' => $merged];
}

$done = false;
$totalMerged = 0;
$wrapperRemoved = false;
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    csrf_verify();

    // Elke klasse (Amfibieën/Reptielen/...) wordt zelf een hoofdcategorie
    // (parent_id NULL), duplicaten van dezelfde titel samengevoegd.
    foreach(FIX_CLASS_TITLES as $title){
        $r = fix_merge_by_title([$title], null);
        $totalMerged += $r['merged'];
    }

    // De koepel zelf ("Gewervelde dieren"/"Gwervelden") is niet meer
    // gewenst als categorie — eventuele onverwachte overige kinderen
    // verhuizen naar het hoofdniveau (net als bij een gewone verwijdering
    // via het admin-paneel), daarna wordt de koepel weggegooid.
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

    $done = true;
}

admin_header('Taxonomieboom herstellen', '');
?>
<div class="a-card"><div class="a-card-pad">
<?php if($done): ?>
  <div class="notice">Klaar. <?=$totalMerged?> dubbele categorie(ën) samengevoegd (kinderen en dieren zijn meeverhuisd, niets is verloren gegaan)<?php if($wrapperRemoved): ?>, en de overbodige "Gewervelde dieren"/"Gwervelden"-koepel is verwijderd<?php endif; ?>. Amfibieën, Reptielen, Vissen, Vogels en Zoogdieren staan nu elk apart rechtstreeks in de navigatie.</div>
  <p><a class="a-btn" href="content.php?type=category">Naar Categorieën</a> — controleer de boom. <a class="a-btn a-btn-ghost" href="../index.php" target="_blank">Bekijk de site</a></p>
<?php else: ?>
  <h2 style="margin-top:0">Taxonomieboom herstellen</h2>
  <p>Zet Amfibieën, Reptielen, Vissen, Vogels en Zoogdieren elk als eigen, aparte hoofdcategorie rechtstreeks in de navigatie (niet onder een gemeenschappelijke "Gewervelde dieren"-koepel), voegt eventuele duplicaten van diezelfde titels samen (niets gaat verloren, kinderen en dieren verhuizen automatisch mee) en verwijdert de overbodige koepelcategorie zelf. Veilig om meermaals te draaien.</p>
  <form method="post">
    <?=csrf_field()?>
    <button class="a-btn" type="submit">Herstellen</button>
  </form>
<?php endif; ?>
</div></div>
<?php admin_footer(); ?>
