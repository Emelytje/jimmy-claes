<?php
/**
 * Tweetalige site (NL/EN). Taal wordt onthouden in een cookie ('site_lang'),
 * omgeschakeld via ?lang=nl of ?lang=en (herlaadt dezelfde pagina zonder de
 * parameter). t($key) geeft vaste teksten (knoppen, labels) in de juiste
 * taal; localized_field($row,'title') geeft databaseinhoud (categorienaam,
 * beschrijving) terug in het Engels als er een *_en variant is ingevuld,
 * anders valt het terug op de Nederlandse (hoofd)waarde. Latijnse
 * soortnamen worden nergens vertaald — die staan gewoon als titel/omschrijving,
 * geen *_en tegenhanger nodig.
 */

if(isset($_GET['lang']) && in_array($_GET['lang'], ['nl', 'en'], true)){
    setcookie('site_lang', $_GET['lang'], time() + 60*60*24*365, '/');
    $_COOKIE['site_lang'] = $_GET['lang'];
    $qs = $_GET;
    unset($qs['lang']);
    $path = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: '.$path.($qs ? '?'.http_build_query($qs) : ''));
    exit;
}

function current_lang(){
    return (isset($_COOKIE['site_lang']) && $_COOKIE['site_lang'] === 'en') ? 'en' : 'nl';
}

function lang_switch_html(){
    $lang = current_lang();
    $other = $lang === 'nl' ? 'en' : 'nl';
    $qs = $_GET;
    $qs['lang'] = $other;
    $url = strtok($_SERVER['REQUEST_URI'], '?').'?'.http_build_query($qs);
    return '<a href="'.e($url).'" class="lang-switch" title="'.($lang === 'nl' ? 'Switch to English' : 'Overschakelen naar Nederlands').'">'.($other === 'en' ? 'EN' : 'NL').'</a>';
}

// Databaseveld (title/description) in de juiste taal: *_en als die is
// ingevuld en de site in het Engels staat, anders de gewone (NL) waarde.
function localized_field($row, $field){
    if(!is_array($row)) return '';
    if(current_lang() === 'en' && !empty($row[$field.'_en'])) return $row[$field.'_en'];
    return $row[$field] ?? '';
}

function t($key){
    static $dict = null;
    if($dict === null) $dict = i18n_dictionary();
    $lang = current_lang();
    if(!isset($dict[$key])) return $key;
    return $dict[$key][$lang] ?? $dict[$key]['nl'];
}

function i18n_dictionary(){
    return [
        // navigatie / algemeen
        'nav_home'          => ['nl' => 'Home',        'en' => 'Home'],
        'nav_contact'       => ['nl' => 'Contact',     'en' => 'Contact'],
        'nav_about'         => ['nl' => 'Over ons',    'en' => 'About us'],
        'nav_animals'       => ['nl' => 'Dieren',      'en' => 'Animals'],
        'back_button'       => ['nl' => 'Vorige pagina', 'en' => 'Previous page'],
        'discover'          => ['nl' => 'Ontdek',      'en' => 'Discover'],
        'view'              => ['nl' => 'Bekijk',      'en' => 'View'],
        'view_photos'       => ['nl' => 'Bekijk foto’s', 'en' => 'View photos'],
        'no_photos_yet'     => ['nl' => 'Nog geen foto’s van', 'en' => 'No photos yet of'],
        'more_soon'         => ['nl' => 'binnenkort meer!', 'en' => 'more coming soon!'],
        'nothing_in_category' => ['nl' => 'Nog niets in deze categorie.', 'en' => 'Nothing in this category yet.'],
        'no_categories_yet' => ['nl' => 'Nog geen categorieën om te tonen.', 'en' => 'No categories to show yet.'],
        'no_classes_yet'    => ['nl' => 'Nog geen klassen om te tonen.', 'en' => 'No classes to show yet.'],
        'no_content_yet'    => ['nl' => 'Nog geen content om te tonen.', 'en' => 'No content to show yet.'],
        'form_name'         => ['nl' => 'Naam', 'en' => 'Name'],
        'form_email_optional' => ['nl' => 'E-mail (optioneel)', 'en' => 'Email (optional)'],
        'form_message'      => ['nl' => 'Bericht', 'en' => 'Message'],
        'form_send'         => ['nl' => 'Versturen', 'en' => 'Send'],
        'thanks_message'    => ['nl' => 'Bedankt voor je bericht! We nemen zo snel mogelijk contact op.', 'en' => 'Thanks for your message! We\'ll get back to you as soon as possible.'],
        'fill_required'     => ['nl' => 'Vul minstens je naam en bericht in.', 'en' => 'Please fill in at least your name and message.'],
        'fill_valid_email'  => ['nl' => 'Vul een geldig e-mailadres in of laat het veld leeg.', 'en' => 'Enter a valid email address or leave it blank.'],
        'albums_title'      => ['nl' => 'Albums', 'en' => 'Albums'],
        'view_album'        => ['nl' => 'Bekijk album', 'en' => 'View album'],
        'blog_title'        => ['nl' => 'Blog', 'en' => 'Blog'],
        'read_more'         => ['nl' => 'Lees verder', 'en' => 'Read more'],
        'login_wrong'       => ['nl' => 'Verkeerde login.', 'en' => 'Incorrect login.'],
        'install_done'      => ['nl' => 'Installatie klaar. Log nu in.', 'en' => 'Installation complete. Log in now.'],
        'page_not_found'    => ['nl' => 'Pagina niet gevonden', 'en' => 'Page not found'],
        'animals_per_page'  => ['nl' => 'Pagina’s per dier', 'en' => 'Species pages'],
        'vertebrates'       => ['nl' => 'Gewervelde dieren', 'en' => 'Vertebrates'],
        'invertebrates'     => ['nl' => 'Ongewervelde dieren', 'en' => 'Invertebrates'],
        'footer_by'         => ['nl' => 'Website door', 'en' => 'Website by'],

        // login
        'login_title'       => ['nl' => 'Welkom terug', 'en' => 'Welcome back'],
        'login_username'    => ['nl' => 'Gebruikersnaam', 'en' => 'Username'],
        'login_password'    => ['nl' => 'Wachtwoord', 'en' => 'Password'],
        'login_button'      => ['nl' => 'Inloggen', 'en' => 'Log in'],

        // admin sidebar
        'admin_dashboard'   => ['nl' => 'Dashboard', 'en' => 'Dashboard'],
        'admin_pages'       => ['nl' => 'Pagina’s', 'en' => 'Pages'],
        'admin_animals'     => ['nl' => 'Dieren', 'en' => 'Animals'],
        'admin_categories'  => ['nl' => 'Categorieën', 'en' => 'Categories'],
        'admin_zoos'        => ['nl' => 'Dierentuinen', 'en' => 'Zoos'],
        'admin_messages'    => ['nl' => 'Berichten', 'en' => 'Messages'],
        'admin_settings'    => ['nl' => 'Site-instellingen', 'en' => 'Site settings'],
        'admin_view_site'   => ['nl' => 'Bekijk site', 'en' => 'View site'],
        'admin_logout'      => ['nl' => 'Uitloggen', 'en' => 'Log out'],

        // veelgebruikte admin-acties/labels
        'save'              => ['nl' => 'Opslaan', 'en' => 'Save'],
        'edit'              => ['nl' => 'Bewerken', 'en' => 'Edit'],
        'delete'            => ['nl' => 'Verwijder', 'en' => 'Delete'],
        'create'            => ['nl' => 'Aanmaken', 'en' => 'Create'],
        'live'              => ['nl' => 'Live', 'en' => 'Live'],
        'draft'             => ['nl' => 'Concept', 'en' => 'Draft'],
        'hidden'            => ['nl' => 'Verborgen', 'en' => 'Hidden'],
        'title_label'       => ['nl' => 'Titel', 'en' => 'Title'],
        'status'            => ['nl' => 'Status', 'en' => 'Status'],
        'link'              => ['nl' => 'Link', 'en' => 'Link'],
        'visits'            => ['nl' => 'Bezoeken', 'en' => 'Visits'],
        'created'           => ['nl' => 'Aangemaakt', 'en' => 'Created'],
        'category_label'    => ['nl' => 'Categorie', 'en' => 'Category'],
        'search_by_name'    => ['nl' => 'Zoeken op naam', 'en' => 'Search by name'],
        'filter'            => ['nl' => 'Filteren', 'en' => 'Filter'],
        'clear_filter'      => ['nl' => 'Wis filter', 'en' => 'Clear filter'],
        'class_label'       => ['nl' => 'Klasse', 'en' => 'Class'],
        'all_classes'       => ['nl' => 'Alle klassen', 'en' => 'All classes'],
    ];
}
