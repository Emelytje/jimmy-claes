<?php
/**
 * One-time bulk import for the full vertebrates taxonomy (Amfibieën,
 * Reptielen, Vissen, Vogels, Zoogdieren), per the PDF hierarchy. Visit this
 * page once while logged in as admin; it creates the category tree and each
 * species as a draft "Dier" ready for photos. Safe to re-run — existing
 * categories/animals (matched on title + parent) are skipped, nothing is
 * duplicated or overwritten.
 */
require __DIR__.'/inc.php';

$tree = require __DIR__.'/taxonomy-tree-data.php';

function seed_unique_slug($table, $baseSlug){
    $slug = $baseSlug; $i = 2;
    $chk = db()->prepare("SELECT COUNT(*) c FROM $table WHERE slug=?");
    while(true){
        $chk->execute([$slug]);
        if((int)$chk->fetch()['c'] === 0) return $slug;
        $slug = $baseSlug.'-'.$i; $i++;
    }
}

// A leaf is a plain (non-associative / numerically indexed) array — its
// entries are species names, not further category names.
function seed_is_species_list($arr){
    if(!is_array($arr)) return false;
    return array_keys($arr) === range(0, count($arr)-1);
}

$stats = ['categories'=>0, 'categories_skipped'=>0, 'animals'=>0, 'animals_skipped'=>0];

function seed_walk($node, $parentId, &$stats){
    foreach($node as $name => $value){
        // find existing category with this title+parent first (idempotent re-runs)
        $st = db()->prepare('SELECT id FROM categories WHERE title=? AND '.($parentId===null?'parent_id IS NULL':'parent_id=?'));
        $params = $parentId===null ? [$name] : [$name, $parentId];
        $st->execute($params);
        $existing = $st->fetch();
        if($existing){
            $catId = (int)$existing['id'];
            $stats['categories_skipped']++;
        } else {
            $slug = seed_unique_slug('categories', slugify($name));
            $ins = db()->prepare('INSERT INTO categories(title, slug, parent_id, blocks, published) VALUES(?,?,?,?,0)');
            $ins->execute([$name, $slug, $parentId, '[]']);
            $catId = (int)db()->lastInsertId();
            $stats['categories']++;
        }

        if(seed_is_species_list($value)){
            foreach($value as $species){
                $species = trim($species);
                if($species === '') continue;
                $chk = db()->prepare('SELECT id FROM animals WHERE title=? AND category_id=?');
                $chk->execute([$species, $catId]);
                if($chk->fetch()){ $stats['animals_skipped']++; continue; }
                $slug = seed_unique_slug('animals', slugify($species));
                $ins = db()->prepare('INSERT INTO animals(title, slug, blocks, published, category_id) VALUES(?,?,?,0,?)');
                $ins->execute([$species, $slug, '[]', $catId]);
                $stats['animals']++;
            }
        } else {
            seed_walk($value, $catId, $stats);
        }
    }
}

$done = false;
$published = 0;
$publishedAnimals = 0;
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_verify();
    $action = $_POST['action'] ?? 'import';
    if($action === 'publish_categories'){
        $published = db()->exec('UPDATE categories SET published=1 WHERE published=0');
    } elseif($action === 'publish_all'){
        $published = db()->exec('UPDATE categories SET published=1 WHERE published=0');
        $publishedAnimals = db()->exec('UPDATE animals SET published=1 WHERE published=0');
    } else {
        seed_walk($tree, null, $stats);
        $done = true;
    }
}

admin_header('Taxonomie importeren', '');
?>
<div class="a-card"><div class="a-card-pad">
<?php if($published || $publishedAnimals): ?>
  <div class="notice"><?=$published?> categorieën<?php if($publishedAnimals): ?> en <?=$publishedAnimals?> dieren<?php endif; ?> gepubliceerd. Alles staat nu live op de site.</div>
<?php endif; ?>
<?php if($done): ?>
  <div class="notice">Klaar. <?=$stats['categories']?> nieuwe categorieën aangemaakt (<?=$stats['categories_skipped']?> bestonden al), <?=$stats['animals']?> nieuwe dieren aangemaakt als concept (<?=$stats['animals_skipped']?> bestonden al).</div>
  <p>Standaard blijven nieuwe soorten concept (nog geen foto). Klik hieronder op "Alles publiceren" om de hele boom — categorieën én soorten — meteen live te zetten, ook zonder dat er al foto's op staan.</p>
<?php else: ?>
  <h2 style="margin-top:0">Taxonomie importeren (Amfibieën, Reptielen, Vissen, Vogels, Zoogdieren)</h2>
  <p>Dit maakt in één keer de volledige categorieboom aan zoals in de PDF, plus elke soort als een nieuw concept-dier (categorie toegekend, nog geen foto — dat doe je zelf na dit importeren). Bestaande categorieën/dieren met dezelfde naam worden overgeslagen, dus dit is veilig om nogmaals te draaien — al eerder geïmporteerde soorten (amfibieën, slangen) blijven ongemoeid, enkel de nieuwe takken worden toegevoegd.</p>
  <form method="post">
    <?=csrf_field()?>
    <input type="hidden" name="action" value="import">
    <button class="a-btn" type="submit">Importeren</button>
  </form>
<?php endif; ?>
</div></div>
<?php if($done || $published || $publishedAnimals): ?>
<div class="a-card"><div class="a-card-pad">
  <form method="post" style="display:inline">
    <?=csrf_field()?>
    <input type="hidden" name="action" value="publish_all">
    <button class="a-btn" type="submit">Alles publiceren (categorieën + dieren)</button>
  </form>
  <form method="post" style="display:inline">
    <?=csrf_field()?>
    <input type="hidden" name="action" value="publish_categories">
    <button class="a-btn a-btn-ghost" type="submit">Enkel categorieën publiceren</button>
  </form>
  <a class="a-btn a-btn-ghost" href="content.php?type=category">Naar Categorieën</a>
  <a class="a-btn a-btn-ghost" href="content.php?type=animal">Naar Dieren</a>
</div></div>
<?php endif; ?>
<?php admin_footer(); ?>
