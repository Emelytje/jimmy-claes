<?php
/**
 * One-time bulk import for the vertebrates taxonomy (part 1: amfibieën +
 * reptielen > slangen, per the PDF hierarchy). Visit this page once while
 * logged in as admin; it creates the category tree and each species as a
 * draft "Dier" ready for photos. Safe to re-run — existing slugs are
 * skipped, nothing is duplicated or overwritten.
 */
require __DIR__.'/inc.php';

// name => [] (leaf category, optionally with species as a plain list)
//      or name => [subcategory tree...] (associative = more categories)
$tree = [
    'Gewervelde dieren' => [
        'Amfibieën' => [
            'Kikkers' => [
                'Aziatische hoornkikkers' => [],
                'Boomkikkers' => [
                    'Triprion spinosus','Trachycephalus resinifictrix','Phyllomedusa tomopterna',
                    'Phyllomedusa sauvagii','Phyllomedusa bicolor','Litoria caerulea','Cruziohyla craspedopus',
                ],
                'Echte kikkers' => ['Lithobates catesbeianus'],
                'Gouden kikkers' => ['Mantella aurantiaca'],
                'Padden' => ['Rhinella marina','Incilius coniferus','Aptelopus zeteki'],
                'Pijlgifkikkers' => [
                    'Phyliobates terribilis','Oophaga hustrionica','Dendrobates trinctorius azureus','Dendrobates leucomelas',
                ],
                'Schuimnestboomkikkers' => ['Rhacophorus dennysi'],
                'Smalbekkikkers' => ['Dyscophus guineti'],
                'Vuurbuikpadden' => ['Bombina orientalis'],
                'Zuid-Amerikaanse hoornkikkers' => ['Lepidobatrachus','Ceratophrys'],
            ],
            'Salamanders' => [
                'Echte salamanders' => ['Cynops orientalis'],
                'Molsalamanders' => ['Ambystoma mexicanum'],
            ],
            'Wormsalamanders' => [
                'Waterbewonende wormsalamanders' => ['Potomotyphlus'],
            ],
        ],
        'Reptielen' => [
            'Slangen' => [
                'Zandslangen' => ['Psammophis mossambicus','Malpolon insignitus'],
                'Waterslangen' => [
                    'Thamnophis sirtalis tetrataenia','Nerodia floridana','Natrix tessellata','Natrix natrix natrix','Natrix maura',
                ],
                'Python' => [
                    'Simalia boeleni','Simalia amethistina','Python regius','Python brongersmai','Python bivittatus',
                    'Python anchietae','Morelia viridis','Morelia spilota variegata','Morelia spilota cheynei',
                    'Morelia carinata','Morelia bredli','Morelia spilota spilota','Malayopython timoriensis',
                    'Malayopython reticulatus reticulatus','Liasis mackloti savuensis','Leiopython albertisii',
                    'Aspidites ramsayi','Apodora papuana',
                ],
                'Madagaskar slangen' => ['Langaha madagascariensis'],
                'Koraalslangachtigen' => [
                    'Oxyuranus microlepidotus','Ophiophagus hannah','Naja siamensis','Naja nivea',
                    'Naja nigricincta nigricincta','Naja naja','Naja melanoleuca','Naja kaouthia','Naja haje haje',
                    'Naja annulifera','Dendroaspis polylepis','Dendroaspis angusticeps','Acanthophis rugosus',
                    'Acanthophis anatarcticus',
                ],
                'Haakneusslangen' => ['Philodryas baroni','Hydrodynastes gigas','Heterodon nasicus'],
                'Groefkopadders' => [
                    'Trimeresurus venustus','Trimeresurus stejnegeri','Trimeresurus mcgregori','Trimeresurus insularis',
                    'Trimeresurus flavomaculatus','Trimeresurus albolabris','Sitrurus miliarius barbouri',
                    'Sistrurus miliarius','Sisturus catenatus','Protobothrops mangshanensis','Mixcoatlus melanurus',
                    'Lachesis muta muta','Lachesis melanocephala','Crotalus vegrandis','Crotalus tzabcan',
                    'Crotalus pyrrhus','Crotalus polystictus','Crotalus molossus molossus','Crotalus horridus',
                    'Crotalus cerastes','Crotalus catalinensis','Crotalus atrox','Crotalus ademteus',
                    'Craspedocephalus trigonocephalus','Bothrops moojeni','Bothrops asper','Bothriechis schlegelii cf',
                    'Atropoides mexicanus','Agkistrodon contrortix','Agkistrodon conanti','Agkistrodon bilineatus',
                ],
                'Echte gladde slangen' => [
                    'Zamenis situla','Zamenis longissimus','Spilotes pullatus','Pituophis melanoleucus mugitus',
                    'Phrynonax poecilonotus','Pantherophis spiloides','Pantherophis guttatus','Pantherophis alleghaniensis',
                    'Lampropeltis triangulum syspila','Lampropeltis triangulum hondurensis','Lampropeltis prymelana',
                    'Lampropeltis polyzona','Lampropeltis getula floridana','Lampropeltis getula californiae',
                    'Lampropeltis calligaster','Lampropeltis alterna','Lampropeltis abnorma','Gonysoma oxycephalum',
                    'Gonysoma boulengeri','Elaphe taeniura ridleyi','Elaphe taeniura friesei','Elaphe schrenckii',
                    'Elaphe quatuorlineata','Elaphe moelendorffi','Elaphe carinata carinata','Drymarchon melanurus unicolor',
                    'Drymarchon couperi','Dispholidus typus','Dasypeltis scabra','Boiga dendrophilla dendrophilla',
                ],
                'Echte adders' => [
                    'Vipera berus berus','Vipera ammodytes meridionalis','Vipera ammodytes ammodytes',
                    'Cerastes cerastes cerastes','Bitis rhinoceros','Bitis parviocula','Bitis nasicornis',
                    'Bitis gabionica','Bitis cornuta','Bitis arientans','Atheris squamigera','Atheris nitschei',
                ],
                "Boa's" => [
                    'Sazinia madagascariensis','Lichanura trivirgata gracia','Eunectes notaeus','Eunectes murinus',
                    'Eryx colubrinus loveridgei','Epicrates cenchria','Corallus hortulana','Corallus caninus',
                    'Corallus batesii','Corallus annulatus','Chilabothrus subflavus','Chilabothrus inoratus',
                    'Chilabothrus angulifer','Boa constrictor imperator','Boa constrictor constricto',
                    'Acrantophis madagascariensis','Acrantophis dumerlili',
                ],
            ],
            'Schildpadden' => [],
            'Krokodilachtigen' => [],
            'Hagedissen' => [],
        ],
        'Vissen' => [],
        'Vogels' => [],
        'Zoogdieren' => [],
    ],
];

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
  <h2 style="margin-top:0">Taxonomie importeren (deel 1: amfibieën + reptielen &gt; slangen)</h2>
  <p>Dit maakt in één keer de volledige categorieboom aan zoals in de PDF, plus elke soort als een nieuw concept-dier (categorie toegekend, nog geen foto — dat doe je zelf na dit importeren). Bestaande categorieën/dieren met dezelfde naam worden overgeslagen, dus dit is veilig om nogmaals te draaien.</p>
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
