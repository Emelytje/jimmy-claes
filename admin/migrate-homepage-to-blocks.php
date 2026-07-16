<?php
/**
 * Eénmalige omzetting: de homepage draait momenteel nog op de vaste,
 * niet-bewerkbare basisopmaak in index.php (titel+tekst uit Site-instellingen,
 * plus een rooster met alle dieren) omdat de "Home"-pagina zelf leeg/concept
 * is. Deze knop herbouwt exact diezelfde inhoud als gewone blokken en
 * publiceert de pagina, zodat de pagebuilder-editor voortaan echt toont (en
 * laat bewerken) wat er live staat. Veilig om te herdraaien: als de
 * Home-pagina al blokken heeft, gebeurt er niets.
 */
require __DIR__.'/inc.php';

$st = db()->prepare('SELECT * FROM pages WHERE is_homepage=1 LIMIT 1');
$st->execute();
$home = $st->fetch();

$done = false;
$alreadyHadBlocks = false;
$created = false;

if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_verify();

    if(!$home){
        $slug = 'home';
        $chk = db()->prepare('SELECT COUNT(*) c FROM pages WHERE slug=?');
        $chk->execute([$slug]);
        if((int)$chk->fetch()['c'] > 0) $slug = 'home-'.substr(bin2hex(random_bytes(3)), 0, 5);
        $ins = db()->prepare('INSERT INTO pages(title,slug,blocks,published,is_homepage) VALUES(?,?,?,0,1)');
        $ins->execute(['Home', $slug, '[]']);
        $homeId = (int)db()->lastInsertId();
        $created = true;
    } else {
        $homeId = (int)$home['id'];
        $existingBlocks = pb_decode_blocks($home['blocks'] ?? null);
        if($existingBlocks) $alreadyHadBlocks = true;
    }

    if(!$alreadyHadBlocks){
        $introTitle = setting('intro_title', 'Jimbo Animal Species of the World');
        $introText = setting('intro_text', 'Een zachte fotografieplek met verhalen, beelden en pagina’s per dier.');
        $animalCount = (int)db()->query('SELECT COUNT(*) c FROM animals WHERE published=1')->fetch()['c'];

        $defaultSettings = ['fontFamily'=>'','fontSize'=>'','textColor'=>'','bgColor'=>'','align'=>'left','paddingY'=>56,'paddingX'=>24,'radius'=>0,'shadow'=>'none','animation'=>'fade-up'];
        $blocks = [
            [
                'id' => 'b_'.bin2hex(random_bytes(4)),
                'type' => 'hero',
                'settings' => array_merge($defaultSettings, ['align'=>'center','paddingY'=>'','paddingX'=>'','animation'=>'fade-in']),
                'data' => ['title'=>$introTitle, 'subtitle'=>$introText, 'buttonText'=>'', 'buttonHref'=>'#', 'bgImage'=>'', 'overlay'=>45],
            ],
            [
                'id' => 'b_'.bin2hex(random_bytes(4)),
                'type' => 'title',
                'settings' => $defaultSettings,
                'data' => ['text'=>"Pagina's per dier", 'level'=>'h2'],
            ],
            [
                'id' => 'b_'.bin2hex(random_bytes(4)),
                'type' => 'recent',
                'settings' => $defaultSettings,
                'data' => ['source'=>'animals', 'count'=>max(3, min(24, $animalCount ?: 12))],
            ],
        ];
        db()->prepare('UPDATE pages SET blocks=?, published=1, is_homepage=1 WHERE id=?')
            ->execute([json_encode($blocks), $homeId]);
        $done = true;
    }

    header('Location: migrate-homepage-to-blocks.php?done=1'.($alreadyHadBlocks ? '&skip=1' : '').($created ? '&created=1' : '')); exit;
}

if(isset($_GET['done'])){ $done = true; $alreadyHadBlocks = isset($_GET['skip']); $created = isset($_GET['created']); }

admin_header('Homepage omzetten naar blokken', 'pages');
?>
<div class="a-card"><div class="a-card-pad">
<?php if($done && $alreadyHadBlocks): ?>
  <div class="notice">De Home-pagina had al blokken — niets aangepast, je huidige opbouw blijft ongemoeid.</div>
  <p><a class="a-btn a-btn-ghost" href="pages.php">Naar Pagina's</a></p>
<?php elseif($done): ?>
  <div class="notice">Klaar<?php if($created): ?> (nieuwe Home-pagina aangemaakt)<?php endif; ?>. De homepage is nu opgebouwd uit gewone blokken — dezelfde titel, tekst en dierenkaarten als voorheen — en meteen gepubliceerd.</div>
  <p>Ga naar <a href="pages.php">Pagina's</a> en open "Home" om verder aan te passen, bijvoorbeeld het "Gewervelde / Ongewervelde"-blok toevoegen.</p>
  <p><a class="a-btn" href="pages.php">Naar Pagina's</a></p>
<?php else: ?>
  <h2 style="margin-top:0">Homepage omzetten naar blokken</h2>
  <p>Je homepage toont nu nog de vaste, niet-bewerkbare basisopmaak (titel + tekst uit Site-instellingen, plus een rooster met alle dieren) — dat is waarom de pagebuilder-editor voor "Home" leeg lijkt: die pagina zelf heeft nog geen blokken en staat nog op concept.</p>
  <p>Deze knop zet dat één keer om in gewone, versleepbare blokken — met exact dezelfde titel, tekst en dierenkaarten — en publiceert de pagina meteen. Daarna toont de editor precies wat er live staat, en kan je zelf blokken toevoegen zoals "Gewervelde / Ongewervelde".</p>
  <p style="color:#8a7c6c;font-size:.9rem">Veilig om te draaien: als de Home-pagina al blokken heeft, gebeurt er niets.</p>
  <form method="post">
    <?=csrf_field()?>
    <button class="a-btn" type="submit">Omzetten</button>
  </form>
<?php endif; ?>
</div></div>
<?php admin_footer(); ?>
