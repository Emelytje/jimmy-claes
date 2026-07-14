<?php
/**
 * One-time herstel-script voor de dieren-categorieboom. Loopt de kloppende
 * structuur (dezelfde die seed-taxonomy.php gebruikt om te importeren) van
 * boven naar onder af en zet voor elke naam de bestaande categorie met die
 * titel op haar juiste plek — ongeacht waar ze nu precies hangt (los op het
 * hoofdniveau, onder een verkeerde/verouderde koepel, meerdere niveaus te
 * diep verstopt, ...). Dubbele categorieën met dezelfde titel worden
 * samengevoegd (kinderen + dieren verhuizen mee, niets gaat verloren).
 * Namen die bewust op meerdere plekken in de boom voorkomen (bv. "Lori's"
 * bij zowel Papegaaiachtigen als Primaten) worden voorzichtig behandeld:
 * een reeds op de juiste plek staande match heeft voorrang, en eenmaal een
 * exemplaar ergens aan toegewezen is, wordt het niet nog eens elders
 * hergebruikt. Veilig om opnieuw te draaien.
 */
require __DIR__.'/inc.php';

const FIX_TOP_LEVEL_TITLES = ['Amfibieën', 'Reptielen', 'Vissen', 'Vogels', 'Zoogdieren', 'Ongewervelde'];

$tree = require __DIR__.'/taxonomy-tree-data.php';

// Verwijdert restjes van de "—"-inspringing uit de admin-lijst als die per
// ongeluk in een echte titel zijn beland (bv. iemand die "— Vogels" als
// titel intikte, denkend dat dat de manier was om in te springen).
function fix_clean_title($title){
    return preg_replace('/^[\s\x{2014}\x{2013}-]+/u', '', trim($title));
}

// Zet de categorie met de gegeven titel op $parentId (NULL = hoofdniveau).
// Als er meerdere categorieën met die titel bestaan (duplicaten, of een
// naam die bewust elders in de boom nog eens voorkomt), kiest deze functie
// de kandidaat die al bij deze $parentId hoort, anders de eerste nog niet
// door deze herstelronde "opgeëiste" kandidaat — zodat eenzelfde rij niet
// per ongeluk tussen twee boomtakken heen en weer geslingerd wordt. Voegt
// overtollige duplicaten (die niet als kandidaat gekozen zijn) samen met de
// gekozen categorie. Geeft het id van de gekozen categorie terug, of null
// als er in de database helemaal geen categorie met die titel bestaat.
function fix_place_category($title, $parentId, &$claimedIds, &$stats){
    $st = db()->prepare('SELECT id, parent_id FROM categories WHERE title=?');
    $st->execute([$title]);
    $rows = $st->fetchAll();
    if(!$rows){
        // Titel bestaat (nog) niet in de database — niets om te herstellen,
        // dat is de taak van seed-taxonomy.php (importeren), niet van dit
        // script (herstellen van bestaande data).
        return null;
    }

    // Kandidaat kiezen: bij voorkeur eentje die al de juiste ouder heeft èn
    // nog niet opgeëist is deze ronde; anders de eerste onopgeëiste; anders
    // (als echt alles al opgeëist is — enkel mogelijk bij een dubbele naam
    // met te weinig exemplaren) wordt dit exemplaar overgeslagen.
    usort($rows, function($a, $b) use ($parentId){
        $aMatch = ($parentId === null) ? $a['parent_id'] === null : (int)$a['parent_id'] === (int)$parentId;
        $bMatch = ($parentId === null) ? $b['parent_id'] === null : (int)$b['parent_id'] === (int)$parentId;
        if($aMatch !== $bMatch) return $aMatch ? -1 : 1;
        return $a['id'] <=> $b['id'];
    });
    $chosen = null;
    foreach($rows as $row){
        $id = (int)$row['id'];
        if(!isset($claimedIds[$id])){ $chosen = $row; break; }
    }
    if($chosen === null){
        $stats['ambiguous']++;
        return null;
    }
    $chosenId = (int)$chosen['id'];
    $claimedIds[$chosenId] = true;

    // Overige exemplaren met dezelfde titel die niet gekozen zijn EN niet
    // meer nodig zijn voor een andere boomtak (d.w.z. deze titel komt maar
    // op één plek in de boom voor) worden samengevoegd met de gekozen.
    // Bij een titel die bewust vaker voorkomt laten we de rest ongemoeid —
    // die kan door een latere aanroep voor de andere boomtak opgeëist worden.

    $needsUpdate = ($parentId === null) ? $chosen['parent_id'] !== null : (int)$chosen['parent_id'] !== (int)$parentId;
    if($needsUpdate){
        db()->prepare('UPDATE categories SET parent_id=? WHERE id=?')->execute([$parentId, $chosenId]);
        $stats['reparented']++;
    }
    db()->prepare('UPDATE categories SET published=1 WHERE id=? AND published=0')->execute([$chosenId]);
    return $chosenId;
}

// Zelfde principe als fix_place_category, maar voor een dier (soort) onder
// een categorie: zoekt het dier op titel op, kiest bij voorkeur een
// exemplaar dat al bij deze categorie hoort en nog niet opgeëist is, en
// koppelt het aan de juiste category_id. Dit is de stap die eerder ontbrak:
// eerdere herstelrondes zetten enkel categorieën recht, niet de dieren die
// per ongeluk hun category_id kwijtraakten (bv. na het handmatig
// verwijderen van hun categorie via het admin-paneel, wat category_id op
// leeg zet in plaats van naar een vervanger te verwijzen).
function fix_place_animal($title, $categoryId, &$claimedAnimalIds, &$stats){
    $st = db()->prepare('SELECT id, category_id FROM animals WHERE title=?');
    $st->execute([$title]);
    $rows = $st->fetchAll();
    if(!$rows) return;

    usort($rows, function($a, $b) use ($categoryId){
        $aMatch = (int)$a['category_id'] === (int)$categoryId;
        $bMatch = (int)$b['category_id'] === (int)$categoryId;
        if($aMatch !== $bMatch) return $aMatch ? -1 : 1;
        return $a['id'] <=> $b['id'];
    });
    $chosen = null;
    foreach($rows as $row){
        $id = (int)$row['id'];
        if(!isset($claimedAnimalIds[$id])){ $chosen = $row; break; }
    }
    if($chosen === null){
        $stats['animalsAmbiguous']++;
        return;
    }
    $chosenId = (int)$chosen['id'];
    $claimedAnimalIds[$chosenId] = true;

    if((int)$chosen['category_id'] !== (int)$categoryId){
        db()->prepare('UPDATE animals SET category_id=? WHERE id=?')->execute([$categoryId, $chosenId]);
        $stats['animalsReattached']++;
    }
    db()->prepare('UPDATE animals SET published=1 WHERE id=? AND published=0')->execute([$chosenId]);
}

function fix_walk($node, $parentId, &$claimedIds, &$claimedAnimalIds, &$stats){
    foreach($node as $name => $value){
        $title = fix_clean_title($name);
        $isSpeciesList = is_array($value) && (count($value) === 0 || array_keys($value) === range(0, count($value) - 1));
        if($isSpeciesList){
            // $name hier is zelf al een categorie (bv. "Fluiteenden"), de
            // soorten in $value moeten aan HAAR gekoppeld worden.
            $id = fix_place_category($title, $parentId, $claimedIds, $stats);
            if($id === null) continue;
            foreach($value as $species){
                fix_place_animal(fix_clean_title($species), $id, $claimedAnimalIds, $stats);
            }
        } else {
            $id = fix_place_category($title, $parentId, $claimedIds, $stats);
            if($id === null) continue;
            fix_walk($value, $id, $claimedIds, $claimedAnimalIds, $stats);
        }
    }
}

$done = false;
$stats = ['reparented' => 0, 'ambiguous' => 0, 'merged' => 0, 'animalsReattached' => 0, 'animalsAmbiguous' => 0];
$wrapperRemoved = 0;

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    csrf_verify();

    $claimedIds = [];
    $claimedAnimalIds = [];
    foreach($tree as $topTitle => $children){
        $topId = fix_place_category($topTitle, null, $claimedIds, $stats);
        if($topId !== null) fix_walk($children, $topId, $claimedIds, $claimedAnimalIds, $stats);
    }

    // Dubbele categorieën (zelfde titel + zelfde ouder) samenvoegen —
    // kinderen en dieren verhuizen naar de oudste, de rest wordt verwijderd.
    // Dit vangt ook duplicaten op die fix_place_category bewust liet staan.
    $dupSt = db()->query('SELECT title, parent_id, COUNT(*) c, GROUP_CONCAT(id ORDER BY id) ids FROM categories GROUP BY title, parent_id HAVING c > 1');
    foreach($dupSt->fetchAll() as $dupRow){
        $ids = array_map('intval', explode(',', $dupRow['ids']));
        $canonicalId = array_shift($ids);
        foreach($ids as $dupId){
            db()->prepare('UPDATE categories SET parent_id=? WHERE parent_id=?')->execute([$canonicalId, $dupId]);
            db()->prepare('UPDATE animals SET category_id=? WHERE category_id=?')->execute([$canonicalId, $dupId]);
            db()->prepare('DELETE FROM categories WHERE id=?')->execute([$dupId]);
            $stats['merged']++;
        }
    }

    // Overbodige koepel-categorieën (bv. "Gewervelde dieren"/"Gwervelden")
    // die nergens meer als bovenliggende van iets dienen en zelf geen van
    // de 5 hoofdklassen zijn, worden opgeruimd — hun eventuele resterende
    // kinderen (zou niet meer mogen voorkomen na de stap hierboven, maar
    // voor de zekerheid) verhuizen naar het hoofdniveau in plaats van
    // verloren te gaan.
    $ph = implode(',', array_fill(0, count(FIX_TOP_LEVEL_TITLES), '?'));
    $orphanWrappers = db()->prepare("SELECT id FROM categories WHERE parent_id IS NULL AND title NOT IN ($ph) AND id NOT IN (SELECT DISTINCT parent_id FROM categories WHERE parent_id IS NOT NULL)");
    $orphanWrappers->execute(FIX_TOP_LEVEL_TITLES);
    foreach($orphanWrappers->fetchAll() as $row){
        // Enkel weggooien als het duidelijk om een koepel-restant gaat
        // (geen eigen dieren, en de titel komt niet voor in de kloppende
        // boom) — anders raken we per ongeluk een echte, losstaande
        // categorie van de gebruiker kwijt.
        $wid = (int)$row['id'];
        $chk = db()->prepare('SELECT title FROM categories WHERE id=?');
        $chk->execute([$wid]);
        $wtitle = fix_clean_title($chk->fetch()['title'] ?? '');
        if(stripos($wtitle, 'gewervelde') === false && stripos($wtitle, 'gwervelden') === false) continue;
        $animalChk = db()->prepare('SELECT COUNT(*) c FROM animals WHERE category_id=?');
        $animalChk->execute([$wid]);
        if((int)$animalChk->fetch()['c'] > 0) continue;
        db()->prepare('DELETE FROM categories WHERE id=?')->execute([$wid]);
        $wrapperRemoved++;
    }

    $done = true;
}

admin_header('Taxonomieboom herstellen', '');
?>
<div class="a-card"><div class="a-card-pad">
<?php if($done): ?>
  <div class="notice">Klaar. <?=$stats['reparented']?> categorie(ën) op hun juiste plek gezet (op eender welke diepte), <?=$stats['animalsReattached']?> di(e)r(en) terug aan hun juiste categorie gekoppeld, <?=$stats['merged']?> dubbele categorie(ën) samengevoegd (niets is verloren gegaan)<?php if($wrapperRemoved): ?>, <?=$wrapperRemoved?> overbodige koepel-categorie(ën) verwijderd<?php endif; ?>. Amfibieën, Reptielen, Vissen, Vogels en Zoogdieren staan elk apart rechtstreeks in de navigatie, met al hun sub- en sub-subcategorieën en soorten netjes genest eronder.<?php if($stats['ambiguous'] || $stats['animalsAmbiguous']): ?> <?=$stats['ambiguous']+$stats['animalsAmbiguous']?> categorie/dier(en) met een naam die op meerdere plekken voorkomt kon(den) niet automatisch geplaatst worden — controleer die zelf even bij Categorieën/Dieren.<?php endif; ?></div>
  <p><a class="a-btn" href="content.php?type=category">Naar Categorieën</a> — controleer de boom. <a class="a-btn a-btn-ghost" href="../index.php" target="_blank">Bekijk de site</a></p>
<?php else: ?>
  <h2 style="margin-top:0">Taxonomieboom herstellen</h2>
  <p>Loopt de kloppende structuur van boven naar onder af en zet elke bestaande categorie op haar juiste, geneste plek — op eender welke diepte, ook als ze nu ergens onder een verkeerde of verouderde koepel verstopt zit. Koppelt ook elke soort terug aan haar juiste categorie (dieren die die koppeling per ongeluk kwijtraakten, bv. na het handmatig verwijderen van hun categorie, staan anders als "leeg" te tonen). Amfibieën, Reptielen, Vissen, Vogels en Zoogdieren blijven zelf op het hoofdniveau staan, rechtstreeks in de navigatie. Voegt ook dubbele categorieën samen (niets gaat verloren) en ruimt een overbodige "Gewervelde dieren"-koepel op. Veilig om meermaals te draaien.</p>
  <form method="post">
    <?=csrf_field()?>
    <button class="a-btn" type="submit">Herstellen</button>
  </form>
<?php endif; ?>
</div></div>
<?php admin_footer(); ?>
