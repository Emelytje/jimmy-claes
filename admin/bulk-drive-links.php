<?php
/**
 * Plak-en-klaar tool om in één keer een Google Drive-link te koppelen aan
 * veel dieren tegelijk (bv. 500), i.p.v. elk dier apart open te doen. Elke
 * regel is "Naam<tab of komma>Link"; de naam wordt gematcht tegen de
 * diersoort-titel (exacte match, hoofdletterongevoelig). Titels die
 * bewust dubbel in de boom voorkomen (bv. Lathamus discolor) worden
 * overgeslagen — die kan je los koppelen via de Drive-link-kolom in de
 * Dieren-lijst zelf.
 */
require __DIR__.'/inc.php';

if(!pb_has_column('animals','drive_url')){
    try{ db()->exec("ALTER TABLE animals ADD COLUMN drive_url VARCHAR(500) DEFAULT NULL"); }catch(Exception $e){}
}

function bdl_parse_lines($raw){
    $rows = [];
    foreach(preg_split('/\r\n|\r|\n/', $raw) as $line){
        $line = trim($line);
        if($line === '') continue;
        if(strpos($line, "\t") !== false){
            [$name, $link] = array_map('trim', explode("\t", $line, 2));
        } elseif(strpos($line, ',') !== false){
            [$name, $link] = array_map('trim', explode(',', $line, 2));
        } else {
            continue;
        }
        if($name === '' || $link === '') continue;
        $rows[] = [$name, $link];
    }
    return $rows;
}

$done = false;
$stats = ['updated' => 0, 'notFound' => [], 'ambiguous' => []];

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    csrf_verify();
    $rows = bdl_parse_lines($_POST['data'] ?? '');

    // Alle dieren éénmalig ophalen en op lowercase titel groeperen, zodat er
    // geen 500 losse queries nodig zijn en dubbele titels meteen zichtbaar zijn.
    $byTitle = [];
    foreach(db()->query('SELECT id, title FROM animals') as $a){
        $byTitle[mb_strtolower(trim($a['title']))][] = $a['id'];
    }

    $upd = db()->prepare('UPDATE animals SET drive_url=? WHERE id=?');
    foreach($rows as [$name, $link]){
        if($link !== '' && !preg_match('~^https?://~i', $link)) $link = 'https://'.$link;
        $key = mb_strtolower($name);
        $ids = $byTitle[$key] ?? [];
        if(count($ids) === 0){
            $stats['notFound'][] = $name;
        } elseif(count($ids) > 1){
            $stats['ambiguous'][] = $name;
        } else {
            $upd->execute([$link, $ids[0]]);
            $stats['updated']++;
        }
    }
    $done = true;
}

admin_header(t('bdl_title'), 'animals');
?>
<div class="a-card"><div class="a-card-pad">
<?php if($done): ?>
  <div class="notice"><?=sprintf(e(t('bdl_done_summary')), $stats['updated'])?></div>
  <?php if($stats['notFound']): ?>
  <p><strong><?=e(t('bdl_not_found_label'))?></strong> (<?=count($stats['notFound'])?>)</p>
  <p style="font-size:.85rem;color:#8a7c6c"><?=e(implode(', ', $stats['notFound']))?></p>
  <?php endif; ?>
  <?php if($stats['ambiguous']): ?>
  <p><strong><?=e(t('bdl_ambiguous_label'))?></strong> (<?=count($stats['ambiguous'])?>)</p>
  <p style="font-size:.85rem;color:#8a7c6c"><?=e(implode(', ', $stats['ambiguous']))?></p>
  <?php endif; ?>
  <p><a class="a-btn" href="content.php?type=animal"><?=e(t('to_animals_check'))?></a> <a class="a-btn a-btn-ghost" href="bulk-drive-links.php"><?=e(t('bdl_do_more'))?></a></p>
<?php else: ?>
  <h2 style="margin-top:0"><?=e(t('bdl_title'))?></h2>
  <p><?=e(t('bdl_explain'))?></p>
  <form method="post">
    <?=csrf_field()?>
    <div class="a-field">
      <label><?=e(t('bdl_textarea_label'))?></label>
      <textarea name="data" rows="16" style="width:100%;font-family:monospace;font-size:.85rem" placeholder="Mustelus asterias, https://drive.google.com/drive/folders/xxxx&#10;Aurelia aurita, https://drive.google.com/drive/folders/yyyy"></textarea>
    </div>
    <button class="a-btn" type="submit"><?=e(t('bdl_submit_btn'))?></button>
  </form>
<?php endif; ?>
</div></div>
<?php admin_footer(); ?>
