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

        // dashboard
        'pages_total'       => ['nl' => 'Pagina’s totaal', 'en' => 'Total pages'],
        'content_total'     => ['nl' => 'Alle content', 'en' => 'All content'],
        'new_messages'      => ['nl' => 'Nieuwe berichten', 'en' => 'New messages'],
        'most_viewed'       => ['nl' => 'Meest bekeken', 'en' => 'Most viewed'],
        'most_viewed_desc'  => ['nl' => 'Over alle pagina’s, dieren, albums en blogposts.', 'en' => 'Across all pages, animals, albums and blog posts.'],
        'type_label'        => ['nl' => 'Type', 'en' => 'Type'],
        'no_visits_yet'     => ['nl' => 'Nog geen bezoeken geregistreerd.', 'en' => 'No visits recorded yet.'],
        'recently_edited'   => ['nl' => 'Recent bewerkt', 'en' => 'Recently edited'],
        'recently_edited_desc' => ['nl' => 'De laatste pagina’s waar je aan werkte.', 'en' => 'The pages you worked on most recently.'],
        'no_pages_yet'      => ['nl' => 'Nog geen pagina’s', 'en' => 'No pages yet'],
        'build_first_page'  => ['nl' => 'Bouw je eerste pagina met de drag-and-drop pagebuilder.', 'en' => 'Build your first page with the drag-and-drop page builder.'],
        'new_page_btn'      => ['nl' => '+ Nieuwe pagina', 'en' => '+ New page'],
        'type_page'         => ['nl' => 'Pagina', 'en' => 'Page'],
        'type_animal'       => ['nl' => 'Dier', 'en' => 'Animal'],
        'type_album'        => ['nl' => 'Album', 'en' => 'Album'],
        'type_post'         => ['nl' => 'Blogpost', 'en' => 'Blog post'],

        // pagina's / dieren aanmaakformulier
        'new_page_title_label' => ['nl' => 'Titel van de nieuwe pagina', 'en' => 'Title of the new page'],
        'new_animal_title_label' => ['nl' => 'Titel van het nieuwe dier', 'en' => 'Title of the new animal'],
        'new_category_title_label' => ['nl' => 'Titel van de nieuwe categorie', 'en' => 'Title of the new category'],
        'title_placeholder' => ['nl' => 'Titel', 'en' => 'Title'],
        'in_menu'           => ['nl' => 'In menu', 'en' => 'In menu'],
        'yes'               => ['nl' => 'Ja', 'en' => 'Yes'],
        'no'                => ['nl' => 'Nee', 'en' => 'No'],
        'updated'           => ['nl' => 'Bijgewerkt', 'en' => 'Updated'],
        'slug_label'        => ['nl' => 'Slug', 'en' => 'Slug'],
        'create_page_btn'   => ['nl' => '+ Pagina aanmaken', 'en' => '+ Create page'],
        'create_btn'        => ['nl' => '+ Aanmaken', 'en' => '+ Create'],
        'parent_category'   => ['nl' => 'Bovenliggende categorie', 'en' => 'Parent category'],
        'none_top_level'    => ['nl' => 'Geen (hoofdcategorie)', 'en' => 'None (top-level category)'],
        'english_name'      => ['nl' => 'Engelse naam', 'en' => 'English name'],

        // dierentuinen (zoos)
        'zoos_heading'      => ['nl' => 'Dierentuinen in de hoofdnavigatie', 'en' => 'Zoos in the main navigation'],
        'zoos_desc'         => ['nl' => 'Deze links vervangen de dierenklassen bovenaan de site. De klassen (Amfibieën, Ongewervelde, enz.) zijn nu bereikbaar via de knoppen op de homepage.', 'en' => 'These links replace the animal classes at the top of the site. The classes (Amphibians, Invertebrates, etc.) are now reachable via the buttons on the homepage.'],
        'name_label'        => ['nl' => 'Naam', 'en' => 'Name'],
        'website_url'       => ['nl' => 'Website (URL)', 'en' => 'Website (URL)'],
        'add_btn'           => ['nl' => '+ Toevoegen', 'en' => '+ Add'],
        'name_url'          => ['nl' => 'Naam / URL', 'en' => 'Name / URL'],
        'no_zoos_yet'       => ['nl' => 'Nog geen dierentuinen', 'en' => 'No zoos yet'],
        'add_first_zoo'     => ['nl' => 'Voeg hierboven je eerste link toe, bijv. Zoo Antwerpen.', 'en' => 'Add your first link above, e.g. Antwerp Zoo.'],
        'confirm_delete_zoo' => ['nl' => '\' verwijderen uit de navigatie?', 'en' => '\' remove from the navigation?'],

        // berichten (messages)
        'messages_desc'     => ['nl' => 'Berichten via het contactformulier komen hier altijd terecht, ook als de e-mailmelding (indien ingesteld bij Site-instellingen) niet aankomt.', 'en' => 'Messages via the contact form always end up here too, even if the email notification (if set up in Site settings) doesn\'t arrive.'],
        'email_label'       => ['nl' => 'E-mail', 'en' => 'Email'],
        'message_label'     => ['nl' => 'Bericht', 'en' => 'Message'],
        'date_label'        => ['nl' => 'Datum', 'en' => 'Date'],
        'new_label'         => ['nl' => 'Nieuw', 'en' => 'New'],
        'read_label'        => ['nl' => 'Gelezen', 'en' => 'Read'],
        'no_messages_yet'   => ['nl' => 'Nog geen berichten.', 'en' => 'No messages yet.'],
        'confirm_delete_message' => ['nl' => 'Bericht definitief verwijderen?', 'en' => 'Permanently delete this message?'],

        // site-instellingen
        'site_name'         => ['nl' => 'Sitenaam', 'en' => 'Site name'],
        'intro_title_label' => ['nl' => 'Introtitel (homepage)', 'en' => 'Intro title (homepage)'],
        'intro_text_label'  => ['nl' => 'Introtekst (homepage)', 'en' => 'Intro text (homepage)'],
        'seo_desc_label'    => ['nl' => 'SEO-omschrijving (standaard)', 'en' => 'SEO description (default)'],
        'contact_email_label' => ['nl' => 'Contact-e-mailadres', 'en' => 'Contact email address'],
        'contact_email_hint' => ['nl' => 'Berichten via het contactformulier worden hierheen gemaild.', 'en' => 'Messages via the contact form are emailed here.'],
        'primary_color'     => ['nl' => 'Hoofdkleur', 'en' => 'Primary color'],
        'accent_color'      => ['nl' => 'Accentkleur', 'en' => 'Accent color'],
        'heading_font'      => ['nl' => 'Lettertype (koppen)', 'en' => 'Font (headings)'],
        'class_banner_colors' => ['nl' => 'Bannerkleuren per dierklasse', 'en' => 'Banner colors per animal class'],
        'class_banner_colors_desc' => ['nl' => 'De achtergrondkleur boven de titel op categorie- en dierenpagina\'s, per klasse (geldt ook voor alle sub-categorieën eronder).', 'en' => 'The background color above the title on category and animal pages, per class (also applies to all sub-categories underneath).'],
        'settings_saved'    => ['nl' => 'Instellingen opgeslagen.', 'en' => 'Settings saved.'],
        'login_credentials' => ['nl' => 'Inloggegevens', 'en' => 'Login credentials'],
        'credentials_updated' => ['nl' => 'Inloggegevens bijgewerkt.', 'en' => 'Login credentials updated.'],
        'current_password'  => ['nl' => 'Huidig wachtwoord', 'en' => 'Current password'],
        'current_password_hint' => ['nl' => 'Vereist om te bevestigen', 'en' => 'Required to confirm'],
        'new_password'      => ['nl' => 'Nieuw wachtwoord', 'en' => 'New password'],
        'new_password_hint' => ['nl' => 'Laat leeg om wachtwoord niet te wijzigen', 'en' => 'Leave blank to keep the current password'],
        'new_password_confirm' => ['nl' => 'Nieuw wachtwoord (bevestig)', 'en' => 'New password (confirm)'],
        'new_password_confirm_hint' => ['nl' => 'Herhaal nieuw wachtwoord', 'en' => 'Repeat new password'],
        'save_credentials'  => ['nl' => 'Inloggegevens opslaan', 'en' => 'Save login credentials'],
        'not_provided'      => ['nl' => '(niet opgegeven)', 'en' => '(not provided)'],
        'messages_appear_here' => ['nl' => 'Berichten via het contactformulier verschijnen hier.', 'en' => 'Messages via the contact form appear here.'],
        'confirm_delete_message_prefix' => ['nl' => 'Bericht van \'', 'en' => 'Permanently delete the message from \''],
        'confirm_delete_message_suffix' => ['nl' => '\' definitief verwijderen?', 'en' => '\'?'],
        'err_wrong_current_password' => ['nl' => 'Huidig wachtwoord is onjuist.', 'en' => 'Current password is incorrect.'],
        'err_username_empty' => ['nl' => 'Gebruikersnaam mag niet leeg zijn.', 'en' => 'Username cannot be empty.'],
        'err_password_too_short' => ['nl' => 'Nieuw wachtwoord moet minstens 8 tekens zijn.', 'en' => 'New password must be at least 8 characters.'],
        'err_password_mismatch' => ['nl' => 'Nieuw wachtwoord komt niet overeen met de bevestiging.', 'en' => 'New password doesn\'t match the confirmation.'],
        'err_username_taken' => ['nl' => 'Die gebruikersnaam is al in gebruik.', 'en' => 'That username is already taken.'],
        'confirm_delete_content_suffix' => ['nl' => '\' definitief verwijderen?', 'en' => '\'? This cannot be undone.'],
        'no_items_yet_prefix' => ['nl' => 'Nog geen ', 'en' => 'No '],
        'create_first_hint' => ['nl' => 'Maak hierboven de eerste aan.', 'en' => 'Create the first one above.'],
        'none_dash'         => ['nl' => '— geen —', 'en' => '— none —'],
        'confirm_delete_page' => ['nl' => 'Pagina \'', 'en' => 'Permanently delete the page \''],
        'no_pages_create_first' => ['nl' => 'Maak hierboven je eerste pagina aan.', 'en' => 'Create your first page above.'],
    ];
}
