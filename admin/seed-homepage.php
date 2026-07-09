<?php
/**
 * One-time setup: sea-blue color theme + a ready-to-go homepage (hero,
 * empty photo slideshow, recent-species showcase, photo counter). Visit
 * once while logged in as admin. Safe to re-run — it edits the existing
 * homepage page if one was already created by this script, instead of
 * creating duplicates.
 */
require __DIR__.'/inc.php';

const SEED_PRIMARY = '#0e6ba8';
const SEED_ACCENT = '#cfe9f0';
const SEED_MARKER_TITLE = 'Home';

function seed_default_settings($over = []){
    return array_merge([
        'fontFamily'=>'', 'fontSize'=>'', 'textColor'=>'', 'bgColor'=>'',
        'align'=>'left', 'paddingY'=>56, 'paddingX'=>24, 'radius'=>0,
        'shadow'=>'none', 'animation'=>'fade-up',
    ], $over);
}
function seed_uid(){ return 'b_'.substr(bin2hex(random_bytes(4)), 0, 8); }

$done = false;
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_verify();

    set_setting('primary_color', SEED_PRIMARY);
    set_setting('accent_color', SEED_ACCENT);
    set_setting('site_title', 'Jimbo Animal Species of the World');

    $blocks = [
        [
            'id' => seed_uid(), 'type' => 'hero',
            'settings' => seed_default_settings(['align'=>'center','paddingY'=>'','paddingX'=>'','animation'=>'fade-in']),
            'data' => [
                'title' => setting('site_title','Jimbo Animal Species of the World'),
                'subtitle' => 'Een fotografische duik in de dierenwereld — van diepzee tot jungle.',
                'buttonText' => 'Bekijk alle dieren',
                'buttonHref' => 'animals.php',
                'bgImage' => '', 'overlay' => 45,
            ],
        ],
        [
            'id' => seed_uid(), 'type' => 'slideshow',
            'settings' => seed_default_settings(),
            'data' => ['images'=>[], 'interval'=>5],
        ],
        [
            'id' => seed_uid(), 'type' => 'title',
            'settings' => seed_default_settings(['align'=>'center']),
            'data' => ['text'=>'Ontdek per categorie', 'level'=>'h2'],
        ],
        [
            'id' => seed_uid(), 'type' => 'categories_grid',
            'settings' => seed_default_settings(),
            'data' => [],
        ],
        [
            'id' => seed_uid(), 'type' => 'photocount',
            'settings' => seed_default_settings(['align'=>'center','bgColor'=>SEED_ACCENT]),
            'data' => ['label'=>"foto's op deze website"],
        ],
        [
            'id' => seed_uid(), 'type' => 'title',
            'settings' => seed_default_settings(['align'=>'center']),
            'data' => ['text'=>'Recent toegevoegd', 'level'=>'h2'],
        ],
        [
            'id' => seed_uid(), 'type' => 'recent',
            'settings' => seed_default_settings(),
            'data' => ['source'=>'animals', 'count'=>6],
        ],
    ];
    $blocksJson = pb_encode_blocks($blocks);

    $existing = db()->prepare('SELECT id FROM pages WHERE title=? AND is_homepage=1');
    $existing->execute([SEED_MARKER_TITLE]);
    $row = $existing->fetch();
    if($row){
        db()->prepare('UPDATE pages SET blocks=?, published=1, show_in_nav=0 WHERE id=?')->execute([$blocksJson, $row['id']]);
    } else {
        db()->exec('UPDATE pages SET is_homepage=0');
        $slug = 'home';
        $chk = db()->prepare('SELECT COUNT(*) c FROM pages WHERE slug=?');
        $chk->execute([$slug]);
        if((int)$chk->fetch()['c'] > 0) $slug = 'home-'.substr(bin2hex(random_bytes(2)),0,4);
        db()->prepare('INSERT INTO pages(title, slug, blocks, published, show_in_nav, is_homepage) VALUES(?,?,?,1,0,1)')
            ->execute([SEED_MARKER_TITLE, $slug, $blocksJson]);
    }
    $done = true;
}

admin_header('Homepage instellen', '');
?>
<div class="a-card"><div class="a-card-pad">
<?php if($done): ?>
  <div class="notice">Klaar. Sitenaam ingesteld op "Jimbo Animal Species of the World", kleurthema op zee-blauw, en de homepage is aangemaakt/bijgewerkt met: een hero-sectie, een lege fotoslideshow (klaar om foto's in te zetten), een fototeller, en een "recent toegevoegd"-overzicht.</div>
  <p><a class="a-btn" href="pages.php">Naar Pagina's</a> — open "Home" om foto's toe te voegen aan de slideshow en verder aan te passen. Alles blijft net als elke andere pagina volledig bewerkbaar.</p>
<?php else: ?>
  <h2 style="margin-top:0">Homepage + zee-thema instellen</h2>
  <p>Dit zet de hoofdkleur op <code><?=SEED_PRIMARY?></code> en de accentkleur op <code><?=SEED_ACCENT?></code> (zee-blauw thema — later gewoon aan te passen bij Site-instellingen), en maakt/werkt een pagina "Home" bij die als homepage is ingesteld met een hero, een lege fotoslideshow-kader (klaar voor jouw foto's), een fototeller en een "recent toegevoegd"-blok. Veilig om opnieuw te draaien — overschrijft enkel deze zelfde homepage, niet je andere pagina's.</p>
  <form method="post">
    <?=csrf_field()?>
    <button class="a-btn" type="submit">Instellen</button>
  </form>
<?php endif; ?>
</div></div>
<?php admin_footer(); ?>
