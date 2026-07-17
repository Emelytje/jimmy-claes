<?php
/**
 * Pagebuilder render engine.
 *
 * Schema (one page's `blocks` column, json_decoded to an array):
 * [
 *   {
 *     "id":       "b_xxxxxxxx"        unique within the page,
 *     "type":     "hero|title|text|image|gallery|video|button|divider|quote|columns|row|recent|contact|html",
 *     "settings": {
 *       "fontFamily": "" | "Playfair Display" | ...   (Google Font family name),
 *       "fontSize":   "" | "16" (px, applies to block's main text),
 *       "textColor":  "" | "#222222",
 *       "bgColor":    "" | "#f6f1ea",
 *       "align":      "left|center|right",
 *       "paddingY":   int px (block top/bottom padding),
 *       "paddingX":   int px (block left/right padding),
 *       "radius":     int px (corner rounding of the block's own background),
 *       "shadow":     "none|sm|md|lg",
 *       "animation":  "none|fade-up|fade-in|slide-left|slide-right|zoom-in"
 *     },
 *     "data": { ...type-specific fields, see render_block() ... }
 *   },
 *   ...
 * ]
 *
 * Columns blocks nest an array of the same block objects one level deep
 * (data.cols[i].blocks[]); render_blocks() is reused recursively for those.
 *
 * Row blocks are the freeform side-by-side layout created by dragging a
 * block against the edge of another one in the editor: data.cells is an
 * array of {"widthPct": 0-100, "blocks": [...block objects, same as a
 * columns column...]}, data.mobileStack (default true) stacks cells on
 * narrow screens. Image blocks may carry data.aspectRatio (a width/height
 * number, e.g. 1.5) to fix their height responsively via CSS aspect-ratio.
 */

const PB_SHADOWS = [
    'none' => '',
    'sm'   => '0 1px 3px rgba(30,20,10,.08)',
    'md'   => '0 8px 24px rgba(30,20,10,.10)',
    'lg'   => '0 20px 48px rgba(30,20,10,.16)',
];

const PB_ALLOWED_TEXT_TAGS = '<p><br><b><strong><i><em><u><a><span><ul><ol><li>';

function pb_new_block_id(){ return 'b_'.bin2hex(random_bytes(6)); }

function pb_decode_blocks($json){
    if(empty($json)) return [];
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function pb_encode_blocks($blocks){
    return json_encode($blocks, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}

function pb_clean_text_html($html){
    $html = (string)$html;
    $html = strip_tags($html, PB_ALLOWED_TEXT_TAGS);
    // <a> keeps a vetted href/target/rel; <span> keeps a rebuilt, validated
    // color/font-size style; every other allowed tag loses ALL attributes
    // (blocks onclick/onmouseover/style="javascript:..." etc.).
    $html = preg_replace_callback('/<a\b[^>]*>/i', function($m){
        $href = '';
        if(preg_match('/href\s*=\s*"([^"]*)"/i', $m[0], $h)) $href = $h[1];
        elseif(preg_match('/href\s*=\s*\'([^\']*)\'/i', $m[0], $h)) $href = $h[1];
        if($href!=='' && (preg_match('#^(https?:)?//#i',$href) || str_starts_with($href,'/') || str_starts_with($href,'#') || preg_match('/^[a-z0-9_\-]+\.php/i',$href))){
            return '<a href="'.e($href).'" target="_blank" rel="noopener">';
        }
        return '<a>';
    }, $html);
    $html = preg_replace_callback('/<span\b([^>]*)>/i', function($m){
        $style = '';
        if(preg_match('/style\s*=\s*"([^"]*)"/i', $m[1], $s)) $style = $s[1];
        $decl = [];
        // Browsers normalize style.color to rgb()/rgba() when serialized back
        // out of the DOM (setting it as a hex string doesn't stick), so both
        // forms need accepting here — the rgb()/rgba() body is restricted to
        // digits/commas/dots/percent/spaces, nothing that could break out.
        if(preg_match('/color\s*:\s*(#[0-9a-fA-F]{3,8}|rgba?\([0-9.,%\s]+\))\s*(?:;|$)/', $style, $c)) $decl[] = 'color:'.$c[1];
        if(preg_match('/font-size\s*:\s*(\d{1,3})px\s*(?:;|$)/', $style, $fs)){
            $decl[] = 'font-size:'.max(8, min(96, (int)$fs[1])).'px';
        }
        return $decl ? '<span style="'.e(implode(';', $decl)).'">' : '<span>';
    }, $html);
    $html = preg_replace('#<(/?(?:p|br|b|strong|i|em|u|ul|ol|li))\b[^>]*>#i', '<$1>', $html);
    return $html;
}

function pb_style_attr($settings, $extra=[]){
    $s = $settings ?: [];
    $decl = [];
    if(!empty($s['fontFamily'])) $decl[] = "font-family:'".str_replace("'","",$s['fontFamily'])."',var(--font-body)";
    if(!empty($s['fontSize'])) $decl[] = 'font-size:'.(int)$s['fontSize'].'px';
    if(!empty($s['textColor'])) $decl[] = '--pb-text-color:'.pb_safe_color($s['textColor']).';color:'.pb_safe_color($s['textColor']);
    if(!empty($s['bgColor'])) $decl[] = 'background-color:'.pb_safe_color($s['bgColor']);
    if(isset($s['paddingY']) && $s['paddingY']!=='') $decl[] = 'padding-top:'.(int)$s['paddingY'].'px;padding-bottom:'.(int)$s['paddingY'].'px';
    if(isset($s['paddingX']) && $s['paddingX']!=='') $decl[] = 'padding-left:'.(int)$s['paddingX'].'px;padding-right:'.(int)$s['paddingX'].'px';
    if(isset($s['radius']) && $s['radius']!=='') $decl[] = 'border-radius:'.(int)$s['radius'].'px';
    if(!empty($s['shadow']) && isset(PB_SHADOWS[$s['shadow']]) && PB_SHADOWS[$s['shadow']]!=='') $decl[] = 'box-shadow:'.PB_SHADOWS[$s['shadow']];
    if(!empty($s['align'])) $decl[] = 'text-align:'.($s['align']==='left'||$s['align']==='center'||$s['align']==='right' ? $s['align'] : 'left');
    foreach($extra as $k=>$v) $decl[] = $k.':'.$v;
    return $decl ? ' style="'.e(implode(';',$decl)).'"' : '';
}

function pb_safe_color($c){
    $c = trim((string)$c);
    if(preg_match('/^#[0-9a-fA-F]{3,8}$/', $c)) return $c;
    if(preg_match('/^rgba?\([0-9,.\s]+\)$/', $c)) return $c;
    return '#000000';
}

function pb_animation_attrs($settings){
    $anim = $settings['animation'] ?? 'none';
    if(!$anim || $anim==='none') return '';
    $allowed = ['fade-up','fade-in','slide-left','slide-right','zoom-in'];
    if(!in_array($anim,$allowed,true)) return '';
    return ' data-animate="'.e($anim).'"';
}

function pb_font_families_used($blocks){
    $fonts = [];
    foreach($blocks as $b){
        if(!empty($b['settings']['fontFamily'])) $fonts[$b['settings']['fontFamily']] = true;
        if(($b['type']??'')==='columns'){
            foreach(($b['data']['cols']??[]) as $col){ foreach(pb_font_families_used($col['blocks']??[]) as $f) $fonts[$f]=true; }
        }
        if(($b['type']??'')==='row'){
            foreach(($b['data']['cells']??[]) as $cell){ foreach(pb_font_families_used($cell['blocks']??[]) as $f) $fonts[$f]=true; }
        }
    }
    return array_keys($fonts);
}

function pb_google_fonts_link_href($families){
    if(!$families) return '';
    $parts = [];
    foreach($families as $f){ $parts[] = 'family='.rawurlencode($f).':wght@400;600;700'; }
    return 'https://fonts.googleapis.com/css2?'.implode('&',$parts).'&display=swap';
}

function render_blocks($blocks, $depth=0, $ctx=[]){
    if(!is_array($blocks) || $depth > 2) return '';
    $out = '';
    foreach($blocks as $block){ $out .= render_block($block, $depth, $ctx); }
    return $out;
}

function render_block($block, $depth=0, $ctx=[]){
    $type = $block['type'] ?? '';
    $data = $block['data'] ?? [];
    $settings = $block['settings'] ?? [];
    $id = e($block['id'] ?? '');

    switch($type){
        case 'title':      $inner = pb_render_title($data); break;
        case 'text':       $inner = pb_render_text($data); break;
        case 'image':      $inner = pb_render_image($data); break;
        case 'gallery':    $inner = pb_render_gallery($data); break;
        case 'video':      $inner = pb_render_video($data); break;
        case 'button':     $inner = pb_render_button($data); break;
        case 'divider':    $inner = pb_render_divider($data); break;
        case 'quote':      $inner = pb_render_quote($data); break;
        case 'columns':    $inner = pb_render_columns($data, $depth, $ctx); break;
        case 'row':        $inner = pb_render_row($data, $depth, $ctx); break;
        case 'recent':     $inner = pb_render_recent($data); break;
        case 'subcategories': $inner = pb_render_subcategories($data, $ctx); break;
        case 'categories_grid': $inner = pb_render_categories_grid($data); break;
        case 'photocount': $inner = pb_render_photocount($data); break;
        case 'species_progress': $inner = pb_render_species_progress($data); break;
        case 'class_split': $inner = pb_render_class_split($data); break;
        case 'slideshow':  $inner = pb_render_slideshow($data); break;
        case 'hero':       return pb_render_hero($data, $settings, $id);
        case 'contact':    $inner = pb_render_contact($data); break;
        case 'html':       $inner = (string)($data['code'] ?? ''); break;
        default: return '';
    }

    $style = pb_style_attr($settings);
    $anim = pb_animation_attrs($settings);
    return '<section class="pb-block pb-'.e($type).'" data-block-id="'.$id.'"'.$style.$anim.'><div class="pb-inner">'.$inner.'</div></section>';
}

function pb_render_title($d){
    $level = $d['level'] ?? 'h2'; if(!in_array($level, ['h1','h2','h3'], true)) $level = 'h2';
    return '<'.$level.'>'.e($d['text'] ?? '').'</'.$level.'>';
}

function pb_render_text($d){
    return '<div class="pb-text">'.pb_clean_text_html($d['html'] ?? '').'</div>';
}

function pb_render_image($d){
    $src = e($d['src'] ?? '');
    if($src==='') return '';
    $alt = e($d['alt'] ?? '');
    $cls = ($d['width'] ?? 'contained') === 'full' ? 'pb-img-full' : 'pb-img-contained';
    $imgStyleParts = [];
    if(!empty($d['aspectRatio'])){
        $ar = pb_safe_aspect_ratio($d['aspectRatio']);
        if($ar){ $imgStyleParts[] = 'aspect-ratio:'.$ar; $imgStyleParts[] = 'object-fit:cover'; $imgStyleParts[] = 'height:auto'; }
    }
    $imgStyle = $imgStyleParts ? ' style="'.e(implode(';',$imgStyleParts)).'"' : '';
    $img = '<img src="'.$src.'" alt="'.$alt.'" loading="lazy" class="'.$cls.'"'.$imgStyle.'>';
    if(!empty($d['link'])) $img = '<a href="'.e($d['link']).'">'.$img.'</a>';
    $badge = pb_gallery_zoo_badge($d['zoo_id'] ?? null);
    $caption = !empty($d['caption']) ? '<figcaption>'.e($d['caption']).'</figcaption>' : '';

    $figureStyleDecls = ['position:relative'];
    if(isset($d['widthPct']) && $d['widthPct']!==''){
        $wp = max(10, min(100, (float)$d['widthPct']));
        $figureStyleDecls[] = 'max-width:'.$wp.'%'; $figureStyleDecls[] = 'margin-left:auto'; $figureStyleDecls[] = 'margin-right:auto';
    }
    return '<figure class="pb-figure" style="'.e(implode(';', $figureStyleDecls)).'">'.$img.$badge.$caption.'</figure>';
}

function pb_safe_aspect_ratio($v){
    $n = (float)$v;
    if($n < 0.2 || $n > 6) return null;
    return (string)round($n, 4);
}

// Alle zoos eenmalig per request opgehaald en op id gezet, zodat een
// galerij met veel foto's niet voor elke foto apart een query doet.
function pb_zoos_by_id(){
    static $map = null;
    if($map !== null) return $map;
    $map = [];
    try{
        $rows = db()->query('SELECT * FROM zoos')->fetchAll();
        foreach($rows as $z) $map[(int)$z['id']] = $z;
    }catch(Exception $e){}
    return $map;
}

// "Zoo Antwerpen, Antwerpen, België" — enkel de ingevulde delen, geen extra
// tekst ervoor/erna, zoals gevraagd voor het foto-tagje in een fotogalerij.
function zoo_label($zoo){
    if(!$zoo) return '';
    $parts = [];
    foreach(['title','city','country'] as $f){ if(!empty($zoo[$f])) $parts[] = trim($zoo[$f]); }
    return implode(', ', $parts);
}

function pb_gallery_zoo_badge($zooId){
    if(empty($zooId)) return '';
    $zoo = pb_zoos_by_id()[(int)$zooId] ?? null;
    $label = zoo_label($zoo);
    return $label !== '' ? '<span class="pb-gallery-zoo-badge">'.e($label).'</span>' : '';
}

function pb_render_gallery($d){
    $cols = max(2, min(4, (int)($d['columns'] ?? 3)));
    $layout = ($d['layout'] ?? 'grid') === 'masonry' ? 'masonry' : 'grid';
    $html = '<div class="pb-gallery pb-gallery-'.$layout.'" style="--pb-cols:'.$cols.'">';
    foreach(($d['images'] ?? []) as $img){
        if(empty($img['src'])) continue;
        $cls = 'pb-gallery-item'.((($img['size'] ?? '') === 'large') ? ' pb-gallery-item-lg' : '');
        $html .= '<figure class="'.$cls.'"><img src="'.e($img['src']).'" alt="'.e($img['alt'] ?? '').'" loading="lazy">';
        $html .= pb_gallery_zoo_badge($img['zoo_id'] ?? null);
        if(!empty($img['caption'])) $html .= '<figcaption>'.e($img['caption']).'</figcaption>';
        $html .= '</figure>';
    }
    return $html.'</div>';
}

function pb_render_video($d){
    $url = trim($d['url'] ?? '');
    if($url==='') return '';
    $embed = pb_video_embed_url($url);
    if($embed){
        return '<div class="pb-video-wrap"><iframe src="'.e($embed).'" loading="lazy" allow="accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture" allowfullscreen></iframe></div>';
    }
    if(preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', $url)){
        return '<div class="pb-video-wrap"><video controls src="'.e($url).'"'.(!empty($d['poster'])?' poster="'.e($d['poster']).'"':'').'></video></div>';
    }
    return '';
}

function pb_video_embed_url($url){
    if(preg_match('#(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([A-Za-z0-9_\-]{6,})#', $url, $m)){
        return 'https://www.youtube-nocookie.com/embed/'.$m[1];
    }
    if(preg_match('#vimeo\.com/(\d+)#', $url, $m)){
        return 'https://player.vimeo.com/video/'.$m[1];
    }
    return null;
}

function pb_render_button($d){
    $style = $d['style'] ?? 'solid'; if(!in_array($style, ['solid','outline','ghost'], true)) $style = 'solid';
    $size = $d['size'] ?? 'md'; if(!in_array($size, ['sm','md','lg'], true)) $size = 'md';
    $href = pb_safe_href($d['href'] ?? '#');
    return '<a class="pb-btn pb-btn-'.e($style).' pb-btn-'.e($size).'" href="'.e($href).'">'.e($d['text'] ?? 'Klik hier').'</a>';
}

function pb_safe_href($href){
    $href = trim((string)$href);
    if($href==='') return '#';
    if(preg_match('#^(https?:)?//#i',$href)) return $href;
    if(str_starts_with($href,'/') || str_starts_with($href,'#') || str_starts_with($href,'mailto:') || str_starts_with($href,'tel:')) return $href;
    if(preg_match('/^[a-z0-9_\-]+\.php(\?.*)?(#.*)?$/i',$href)) return $href;
    return '#';
}

function pb_render_divider($d){
    $style = $d['style'] ?? 'line'; if(!in_array($style, ['line','dots','space'], true)) $style = 'line';
    return '<div class="pb-divider pb-divider-'.e($style).'"></div>';
}

function pb_render_quote($d){
    $author = !empty($d['author']) ? '<cite>'.e($d['author']).'</cite>' : '';
    return '<blockquote class="pb-quote"><p>'.e($d['text'] ?? '').'</p>'.$author.'</blockquote>';
}

function pb_render_columns($d, $depth, $ctx=[]){
    $cols = $d['cols'] ?? [];
    $count = max(1, min(4, count($cols) ?: (int)($d['count'] ?? 2)));
    $gap = isset($d['gap']) ? (int)$d['gap'] : 32;
    $html = '<div class="pb-columns-grid pb-columns-'.$count.'" style="--pb-gap:'.$gap.'px">';
    foreach($cols as $col){
        $html .= '<div class="pb-column">'.render_blocks($col['blocks'] ?? [], $depth+1, $ctx).'</div>';
    }
    return $html.'</div>';
}

function pb_render_row($d, $depth, $ctx=[]){
    $cells = $d['cells'] ?? [];
    if(!$cells) return '';
    $gap = isset($d['gap']) ? (int)$d['gap'] : 24;
    $stack = (!array_key_exists('mobileStack', $d) || $d['mobileStack']) ? 1 : 0;
    $html = '<div class="pb-row-flex" data-stack="'.$stack.'" style="--pb-row-gap:'.$gap.'px">';
    $count = count($cells);
    foreach($cells as $cell){
        $w = isset($cell['widthPct']) ? max(5, min(95, (float)$cell['widthPct'])) : (100 / max(1,$count));
        $html .= '<div class="pb-cell" style="width:'.round($w,4).'%">'.render_blocks($cell['blocks'] ?? [], $depth+1, $ctx).'</div>';
    }
    return $html.'</div>';
}

function pb_render_hero($d, $settings, $id){
    $bg = !empty($d['bgImage']) ? e($d['bgImage']) : '';
    $overlay = isset($d['overlay']) ? max(0,min(100,(int)$d['overlay'])) : 45;
    $style = pb_style_attr($settings, $bg ? ['background-image'=>"url('".addslashes($bg)."')"] : []);
    $anim = pb_animation_attrs($settings);
    $html = '<section class="pb-block pb-hero'.($bg?' pb-hero-has-bg':'').'" data-block-id="'.$id.'"'.$style.$anim.'>';
    if($bg) $html .= '<div class="pb-hero-overlay" style="opacity:'.($overlay/100).'"></div>';
    $html .= '<div class="pb-inner pb-hero-inner">';
    if(!empty($d['title'])) $html .= '<h1>'.e($d['title']).'</h1>';
    if(!empty($d['subtitle'])) $html .= '<p class="pb-hero-subtitle">'.e($d['subtitle']).'</p>';
    if(!empty($d['buttonText'])) $html .= '<a class="pb-btn pb-btn-solid pb-btn-lg" href="'.e(pb_safe_href($d['buttonHref'] ?? '#')).'">'.e($d['buttonText']).'</a>';
    $html .= '</div></section>';
    return $html;
}

function pb_fetch_recent_items($source, $count){
    $defs = [
        'animals' => ['url'=>'animal.php?slug=', 'cols'=>'title, slug, cover_image, created_at'],
        'albums'  => ['url'=>'album.php?slug=',  'cols'=>'title, slug, cover_image, created_at'],
        'posts'   => ['url'=>'post.php?slug=',   'cols'=>'title, slug, cover_image, created_at'],
        'pages'   => ['url'=>'page.php?slug=',   'cols'=>'title, slug, NULL AS cover_image, updated_at AS created_at'],
    ];
    $sources = $source === 'mixed' ? ['animals','albums','posts'] : (isset($defs[$source]) ? [$source] : ['animals']);
    $all = [];
    foreach($sources as $s){
        $def = $defs[$s];
        $st = db()->query("SELECT {$def['cols']} FROM $s WHERE published=1 ORDER BY created_at DESC LIMIT ".(int)$count);
        foreach($st as $row){ $row['url'] = $def['url'].$row['slug']; $all[] = $row; }
    }
    usort($all, function($a,$b){ return strtotime($b['created_at']) <=> strtotime($a['created_at']); });
    return array_slice($all, 0, $count);
}

function pb_render_recent($d){
    $source = $d['source'] ?? 'mixed';
    if(!in_array($source, ['mixed','animals','albums','posts','pages'], true)) $source = 'mixed';
    $count = max(1, min(12, (int)($d['count'] ?? 3)));
    $rows = pb_fetch_recent_items($source, $count);
    if(!$rows) return '<p style="text-align:center;color:var(--ink-soft)">'.e(t('no_content_yet')).'</p>';
    $html = '<div class="grid">';
    foreach($rows as $r){
        $img = !empty($r['cover_image']) ? '<img src="'.e($r['cover_image']).'" alt="" loading="lazy">' : '';
        $html .= '<article class="card"><a href="'.e($r['url']).'">'.$img.'</a><div class="pad"><h3>'.e($r['title']).'</h3><a class="btn" href="'.e($r['url']).'">'.e(t('view')).'</a></div></article>';
    }
    return $html.'</div>';
}

function pb_render_contact($d){
    $title = !empty($d['title']) ? '<h3>'.e($d['title']).'</h3>' : '';
    $redirect = e($_SERVER['REQUEST_URI'] ?? '/');
    return $title.'<form method="post" action="submit-message.php" class="pb-contact-form">'
        .csrf_field()
        .'<input type="hidden" name="redirect" value="'.$redirect.'">'
        .'<label>'.e(t('form_name')).'<input name="name" required></label>'
        .'<label>'.e(t('form_email_optional')).'<input type="email" name="email"></label>'
        .'<label>'.e(t('form_message')).'<textarea name="message" rows="5" required></textarea></label>'
        .'<button type="submit" class="pb-btn pb-btn-solid pb-btn-md">'.e($d['buttonText'] ?? t('form_send')).'</button>'
        .'</form>';
}

// Kruimelpad-keten van hoofdcategorie tot en met de gegeven categorie zelf,
// voor het tonen van de volledige taxonomie-plaats van een diepe pagina
// (bv. Gewervelde dieren > Amfibieën > Kikkers > Boomkikkers).
function pb_category_ancestors($categoryId){
    $chain = [];
    $st = db()->prepare('SELECT id, title, slug, parent_id FROM categories WHERE id=?');
    $cur = (int)$categoryId;
    $guard = 0;
    while($cur && $guard++ < 25){
        $st->execute([$cur]);
        $row = $st->fetch();
        if(!$row) break;
        array_unshift($chain, $row);
        $cur = $row['parent_id'] ? (int)$row['parent_id'] : 0;
    }
    return $chain;
}

// Zachte, per-diersoortklasse themakleur voor de banner van categorie- en
// dierenpagina's — zoekt in de kruimelpad-keten naar een bekende klasse
// (Vissen/Vogels/Reptielen/Zoogdieren/Amfibieën) en geeft die kleur terug,
// of '' als de pagina buiten die klassen valt (bv. de wortel "Gewervelde
// dieren" zelf), zodat dan gewoon de standaard-accentkleur blijft gelden.
// Eén plek voor de koppeling klassenaam -> instellingen-sleutel + standaard
// pastelkleur, gedeeld door pb_class_theme_color() (voorkant) en
// admin/settings.php (kleurenpicker), zodat ze nooit uit elkaar kunnen lopen.
function pb_class_color_map(){
    return [
        // Homepage-achtergrondkleur: geen echte dierklasse, maar hergebruikt
        // hetzelfde instellingen-mechanisme (kleurenkiezer + opslaan) als de
        // klasse-banners hieronder. Leeg (geen standaardkleur) zolang niet
        // ingesteld, zodat de homepage er niet ongevraagd anders uitziet.
        'homepage'      => ['class_color_homepage',       ''],
        // "Gewervelde" is geen echte categorie (bewuste keuze), maar
        // gewervelde.php gebruikt deze zelfde instelling rechtstreeks voor
        // zijn eigen banner — vandaar toch een plek hier.
        'gewervelde'    => ['class_color_gewervelde',     '#e0a868'],
        'vissen'        => ['class_color_vissen',        '#a9cde0'],
        'vogels'        => ['class_color_vogels',        '#e3cf8f'],
        'reptielen'     => ['class_color_reptielen',      '#a9cfae'],
        'zoogdieren'    => ['class_color_zoogdieren',     '#dcaaa3'],
        'amfibieën'     => ['class_color_amfibieen',      '#cdb37e'],
        'ongewervelde'  => ['class_color_ongewervelde',   '#f6f3ec'],
        'spinachtigen'  => ['class_color_spinachtigen',   '#b7b7b3'],
        'schijfkwallen' => ['class_color_schijfkwallen',  '#e0b7bd'],
    ];
}

function pb_class_theme_color($categoryId){
    if(!$categoryId) return '';
    $themes = pb_class_color_map();
    // Diepste match wint: pb_category_ancestors() geeft de keten van wortel
    // tot blad, dus achterstevoren doorlopen zodat bv. "Spinachtigen" boven
    // zijn eigen bovenliggende "Ongewervelde" gaat, niet omgekeerd.
    $chain = array_reverse(pb_category_ancestors($categoryId));
    foreach($chain as $row){
        $key = mb_strtolower(trim($row['title']));
        if(isset($themes[$key])){
            [$settingKey, $default] = $themes[$key];
            return setting($settingKey, $default);
        }
    }
    return '';
}

// Simpele "vorige pagina"-knop (browser-terug) boven categorie- en
// dierenpagina's, in plaats van een volledig kruimelpad.
function pb_render_back_button(){
    return '<div class="pb-back-bar"><button type="button" class="pb-back-link" onclick="history.back()">&larr; '.e(t('back_button')).'</button></div>';
}

// Alle categorie-id's onder (en incl.) een gegeven categorie, voor het
// "willekeurige foto uit categorie"-fallback: zo'n foto mag ook uit een
// diepere sub-categorie komen, niet enkel uit de categorie zelf.
function pb_category_descendant_ids($categoryId){
    $ids = [(int)$categoryId];
    $queue = [(int)$categoryId];
    $st = db()->prepare('SELECT id FROM categories WHERE parent_id=?');
    while($queue){
        $cur = array_shift($queue);
        $st->execute([$cur]);
        foreach($st->fetchAll() as $r){ $ids[] = (int)$r['id']; $queue[] = (int)$r['id']; }
    }
    return $ids;
}

// Willekeurige foto uit een categorie (of een van zijn sub-categorieën/dieren)
// als er zelf geen omslagfoto is ingesteld — zodat een kaartje nooit leeg
// hoeft te blijven zolang er ergens in de tak al een foto staat.
function pb_category_random_photo($categoryId){
    $ids = pb_category_descendant_ids($categoryId);
    if(!$ids) return '';
    $ph = implode(',', array_fill(0, count($ids), '?'));
    try{
        $st = db()->prepare(
            "SELECT img FROM (
                SELECT cover_image img FROM animals WHERE category_id IN ($ph) AND cover_image IS NOT NULL AND cover_image<>''
                UNION ALL
                SELECT p.image_path img FROM photos p JOIN animals a ON a.id=p.animal_id WHERE a.category_id IN ($ph)
                UNION ALL
                SELECT cover_image img FROM categories WHERE id IN ($ph) AND cover_image IS NOT NULL AND cover_image<>''
            ) t ORDER BY RAND() LIMIT 1");
        $st->execute(array_merge($ids, $ids, $ids));
        $r = $st->fetch();
        return $r ? $r['img'] : '';
    }catch(Exception $e){ return ''; }
}

// Willekeurige foto uit de eigen fotogalerij van één dier (soort-niveau),
// als fallback wanneer er geen omslagfoto is ingesteld maar er wel al losse
// foto's zijn geupload.
function pb_animal_random_photo($animalId){
    try{
        $st = db()->prepare('SELECT image_path FROM photos WHERE animal_id=? ORDER BY RAND() LIMIT 1');
        $st->execute([(int)$animalId]);
        $r = $st->fetch();
        return $r ? $r['image_path'] : '';
    }catch(Exception $e){ return ''; }
}

// Sommige installaties hebben de title_en/description_en-kolommen (admin ->
// Vertalingen toevoegen) nog niet — controleer één keer per request en
// cache het resultaat, zodat de rest van de code die kolommen veilig kan
// opvragen zonder een query-fout te riskeren.
function pb_has_column($table, $column){
    static $cache = [];
    $key = $table.'.'.$column;
    if(!array_key_exists($key, $cache)){
        try{
            $st = db()->prepare("SHOW COLUMNS FROM $table LIKE ?");
            $st->execute([$column]);
            $cache[$key] = (bool)$st->fetch();
        }catch(Exception $e){ $cache[$key] = false; }
    }
    return $cache[$key];
}

function pb_render_subcategories($d, $ctx=[]){
    $catId = (int)($ctx['category_id'] ?? 0);
    if(!$catId) return '';
    $catEnCols = pb_has_column('categories','title_en') ? ', title_en, description_en' : '';
    $animalEnCols = pb_has_column('animals','title_en') ? ', title_en' : '';
    $rows = [];
    $st = db()->prepare("SELECT id, title, slug, description, cover_image$catEnCols FROM categories WHERE parent_id=? AND published=1 ORDER BY sort_order, title");
    $st->execute([$catId]);
    foreach($st as $r){
        $r['url'] = 'category.php?slug='.$r['slug'];
        if(empty($r['cover_image'])) $r['cover_image'] = pb_category_random_photo((int)$r['id']);
        $rows[] = $r;
    }
    $st = db()->prepare("SELECT id, title, slug, description, cover_image$animalEnCols FROM animals WHERE category_id=? AND published=1 ORDER BY sort_order, title");
    $st->execute([$catId]);
    foreach($st as $r){
        $r['url'] = 'animal.php?slug='.$r['slug'];
        if(empty($r['cover_image'])) $r['cover_image'] = pb_animal_random_photo((int)$r['id']);
        $rows[] = $r;
    }
    if(!$rows) return '<p style="text-align:center;color:var(--ink-soft)">'.e(t('nothing_in_category')).'</p>';
    $html = '<div class="grid">';
    foreach($rows as $r){
        $img = !empty($r['cover_image']) ? '<img src="'.e($r['cover_image']).'" alt="" loading="lazy">' : '';
        $desc = localized_field($r, 'description');
        $html .= '<article class="card"><a href="'.e($r['url']).'">'.$img.'</a><div class="pad"><h3>'.e(localized_field($r,'title')).'</h3>'
            .($desc !== '' ? '<p>'.e($desc).'</p>' : '')
            .'<a class="btn" href="'.e($r['url']).'">'.e(t('view')).'</a></div></article>';
    }
    return $html.'</div>';
}

function pb_render_categories_grid($d){
    $catEnCols = pb_has_column('categories','title_en') ? ', title_en, description_en' : '';
    $rows = db()->query("SELECT id, title, slug, description, cover_image$catEnCols FROM categories WHERE parent_id IS NULL AND published=1 ORDER BY sort_order, title")->fetchAll();
    if(!$rows) return '<p style="text-align:center;color:var(--ink-soft)">'.e(t('no_categories_yet')).'</p>';
    $html = '<div class="grid pb-cat-grid">';
    foreach($rows as $r){
        $url = 'category.php?slug='.$r['slug'];
        $photo = $r['cover_image'] ?: pb_category_random_photo((int)$r['id']);
        $img = $photo ? '<img src="'.e($photo).'" alt="" loading="lazy">' : '<div class="pb-cat-grid-noimg"></div>';
        $desc = localized_field($r, 'description');
        $html .= '<article class="card pb-cat-grid-card"><a href="'.e($url).'">'.$img.'</a><div class="pad"><h3>'.e(localized_field($r,'title')).'</h3>'
            .($desc !== '' ? '<p>'.e($desc).'</p>' : '')
            .'<a class="btn" href="'.e($url).'">'.e(t('discover')).'</a></div></article>';
    }
    return $html.'</div>';
}

// Vaste ingangspagina op de homepage om tussen Gewervelde en Ongewervelde
// dieren te kiezen en van daaruit verder te navigeren: twee foto's naast
// elkaar, elk met een titel eronder — tikken op de foto zelf navigeert.
// Geen instelbare linkbestemming (die twee overzichtspagina's bestaan al),
// enkel de 2 foto's en de teksten zijn aanpasbaar in de editor.
function pb_render_class_split($d){
    $title = trim($d['title'] ?? '');
    $gImg = trim($d['gewerveldeImage'] ?? '');
    $oImg = trim($d['ongewerveldeImage'] ?? '');
    $gLabel = trim($d['gewerveldeLabel'] ?? '') ?: 'Gewervelde dieren';
    $oLabel = trim($d['ongewerveldeLabel'] ?? '') ?: 'Ongewervelde dieren';
    $html = '<div class="pb-class-split">';
    if($title !== '') $html .= '<h2>'.e($title).'</h2>';
    $html .= '<div class="pb-class-split-cards">';
    $html .= '<a class="pb-class-split-card" href="gewervelde.php">'
        .($gImg !== '' ? '<img src="'.e($gImg).'" alt="" loading="lazy">' : '<div class="pb-class-split-noimg"></div>')
        .'<span>'.e($gLabel).'</span></a>';
    $html .= '<a class="pb-class-split-card" href="category.php?slug=ongewervelde">'
        .($oImg !== '' ? '<img src="'.e($oImg).'" alt="" loading="lazy">' : '<div class="pb-class-split-noimg"></div>')
        .'<span>'.e($oLabel).'</span></a>';
    $html .= '</div></div>';
    return $html;
}

// Telt foto's binnen blokken (image/gallery/slideshow), inclusief genest in
// columns/row-blokken — nodig omdat de photos/album_photos-tabellen enkel de
// oude, niet-pagebuilder foto's bijhouden en dus content die via blokken is
// toegevoegd (de normale weg tegenwoordig) anders onzichtbaar blijft voor de teller.
function pb_count_blocks_images($blocks){
    $count = 0;
    foreach((array)$blocks as $b){
        $type = $b['type'] ?? '';
        $data = $b['data'] ?? [];
        if($type === 'image'){
            if(!empty($data['src'])) $count++;
        } elseif($type === 'gallery' || $type === 'slideshow'){
            foreach((array)($data['images'] ?? []) as $img){ if(!empty($img['src'])) $count++; }
        } elseif($type === 'columns'){
            foreach((array)($data['cols'] ?? []) as $col){ $count += pb_count_blocks_images($col['blocks'] ?? []); }
        } elseif($type === 'row'){
            foreach((array)($data['cells'] ?? []) as $cell){ $count += pb_count_blocks_images($cell['blocks'] ?? []); }
        }
    }
    return $count;
}

function pb_count_total_photos(){
    static $cached = null;
    if($cached !== null) return $cached;
    try{
        $count = (int)db()->query('SELECT COUNT(*) c FROM photos')->fetch()['c'];
        $count += (int)db()->query('SELECT COUNT(*) c FROM album_photos')->fetch()['c'];
        foreach(['pages','animals','albums','posts','categories'] as $table){
            $rows = db()->query("SELECT blocks FROM $table WHERE blocks IS NOT NULL AND blocks <> '' AND blocks <> '[]'")->fetchAll();
            foreach($rows as $row) $count += pb_count_blocks_images(pb_decode_blocks($row['blocks']));
        }
        return $cached = $count;
    }catch(Exception $e){ return 0; }
}

// Aantal diersoorten met minstens één foto (oude photos-tabel of blokken)
// t.o.v. het totaal aantal aangemaakte diersoorten — voor de "X van de Y
// soorten al gefotografeerd"-voortgangsbalk.
function pb_species_progress(){
    static $cached = null;
    if($cached !== null) return $cached;
    try{
        $animals = db()->query('SELECT id, blocks FROM animals')->fetchAll();
        $total = count($animals);
        if(!$total) return $cached = [0, 0];
        $legacyCounts = [];
        foreach(db()->query('SELECT animal_id, COUNT(*) c FROM photos GROUP BY animal_id') as $row){
            $legacyCounts[(int)$row['animal_id']] = (int)$row['c'];
        }
        $with = 0;
        foreach($animals as $a){
            $has = ($legacyCounts[(int)$a['id']] ?? 0) > 0 || pb_count_blocks_images(pb_decode_blocks($a['blocks'] ?? null)) > 0;
            if($has) $with++;
        }
        return $cached = [$with, $total];
    }catch(Exception $e){ return [0, 0]; }
}

function pb_render_species_progress($d){
    [$with, $total] = pb_species_progress();
    $label = trim($d['label'] ?? '') ?: t('species_progress_label_simple');
    return '<div class="pb-photocount"><span class="pb-photocount-num">'.number_format($with, 0, ',', '.').'</span><span class="pb-photocount-label">'.e($label).'</span></div>';
}

function pb_render_photocount($d){
    $count = pb_count_total_photos();
    $label = trim($d['label'] ?? '') ?: t('photos_on_site');
    return '<div class="pb-photocount"><span class="pb-photocount-num">'.number_format($count, 0, ',', '.').'</span><span class="pb-photocount-label">'.e($label).'</span></div>';
}

function pb_render_slideshow($d){
    $images = $d['images'] ?? [];
    if(!$images) return '<div class="pbe-empty-col" style="min-height:140px">'.e(t('no_slideshow_photos_public')).'</div>';
    $interval = max(2, min(15, (int)($d['interval'] ?? 5)));
    $html = '<div class="pb-slideshow" data-interval="'.$interval.'000">';
    $html .= '<div class="pb-slideshow-track">';
    foreach($images as $i => $img){
        if(empty($img['src'])) continue;
        $html .= '<figure class="pb-slideshow-slide'.($i===0?' is-active':'').'"><img src="'.e($img['src']).'" alt="'.e($img['alt'] ?? '').'" loading="lazy">'
            .pb_gallery_zoo_badge($img['zoo_id'] ?? null)
            .(!empty($img['caption']) ? '<figcaption>'.e($img['caption']).'</figcaption>' : '').'</figure>';
    }
    $html .= '</div>';
    if(count($images) > 1){
        $html .= '<button type="button" class="pb-slideshow-nav pb-slideshow-prev" aria-label="Vorige">&#8249;</button>';
        $html .= '<button type="button" class="pb-slideshow-nav pb-slideshow-next" aria-label="Volgende">&#8250;</button>';
        $html .= '<div class="pb-slideshow-dots">';
        foreach($images as $i => $img){ $html .= '<button type="button" class="pb-slideshow-dot'.($i===0?' is-active':'').'" data-slide="'.$i.'" aria-label="Ga naar foto '.($i+1).'"></button>'; }
        $html .= '</div>';
    }
    return $html.'</div>';
}

// Bouwt een blokken-array die overeenkomt met wat de live pagina toont via de
// oude vaste opmaak (zie animal.php/category.php/album.php/post.php), voor
// content die nog geen echte blokken heeft. Wordt enkel gebruikt om de
// pagebuilder-editor te vullen zodat die niet leeg lijkt terwijl de live
// pagina wél inhoud toont — wordt pas echt opgeslagen als de gebruiker zelf
// op Opslaan drukt (zelfde principe als de homepage-omzet-knop).
function pb_default_blocks_for($type, $row){
    $defaultSettings = ['fontFamily'=>'','fontSize'=>'','textColor'=>'','bgColor'=>'','align'=>'left','paddingY'=>56,'paddingX'=>24,'radius'=>0,'shadow'=>'none','animation'=>'fade-up'];
    $heroSettings = array_merge($defaultSettings, ['align'=>'center','paddingY'=>'','paddingX'=>'','animation'=>'fade-in']);
    $blocks = [];

    if($type === 'animal'){
        $blocks[] = ['id'=>pb_new_block_id(), 'type'=>'hero', 'settings'=>$heroSettings,
            'data'=>['title'=>$row['title'] ?? '', 'subtitle'=>$row['description'] ?? '', 'buttonText'=>'', 'buttonHref'=>'#', 'bgImage'=>'', 'overlay'=>45]];
        $st = db()->prepare('SELECT * FROM photos WHERE animal_id=? ORDER BY sort_order,id DESC');
        $st->execute([$row['id']]);
        $photos = $st->fetchAll();
        if($photos){
            $images = [];
            foreach($photos as $p) $images[] = ['src'=>$p['image_path'], 'alt'=>$p['title'] ?? '', 'caption'=>$p['caption'] ?? ''];
            $blocks[] = ['id'=>pb_new_block_id(), 'type'=>'gallery', 'settings'=>$defaultSettings, 'data'=>['images'=>$images, 'columns'=>3, 'layout'=>'grid']];
        }
    } elseif($type === 'category'){
        $blocks[] = ['id'=>pb_new_block_id(), 'type'=>'hero', 'settings'=>$heroSettings,
            'data'=>['title'=>$row['title'] ?? '', 'subtitle'=>$row['description'] ?? '', 'buttonText'=>'', 'buttonHref'=>'#', 'bgImage'=>'', 'overlay'=>45]];
        $blocks[] = ['id'=>pb_new_block_id(), 'type'=>'subcategories', 'settings'=>$defaultSettings, 'data'=>[]];
    } elseif($type === 'album'){
        $blocks[] = ['id'=>pb_new_block_id(), 'type'=>'hero', 'settings'=>$heroSettings,
            'data'=>['title'=>$row['title'] ?? '', 'subtitle'=>$row['description'] ?? '', 'buttonText'=>'', 'buttonHref'=>'#', 'bgImage'=>'', 'overlay'=>45]];
        $st = db()->prepare('SELECT * FROM album_photos WHERE album_id=? ORDER BY sort_order,id DESC');
        $st->execute([$row['id']]);
        $photos = $st->fetchAll();
        if($photos){
            $images = [];
            foreach($photos as $p) $images[] = ['src'=>$p['image_path'], 'alt'=>$p['title'] ?? '', 'caption'=>$p['caption'] ?? ''];
            $blocks[] = ['id'=>pb_new_block_id(), 'type'=>'gallery', 'settings'=>$defaultSettings, 'data'=>['images'=>$images, 'columns'=>3, 'layout'=>'grid']];
        }
    } elseif($type === 'post'){
        $blocks[] = ['id'=>pb_new_block_id(), 'type'=>'title', 'settings'=>$defaultSettings, 'data'=>['text'=>$row['title'] ?? '', 'level'=>'h1']];
        if(!empty($row['cover_image'])){
            $blocks[] = ['id'=>pb_new_block_id(), 'type'=>'image', 'settings'=>$defaultSettings, 'data'=>['src'=>$row['cover_image'], 'alt'=>'', 'caption'=>'', 'width'=>'full', 'link'=>'', 'aspectRatio'=>null, 'widthPct'=>'']];
        }
        if(!empty($row['content'])){
            $blocks[] = ['id'=>pb_new_block_id(), 'type'=>'text', 'settings'=>$defaultSettings, 'data'=>['html'=>nl2br(e($row['content']))]];
        }
    }
    return $blocks;
}
