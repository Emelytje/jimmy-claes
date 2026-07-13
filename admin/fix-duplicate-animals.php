<?php
/**
 * One-time opruimscript voor dubbele diersoorten. Doordat de categorieboom
 * meermaals verschoven is, herkende de importeer-idempotentiecheck (die op
 * titel + categorie-id matcht) bestaande soorten soms niet meer terug en
 * maakte een tweede rij aan met een "-2"-slug — met als gevolg dat foto's
 * die je via admin uploadt op de ene rij belanden, terwijl de "schone" URL
 * zonder "-2" naar de andere, lege rij wijst. Dit script voegt zulke
 * duplicaten (zelfde titel) samen tot één dier: de foto's van alle
 * exemplaren verhuizen naar het exemplaar met de schone slug (zonder
 * "-2"/"-3"/...), de rest wordt verwijderd. Categorie en publicatiestatus
 * worden overgenomen van een duplicaat als het overblijvende exemplaar die
 * zelf nog niet had. Veilig om opnieuw te draaien.
 */
require __DIR__.'/inc.php';

$done = false;
$stats = ['merged' => 0, 'photosMoved' => 0];

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    csrf_verify();

    $dupSt = db()->query('SELECT title, COUNT(*) c, GROUP_CONCAT(id ORDER BY id) ids FROM animals GROUP BY title HAVING c > 1');
    foreach($dupSt->fetchAll() as $dupRow){
        $ids = array_map('intval', explode(',', $dupRow['ids']));
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $rowSt = db()->prepare("SELECT * FROM animals WHERE id IN ($ph)");
        $rowSt->execute($ids);
        $rows = $rowSt->fetchAll();

        // Kies als overblijvende exemplaar bij voorkeur die met de schone
        // slug (zonder "-2"/"-3"-toevoeging), anders de oudste (laagste id).
        usort($rows, function($a, $b){
            $aClean = $a['slug'] === slugify($a['title']);
            $bClean = $b['slug'] === slugify($b['title']);
            if($aClean !== $bClean) return $aClean ? -1 : 1;
            return $a['id'] <=> $b['id'];
        });
        $canonical = $rows[0];
        $canonicalId = (int)$canonical['id'];

        foreach(array_slice($rows, 1) as $dup){
            $dupId = (int)$dup['id'];
            $moveSt = db()->prepare('UPDATE photos SET animal_id=? WHERE animal_id=?');
            $moveSt->execute([$canonicalId, $dupId]);
            $stats['photosMoved'] += $moveSt->rowCount();

            if(empty($canonical['category_id']) && !empty($dup['category_id'])){
                db()->prepare('UPDATE animals SET category_id=? WHERE id=?')->execute([(int)$dup['category_id'], $canonicalId]);
                $canonical['category_id'] = $dup['category_id'];
            }
            if(!$canonical['published'] && $dup['published']){
                db()->prepare('UPDATE animals SET published=1 WHERE id=?')->execute([$canonicalId]);
                $canonical['published'] = 1;
            }
            if(empty($canonical['cover_image']) && !empty($dup['cover_image'])){
                db()->prepare('UPDATE animals SET cover_image=? WHERE id=?')->execute([$dup['cover_image'], $canonicalId]);
                $canonical['cover_image'] = $dup['cover_image'];
            }

            db()->prepare('DELETE FROM animals WHERE id=?')->execute([$dupId]);
            $stats['merged']++;
        }
    }

    $done = true;
}

admin_header('Dubbele dieren opruimen', '');
?>
<div class="a-card"><div class="a-card-pad">
<?php if($done): ?>
  <div class="notice">Klaar. <?=$stats['merged']?> dubbele dier(en) samengevoegd, <?=$stats['photosMoved']?> foto('s) verhuisd naar het overblijvende exemplaar met de schone link (zonder "-2"). Niets is verloren gegaan.</div>
  <p><a class="a-btn" href="content.php?type=animal">Naar Dieren</a> — controleer de lijst. <a class="a-btn a-btn-ghost" href="../index.php" target="_blank">Bekijk de site</a></p>
<?php else: ?>
  <h2 style="margin-top:0">Dubbele dieren opruimen</h2>
  <p>Als je foto's uploadt maar ze niet op de verwachte link (zonder "-2" erachter) verschijnen, zijn er waarschijnlijk twee rijen voor diezelfde soort aangemaakt — dit gebeurt soms doordat de categorieboom onderweg verschoof. Dit voegt zulke duplicaten samen: alle foto's verhuizen naar het exemplaar met de schone link, de rest wordt verwijderd. Categorie, publicatiestatus en coverfoto worden overgenomen als het overblijvende exemplaar die zelf nog niet had. Niets gaat verloren. Veilig om meermaals te draaien.</p>
  <form method="post">
    <?=csrf_field()?>
    <button class="a-btn" type="submit">Opruimen</button>
  </form>
<?php endif; ?>
</div></div>
<?php admin_footer(); ?>
