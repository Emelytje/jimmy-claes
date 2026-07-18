<?php
/**
 * One-time setup: vult de "Over ons" / "Over Jimmy"-pagina (slug over-ons)
 * met een kant-en-klare, professioneel ogende opzet (hero, verhaal-tekst,
 * citaat, soortenteller, knop). Bewust zonder foto-blok: een leeg foto-vak
 * rekt door row-stretching op tot een grote lege ruimte — voeg zelf een
 * Foto- of Rij-blok toe via de editor zodra er een foto van Jimmy is.
 * Visit once while logged in as admin. Safe to re-run — het werkt de
 * bestaande pagina met slug "over-ons" bij, of maakt ze aan als ze nog
 * niet bestaat.
 */
require __DIR__.'/inc.php';

const SEED_OJ_SLUG = 'over-ons';

function oj_default_settings($over = []){
    return array_merge([
        'fontFamily'=>'', 'fontSize'=>'', 'textColor'=>'', 'bgColor'=>'',
        'align'=>'left', 'paddingY'=>56, 'paddingX'=>24, 'radius'=>0,
        'shadow'=>'none', 'animation'=>'fade-up',
    ], $over);
}
function oj_uid(){ return 'b_'.substr(bin2hex(random_bytes(4)), 0, 8); }

$done = false;
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_verify();

    $bioHtml = '<p>Al van jongs af aan gefascineerd door dieren, trek ik met mijn camera de wereld rond op zoek naar zoveel mogelijk diersoorten — van bekende publiekstrekkers tot zeldzame soorten die je zelden op foto ziet.</p>'
        .'<p>Elke dierentuin en elk aquarium dat ik bezoek is een nieuwe kans om weer een soort toe te voegen aan mijn verzameling. Op deze website deel ik die verzameling: foto\'s per diersoort, aangevuld met waar ik elk dier gefotografeerd heb.</p>'
        .'<p><em>[Dit is voorbeeldtekst — vervang ze via de pagina-editor door je eigen verhaal.]</em></p>';

    $blocks = [
        [
            'id' => oj_uid(), 'type' => 'hero',
            'settings' => oj_default_settings(['align'=>'center','paddingY'=>'','paddingX'=>'','animation'=>'fade-in']),
            'data' => [
                'title' => 'Over Jimmy',
                'subtitle' => 'Fotograaf op reis langs dierentuinen en aquaria, op zoek naar zoveel mogelijk diersoorten.',
                'buttonText' => '', 'buttonHref' => '#', 'bgImage' => '', 'overlay' => 45,
            ],
        ],
        [
            'id' => oj_uid(), 'type' => 'title',
            'settings' => oj_default_settings(['align'=>'center']),
            'data' => ['text'=>'Mijn verhaal', 'level'=>'h2'],
        ],
        [
            'id' => oj_uid(), 'type' => 'text',
            'settings' => oj_default_settings(),
            'data' => ['html'=>$bioHtml],
        ],
        [
            'id' => oj_uid(), 'type' => 'divider',
            'settings' => oj_default_settings(),
            'data' => ['style'=>'line'],
        ],
        [
            'id' => oj_uid(), 'type' => 'quote',
            'settings' => oj_default_settings(['align'=>'center']),
            'data' => ['text'=>'Elke diersoort verdient het om gezien te worden.', 'author'=>'Jimmy Claes'],
        ],
        [
            'id' => oj_uid(), 'type' => 'species_progress',
            'settings' => oj_default_settings(['align'=>'center']),
            'data' => ['label'=>'diersoorten al gefotografeerd'],
        ],
        [
            'id' => oj_uid(), 'type' => 'button',
            'settings' => oj_default_settings(['align'=>'center']),
            'data' => ['text'=>'Bekijk alle diersoorten', 'href'=>'index.php', 'style'=>'solid', 'size'=>'lg'],
        ],
    ];
    $blocksJson = pb_encode_blocks($blocks);

    $existing = db()->prepare('SELECT id FROM pages WHERE slug=?');
    $existing->execute([SEED_OJ_SLUG]);
    $row = $existing->fetch();
    if($row){
        db()->prepare('UPDATE pages SET blocks=?, meta_title=?, meta_description=?, published=1 WHERE id=?')
            ->execute([$blocksJson, 'Over Jimmy', 'Maak kennis met Jimmy Claes, wildlife-fotograaf op zoek naar zoveel mogelijk diersoorten.', $row['id']]);
    } else {
        db()->prepare('INSERT INTO pages(title, slug, blocks, meta_title, meta_description, published, show_in_nav) VALUES(?,?,?,?,?,1,1)')
            ->execute(['Over ons', SEED_OJ_SLUG, $blocksJson, 'Over Jimmy', 'Maak kennis met Jimmy Claes, wildlife-fotograaf op zoek naar zoveel mogelijk diersoorten.']);
    }
    $done = true;
}

admin_header('Over Jimmy instellen', '');
?>
<div class="a-card"><div class="a-card-pad">
<?php if($done): ?>
  <div class="notice">Klaar. De "Over ons"-pagina is bijgewerkt met: een hero-sectie, een verhaal-tekst, een citaat, de soortenteller en een knop naar alle diersoorten.</div>
  <p><a class="a-btn" href="pages.php">Naar Pagina's</a> — open "Over ons" om je eigen verhaal in te vullen, en voeg via de editor zelf een foto toe zodra je die klaar hebt. Alles blijft net als elke andere pagina volledig bewerkbaar.</p>
<?php else: ?>
  <h2 style="margin-top:0">"Over Jimmy"-pagina instellen</h2>
  <p>Dit vult de pagina met slug <code>over-ons</code> (of maakt ze aan als ze nog niet bestaat) met een kant-en-klare, professioneel ogende opzet: een hero-sectie, een verhaal-tekst (met voorbeeldtekst, zelf aan te passen), een citaat, de soortenteller en een knop naar alle diersoorten. Bewust nog zonder foto — voeg er zelf een toe via de editor zodra je een foto van Jimmy klaar hebt. Veilig om opnieuw te draaien — overschrijft enkel deze pagina, niet je andere pagina's.</p>
  <form method="post">
    <?=csrf_field()?>
    <button class="a-btn" type="submit">Instellen</button>
  </form>
<?php endif; ?>
</div></div>
<?php admin_footer(); ?>
