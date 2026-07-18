<?php
/**
 * CSV-upload om in één keer veel dierentuinen toe te voegen of bij te
 * werken (bv. vanuit een .ods/.xlsx-lijst, opgeslagen als CSV). Herkent
 * kolommen aan de hand van de kopregel (Naam/Stad/Land/Website, NL of EN,
 * eender welke volgorde); zonder herkenbare kopregel wordt de vaste
 * volgorde Naam, Stad, Land, Website aangenomen. Een zoo met een naam die
 * al bestaat (hoofdletterongevoelig) wordt bijgewerkt (stad/land/url),
 * nieuwe namen worden aangemaakt. Veilig om meermaals te draaien.
 */
require __DIR__.'/inc.php';

if(!pb_has_column('zoos','city')){ try{ db()->exec("ALTER TABLE zoos ADD COLUMN city VARCHAR(160) DEFAULT NULL"); }catch(Exception $e){} }
if(!pb_has_column('zoos','country')){ try{ db()->exec("ALTER TABLE zoos ADD COLUMN country VARCHAR(160) DEFAULT NULL"); }catch(Exception $e){} }
if(!pb_has_column('zoos','lat')){ try{ db()->exec("ALTER TABLE zoos ADD COLUMN lat DECIMAL(9,6) DEFAULT NULL"); }catch(Exception $e){} }
if(!pb_has_column('zoos','lng')){ try{ db()->exec("ALTER TABLE zoos ADD COLUMN lng DECIMAL(9,6) DEFAULT NULL"); }catch(Exception $e){} }

function bz_normalize_url($url){
    $url = trim($url);
    if($url !== '' && !preg_match('~^https?://~i', $url)) $url = 'https://'.$url;
    return $url;
}

// Exacte (of bijna-exacte) matching op een vaste woordenlijst, niet
// "bevat" — anders herkent bv. "Nederland" zichzelf als landkolom-header
// omdat het toevallig "land" als substring bevat.
function bz_detect_columns($header){
    $known = [
        'title'   => ['naam', 'name', 'titel', 'title'],
        'city'    => ['stad', 'city', 'plaats'],
        'country' => ['land', 'country'],
        'url'     => ['url', 'website', 'site', 'link'],
    ];
    $map = ['title' => null, 'city' => null, 'country' => null, 'url' => null];
    foreach($header as $i => $col){
        $c = mb_strtolower(trim($col));
        foreach($known as $field => $words){
            if($map[$field] === null && in_array($c, $words, true)){ $map[$field] = $i; break; }
        }
    }
    return $map;
}

function bz_parse_csv($path){
    $rows = [];
    $fh = fopen($path, 'r');
    if(!$fh) return $rows;
    $bom = fread($fh, 3);
    if($bom !== "\xEF\xBB\xBF") rewind($fh);
    $firstLine = fgets($fh);
    if($firstLine === false){ fclose($fh); return $rows; }
    $delim = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
    rewind($fh);
    if($bom === "\xEF\xBB\xBF") fseek($fh, 3);

    $header = fgetcsv($fh, 0, $delim);
    if($header === false){ fclose($fh); return $rows; }
    $map = bz_detect_columns($header);
    $hasHeader = $map['title'] !== null || $map['city'] !== null || $map['country'] !== null || $map['url'] !== null;
    if(!$hasHeader){
        // Geen herkenbare kopregel: vaste volgorde aannemen en deze eerste
        // regel dan ook als data behandelen (niet overslaan).
        $map = ['title' => 0, 'city' => 1, 'country' => 2, 'url' => 3];
        rewind($fh);
        if($bom === "\xEF\xBB\xBF") fseek($fh, 3);
    }

    while(($cols = fgetcsv($fh, 0, $delim)) !== false){
        if(count($cols) < 1) continue;
        $title   = $map['title']   !== null ? trim($cols[$map['title']]   ?? '') : '';
        $city    = $map['city']    !== null ? trim($cols[$map['city']]    ?? '') : '';
        $country = $map['country'] !== null ? trim($cols[$map['country']] ?? '') : '';
        $url     = $map['url']     !== null ? trim($cols[$map['url']]     ?? '') : '';
        if($title === '') continue;
        $rows[] = [$title, $city, $country, $url];
    }
    fclose($fh);
    return $rows;
}

$done = false;
$stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    csrf_verify();
    $rows = [];
    if(!empty($_FILES['csv_file']['tmp_name']) && is_uploaded_file($_FILES['csv_file']['tmp_name'])){
        $rows = bz_parse_csv($_FILES['csv_file']['tmp_name']);
    }

    $existing = [];
    foreach(db()->query('SELECT id, title, city, country, lat, lng FROM zoos') as $z){
        $existing[mb_strtolower(trim($z['title']))] = $z;
    }

    $ins = db()->prepare('INSERT INTO zoos(title,url,city,country,lat,lng) VALUES(?,?,?,?,?,?)');
    $upd = db()->prepare('UPDATE zoos SET url=?, city=?, country=?, lat=?, lng=? WHERE id=?');
    $updNoGeocode = db()->prepare('UPDATE zoos SET url=?, city=?, country=? WHERE id=?');
    $first = true;
    foreach($rows as [$title, $city, $country, $url]){
        $url = bz_normalize_url($url);
        if($url === ''){ $stats['skipped']++; continue; }
        $key = mb_strtolower($title);
        $existingRow = $existing[$key] ?? null;
        if($existingRow){
            $cityChanged = ($existingRow['city'] ?? '') !== $city || ($existingRow['country'] ?? '') !== $country;
            $needsGeocode = $cityChanged || $existingRow['lat'] === null || $existingRow['lng'] === null;
            if($needsGeocode && ($city !== '' || $country !== '')){
                if(!$first) sleep(1); // Nominatim: max 1 verzoek per seconde
                $first = false;
                $coords = geocode_city_country($city, $country);
                $upd->execute([$url, $city !== '' ? $city : null, $country !== '' ? $country : null, $coords[0] ?? null, $coords[1] ?? null, $existingRow['id']]);
            } else {
                $updNoGeocode->execute([$url, $city !== '' ? $city : null, $country !== '' ? $country : null, $existingRow['id']]);
            }
            $stats['updated']++;
        } else {
            $coords = null;
            if($city !== '' || $country !== ''){
                if(!$first) sleep(1);
                $first = false;
                $coords = geocode_city_country($city, $country);
            }
            $ins->execute([$title, $url, $city !== '' ? $city : null, $country !== '' ? $country : null, $coords[0] ?? null, $coords[1] ?? null]);
            $stats['created']++;
        }
    }
    $done = true;
}

admin_header(t('bz_title'), 'zoos');
?>
<div class="a-card"><div class="a-card-pad">
<?php if($done): ?>
  <div class="notice"><?=sprintf(e(t('bz_done_summary')), $stats['created'], $stats['updated'])?><?php if($stats['skipped']): ?> <?=sprintf(e(t('bz_skipped_summary')), $stats['skipped'])?><?php endif; ?></div>
  <p><a class="a-btn" href="zoos.php"><?=e(t('to_zoos_check'))?></a> <a class="a-btn a-btn-ghost" href="bulk-zoos.php"><?=e(t('bdl_do_more'))?></a></p>
<?php else: ?>
  <h2 style="margin-top:0"><?=e(t('bz_title'))?></h2>
  <p><?=e(t('bz_explain'))?></p>
  <form method="post" enctype="multipart/form-data">
    <?=csrf_field()?>
    <div class="a-field">
      <label><?=e(t('bdl_csv_label'))?></label>
      <input type="file" name="csv_file" accept=".csv,text/csv" required>
    </div>
    <button class="a-btn" type="submit"><?=e(t('bdl_submit_btn'))?></button>
  </form>
<?php endif; ?>
</div></div>
<?php admin_footer(); ?>
