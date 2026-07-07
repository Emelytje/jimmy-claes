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
    // <a> keeps a vetted href/target/rel; every other allowed tag loses ALL
    // attributes (blocks onclick/onmouseover/style="javascript:..." etc.).
    $html = preg_replace_callback('/<a\b[^>]*>/i', function($m){
        $href = '';
        if(preg_match('/href\s*=\s*"([^"]*)"/i', $m[0], $h)) $href = $h[1];
        elseif(preg_match('/href\s*=\s*\'([^\']*)\'/i', $m[0], $h)) $href = $h[1];
        if($href!=='' && (preg_match('#^(https?:)?//#i',$href) || str_starts_with($href,'/') || str_starts_with($href,'#') || preg_match('/^[a-z0-9_\-]+\.php/i',$href))){
            return '<a href="'.e($href).'" target="_blank" rel="noopener">';
        }
        return '<a>';
    }, $html);
    $html = preg_replace('#<(/?(?:p|br|b|strong|i|em|u|span|ul|ol|li))\b[^>]*>#i', '<$1>', $html);
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

function render_blocks($blocks, $depth=0){
    if(!is_array($blocks) || $depth > 2) return '';
    $out = '';
    foreach($blocks as $block){ $out .= render_block($block, $depth); }
    return $out;
}

function render_block($block, $depth=0){
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
        case 'columns':    $inner = pb_render_columns($data, $depth); break;
        case 'row':        $inner = pb_render_row($data, $depth); break;
        case 'recent':     $inner = pb_render_recent($data); break;
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
    $caption = !empty($d['caption']) ? '<figcaption>'.e($d['caption']).'</figcaption>' : '';

    $figureStyle = '';
    if(isset($d['widthPct']) && $d['widthPct']!==''){
        $wp = max(10, min(100, (float)$d['widthPct']));
        $figureStyle = ' style="max-width:'.$wp.'%;margin-left:auto;margin-right:auto"';
    }
    return '<figure class="pb-figure"'.$figureStyle.'>'.$img.$caption.'</figure>';
}

function pb_safe_aspect_ratio($v){
    $n = (float)$v;
    if($n < 0.2 || $n > 6) return null;
    return (string)round($n, 4);
}

function pb_render_gallery($d){
    $cols = max(2, min(4, (int)($d['columns'] ?? 3)));
    $layout = ($d['layout'] ?? 'grid') === 'masonry' ? 'masonry' : 'grid';
    $html = '<div class="pb-gallery pb-gallery-'.$layout.'" style="--pb-cols:'.$cols.'">';
    foreach(($d['images'] ?? []) as $img){
        if(empty($img['src'])) continue;
        $cls = 'pb-gallery-item'.((($img['size'] ?? '') === 'large') ? ' pb-gallery-item-lg' : '');
        $html .= '<figure class="'.$cls.'"><img src="'.e($img['src']).'" alt="'.e($img['alt'] ?? '').'" loading="lazy">';
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

function pb_render_columns($d, $depth){
    $cols = $d['cols'] ?? [];
    $count = max(1, min(4, count($cols) ?: (int)($d['count'] ?? 2)));
    $gap = isset($d['gap']) ? (int)$d['gap'] : 32;
    $html = '<div class="pb-columns-grid pb-columns-'.$count.'" style="--pb-gap:'.$gap.'px">';
    foreach($cols as $col){
        $html .= '<div class="pb-column">'.render_blocks($col['blocks'] ?? [], $depth+1).'</div>';
    }
    return $html.'</div>';
}

function pb_render_row($d, $depth){
    $cells = $d['cells'] ?? [];
    if(!$cells) return '';
    $gap = isset($d['gap']) ? (int)$d['gap'] : 24;
    $stack = (!array_key_exists('mobileStack', $d) || $d['mobileStack']) ? 1 : 0;
    $html = '<div class="pb-row-flex" data-stack="'.$stack.'" style="--pb-row-gap:'.$gap.'px">';
    $count = count($cells);
    foreach($cells as $cell){
        $w = isset($cell['widthPct']) ? max(5, min(95, (float)$cell['widthPct'])) : (100 / max(1,$count));
        $html .= '<div class="pb-cell" style="width:'.round($w,4).'%">'.render_blocks($cell['blocks'] ?? [], $depth+1).'</div>';
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
    if(!$rows) return '<p style="text-align:center;color:var(--ink-soft)">Nog geen content om te tonen.</p>';
    $html = '<div class="grid">';
    foreach($rows as $r){
        $img = !empty($r['cover_image']) ? '<img src="'.e($r['cover_image']).'" alt="" loading="lazy">' : '';
        $html .= '<article class="card"><a href="'.e($r['url']).'">'.$img.'</a><div class="pad"><h3>'.e($r['title']).'</h3><a class="btn" href="'.e($r['url']).'">Bekijk</a></div></article>';
    }
    return $html.'</div>';
}

function pb_render_contact($d){
    $title = !empty($d['title']) ? '<h3>'.e($d['title']).'</h3>' : '';
    $redirect = e($_SERVER['REQUEST_URI'] ?? '/');
    return $title.'<form method="post" action="submit-message.php" class="pb-contact-form">'
        .csrf_field()
        .'<input type="hidden" name="redirect" value="'.$redirect.'">'
        .'<label>Naam<input name="name" required></label>'
        .'<label>E-mail (optioneel)<input type="email" name="email"></label>'
        .'<label>Bericht<textarea name="message" rows="5" required></textarea></label>'
        .'<button type="submit" class="pb-btn pb-btn-solid pb-btn-md">'.e($d['buttonText'] ?? 'Versturen').'</button>'
        .'</form>';
}
