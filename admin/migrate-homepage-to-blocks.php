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
$publishedExisting = false;

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
        if($existingBlocks){
            $alreadyHadBlocks = true;
            // Blokken zijn er al maar de pagina stond nog op concept — dan
            // draaide de site alsnog stilletjes op de oude vaste opmaak.
            // Enkel publiceren, de bestaande blokken niet aanraken.
            if(!$home['published']){
                db()->prepare('UPDATE pages SET published=1 WHERE id=?')->execute([$homeId]);
                $publishedExisting = true;
            }
        }
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

    header('Location: migrate-homepage-to-blocks.php?done=1'.($alreadyHadBlocks ? '&skip=1' : '').($created ? '&created=1' : '').($publishedExisting ? '&published=1' : '')); exit;
}

if(isset($_GET['done'])){ $done = true; $alreadyHadBlocks = isset($_GET['skip']); $created = isset($_GET['created']); $publishedExisting = isset($_GET['published']); }

admin_header(t('hp_migrate_title'), 'pages');
?>
<div class="a-card"><div class="a-card-pad">
<?php if($done && $alreadyHadBlocks && $publishedExisting): ?>
  <div class="notice"><?=e(t('hp_migrate_done_published'))?></div>
  <p><a class="a-btn a-btn-ghost" href="pages.php"><?=e(t('to_pages'))?></a></p>
<?php elseif($done && $alreadyHadBlocks): ?>
  <div class="notice"><?=e(t('hp_migrate_done_skip'))?></div>
  <p><a class="a-btn a-btn-ghost" href="pages.php"><?=e(t('to_pages'))?></a></p>
<?php elseif($done): ?>
  <div class="notice"><?=sprintf(e(t('hp_migrate_done_main')), $created ? e(t('hp_migrate_done_created')) : '')?></div>
  <p><?=t('hp_migrate_done_hint')?></p>
  <p><a class="a-btn" href="pages.php"><?=e(t('to_pages'))?></a></p>
<?php else: ?>
  <h2 style="margin-top:0"><?=e(t('hp_migrate_title'))?></h2>
  <p><?=e(t('hp_migrate_intro1'))?></p>
  <p><?=e(t('hp_migrate_intro2'))?></p>
  <p style="color:#8a7c6c;font-size:.9rem"><?=e(t('hp_migrate_safe_hint'))?></p>
  <form method="post">
    <?=csrf_field()?>
    <button class="a-btn" type="submit"><?=e(t('convert_btn'))?></button>
  </form>
<?php endif; ?>
</div></div>
<?php admin_footer(); ?>
