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
        'photos_on_site'    => ['nl' => 'foto\'s op deze website', 'en' => 'photos on this website'],

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

        'homepage_migration_notice_pre'  => ['nl' => 'Je homepage draait nog op de vaste basisopmaak, niet op blokken — daarom lijkt "Home" leeg in de editor. ', 'en' => 'Your homepage is still running on the fixed default layout, not on blocks — that\'s why "Home" looks empty in the editor. '],
        'homepage_migration_notice_link' => ['nl' => 'Zet dit één keer om naar blokken', 'en' => 'Convert it to blocks (one-time)'],
        'homepage_migration_notice_post' => ['nl' => ' om de homepage net als elke andere pagina te kunnen bewerken (bv. om het "Gewervelde / Ongewervelde"-blok toe te voegen).', 'en' => ' so you can edit the homepage just like any other page (e.g. to add the "Vertebrates / Invertebrates" block).'],

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
        'city_label'        => ['nl' => 'Stad', 'en' => 'City'],
        'country_label'     => ['nl' => 'Land', 'en' => 'Country'],
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

        // pagebuilder editor topbar + instellingen-modal
        'back'              => ['nl' => 'Terug', 'en' => 'Back'],
        'desktop'           => ['nl' => 'Desktop', 'en' => 'Desktop'],
        'mobile'            => ['nl' => 'Mobiel', 'en' => 'Mobile'],
        'all_saved'         => ['nl' => 'Alles opgeslagen', 'en' => 'All changes saved'],
        'view_type'         => ['nl' => 'Bekijk', 'en' => 'View'],
        'settings_btn'      => ['nl' => 'Instellingen', 'en' => 'Settings'],
        'type_settings'     => ['nl' => '-instellingen', 'en' => ' settings'],
        'live_published'    => ['nl' => 'Live (gepubliceerd)', 'en' => 'Live (published)'],
        'show_in_main_menu' => ['nl' => 'Tonen in hoofdmenu', 'en' => 'Show in main menu'],
        'set_as_homepage'   => ['nl' => 'Instellen als homepage', 'en' => 'Set as homepage'],
        'homepage_hint'     => ['nl' => 'Als homepage vervangt deze pagina de standaard-voorpagina volledig.', 'en' => 'As the homepage, this page completely replaces the default front page.'],
        'cover_photo_label' => ['nl' => 'Coverfoto (gebruikt in overzichten)', 'en' => 'Cover photo (used in overviews)'],
        'choose_other_photo' => ['nl' => 'Andere foto kiezen (of sleep hier)', 'en' => 'Choose another photo (or drop here)'],
        'upload_photo'      => ['nl' => 'Foto uploaden (of sleep hier)', 'en' => 'Upload photo (or drop here)'],
        'short_description' => ['nl' => 'Korte omschrijving', 'en' => 'Short description'],
        'short_description_placeholder' => ['nl' => 'Tekst die zichtbaar is onder de titel in overzichten (bv. op de homepage)', 'en' => 'Text shown under the title in overviews (e.g. on the homepage)'],
        'short_vs_seo_hint' => ['nl' => 'Dit is andere, zichtbare tekst dan de SEO-omschrijving hieronder (die is enkel voor zoekmachines).', 'en' => 'This is separate, visible text — different from the SEO description below (which is only for search engines).'],
        'no_category'       => ['nl' => 'Geen categorie', 'en' => 'No category'],
        'seo_title'         => ['nl' => 'SEO-titel', 'en' => 'SEO title'],
        'seo_description'   => ['nl' => 'SEO-omschrijving', 'en' => 'SEO description'],
        'seo_description_placeholder' => ['nl' => 'Korte omschrijving voor zoekmachines', 'en' => 'Short description for search engines'],
        'url_autoupdate_hint' => ['nl' => 'past automatisch mee met de titel bij opslaan', 'en' => 'updates automatically with the title when saved'],
        'type_category'     => ['nl' => 'Categorie', 'en' => 'Category'],

        // admin eenmalige tools (homepage-migratie, vertalingen, dubbele dieren)
        'hp_migrate_title'  => ['nl' => 'Homepage omzetten naar blokken', 'en' => 'Convert homepage to blocks'],
        'hp_migrate_done_published' => ['nl' => 'De Home-pagina had al blokken, maar stond nog op concept — nu gepubliceerd. De bestaande blokken zijn niet aangeraakt.', 'en' => 'The Home page already had blocks, but was still a draft — it is now published. The existing blocks were not touched.'],
        'hp_migrate_done_skip' => ['nl' => 'De Home-pagina had al blokken — niets aangepast, je huidige opbouw blijft ongemoeid.', 'en' => 'The Home page already had blocks — nothing was changed, your current layout is left untouched.'],
        'hp_migrate_done_created' => ['nl' => ' (nieuwe Home-pagina aangemaakt)', 'en' => ' (new Home page created)'],
        'hp_migrate_done_main' => ['nl' => 'Klaar%s. De homepage is nu opgebouwd uit gewone blokken — dezelfde titel, tekst en dierenkaarten als voorheen — en meteen gepubliceerd.', 'en' => 'Done%s. The homepage is now built from regular blocks — the same title, text and animal cards as before — and published right away.'],
        'hp_migrate_done_hint' => ['nl' => 'Ga naar <a href="pages.php">Pagina\'s</a> en open "Home" om verder aan te passen, bijvoorbeeld het "Gewervelde / Ongewervelde"-blok toevoegen.', 'en' => 'Go to <a href="pages.php">Pages</a> and open "Home" to make further changes, e.g. adding the "Vertebrates / Invertebrates" block.'],
        'to_pages'          => ['nl' => 'Naar Pagina\'s', 'en' => 'Go to Pages'],
        'hp_migrate_intro1' => ['nl' => 'Je homepage toont nu nog de vaste, niet-bewerkbare basisopmaak (titel + tekst uit Site-instellingen, plus een rooster met alle dieren) — dat is waarom de pagebuilder-editor voor "Home" leeg lijkt: die pagina zelf heeft nog geen blokken en staat nog op concept.', 'en' => 'Your homepage currently still shows the fixed, non-editable default layout (title + text from Site settings, plus a grid of all animals) — that\'s why the page builder editor looks empty for "Home": that page itself has no blocks yet and is still a draft.'],
        'hp_migrate_intro2' => ['nl' => 'Deze knop zet dat één keer om in gewone, versleepbare blokken — met exact dezelfde titel, tekst en dierenkaarten — en publiceert de pagina meteen. Daarna toont de editor precies wat er live staat, en kan je zelf blokken toevoegen zoals "Gewervelde / Ongewervelde".', 'en' => 'This button converts that, once, into regular, draggable blocks — with exactly the same title, text and animal cards — and publishes the page right away. After that, the editor shows exactly what is live, and you can add blocks yourself such as "Vertebrates / Invertebrates".'],
        'hp_migrate_safe_hint' => ['nl' => 'Veilig om te draaien: als de Home-pagina al blokken heeft, gebeurt er niets.', 'en' => 'Safe to run: if the Home page already has blocks, nothing happens.'],
        'convert_btn'       => ['nl' => 'Omzetten', 'en' => 'Convert'],

        'at_title'          => ['nl' => 'Vertalingen toevoegen (NL/EN)', 'en' => 'Add translations (NL/EN)'],
        'at_done'           => ['nl' => 'Klaar.', 'en' => 'Done.'],
        'at_db_updated'     => ['nl' => ' Database aangepast voor tweetaligheid.', 'en' => ' Database updated for bilingual support.'],
        'at_add_btn'        => ['nl' => 'Vertalingen toevoegen', 'en' => 'Add translations'],
        'at_translated_summary' => ['nl' => '%d categorienaam/namen vertaald, %d hadden al een Engelse naam (ongemoeid gelaten)', 'en' => '%d category name(s) translated, %d already had an English name (left untouched)'],
        'at_nomatch_summary' => ['nl' => ', %d herkende ik niet uit de standaardboom (zelf aangemaakte categorie?) — die kan je los vertalen bij het bewerken van die categorie.', 'en' => ', %d I did not recognise from the standard tree (a category you created yourself?) — you can translate those separately when editing that category.'],
        'to_categories'     => ['nl' => 'Naar Categorieën', 'en' => 'Go to Categories'],
        'view_english_site' => ['nl' => 'Bekijk de Engelse site', 'en' => 'View the English site'],
        'at_explain'        => ['nl' => 'De site heeft nu een taalknop (NL/EN) rechtsboven. Deze knop hier vult automatisch de Engelse naam in voor elke categorie die overeenkomt met de standaard taxonomieboom (bv. "Vissen" → "Fish"). Dieren-titels worden niet aangepast — dat zijn al Latijnse soortnamen. Veilig om te herdraaien: bestaande Engelse namen (ook zelf ingevulde) worden nooit overschreven.', 'en' => 'The site now has a language button (NL/EN) in the top right. This button automatically fills in the English name for every category that matches the standard taxonomy tree (e.g. "Vissen" → "Fish"). Animal titles are not changed — those are already Latin species names. Safe to run again: existing English names (including ones you typed yourself) are never overwritten.'],

        'fda_title'         => ['nl' => 'Dubbele dieren opruimen', 'en' => 'Clean up duplicate animals'],
        'cleanup_btn'       => ['nl' => 'Opruimen', 'en' => 'Clean up'],
        'fda_done_summary'  => ['nl' => 'Klaar. %d dubbele dier(en) samengevoegd, %d foto(\'s) verhuisd naar het overblijvende exemplaar met de schone link (zonder "-2"). Niets is verloren gegaan.', 'en' => 'Done. %d duplicate animal(s) merged, %d photo(s) moved to the remaining copy with the clean link (without "-2"). Nothing was lost.'],
        'fda_skipped_legit' => ['nl' => ' %d soortnaam(en) die bewust dubbel in de boom voorkomen (bv. Lathamus discolor) zijn overgeslagen — die blijven allebei apart bestaan, dat is correct.', 'en' => ' %d species name(s) that intentionally appear twice in the tree (e.g. Lathamus discolor) were skipped — both remain separate, which is correct.'],
        'to_animals_check'  => ['nl' => 'Naar Dieren', 'en' => 'Go to Animals'],
        'check_the_list'    => ['nl' => 'controleer de lijst.', 'en' => 'check the list.'],
        'view_site'         => ['nl' => 'Bekijk de site', 'en' => 'View the site'],
        'fda_explain1'      => ['nl' => 'Als je foto\'s uploadt maar ze niet op de verwachte link (zonder "-2" erachter) verschijnen, zijn er waarschijnlijk twee rijen voor diezelfde soort aangemaakt — dit gebeurt soms doordat de categorieboom onderweg verschoof. Dit voegt zulke duplicaten samen: alle foto\'s verhuizen naar het exemplaar met de schone link, de rest wordt verwijderd.', 'en' => 'If you upload photos but they don\'t show up on the expected link (without "-2" at the end), there are probably two rows for the same species — this sometimes happens because the category tree shifted along the way. This merges such duplicates: all photos move to the copy with the clean link, the rest is removed.'],
        'fda_explain2'      => ['nl' => 'Categorie, publicatiestatus en coverfoto worden overgenomen als het overblijvende exemplaar die zelf nog niet had. Niets gaat verloren. Veilig om meermaals te draaien.', 'en' => 'Category, publish status and cover photo are copied over if the remaining copy did not already have them. Nothing is lost. Safe to run multiple times.'],
        'fallback_preview_notice' => ['nl' => 'Dit item heeft nog geen eigen blokken — dit is een voorbeeld dat overeenkomt met wat nu live staat. Druk op Opslaan om het echt om te zetten naar bewerkbare blokken.', 'en' => 'This item doesn\'t have its own blocks yet — this is a preview matching what is currently live. Press Save to actually convert it to editable blocks.'],
        'drive_url_label'   => ['nl' => 'Google Drive-link (optioneel)', 'en' => 'Google Drive link (optional)'],
        'drive_url_hint'    => ['nl' => 'Als je hier een link invult, opent een klik op eender welke foto van dit dier die Drive-map in een nieuw tabblad, in plaats van de normale foto-zoom.', 'en' => 'If you fill in a link here, clicking any photo of this animal will open that Drive folder in a new tab, instead of the normal photo zoom.'],
        'no_slideshow_photos_public' => ['nl' => 'Nog geen foto\'s in de slideshow.', 'en' => 'No photos in the slideshow yet.'],
        'no_photos_filter_label' => ['nl' => 'Zonder foto\'s', 'en' => 'Without photos'],
        'with_photos_filter_label' => ['nl' => 'Met foto\'s', 'en' => 'With photos'],
        'all_photo_states'  => ['nl' => 'Alle (met of zonder foto\'s)', 'en' => 'All (with or without photos)'],
        'photos_label'      => ['nl' => 'Foto\'s', 'en' => 'Photos'],
        'no_photos_pill'    => ['nl' => 'Geen foto\'s', 'en' => 'No photos'],
        'drive_url_label_short' => ['nl' => 'Drive-link', 'en' => 'Drive link'],
        'bdl_title'         => ['nl' => 'Drive-links in bulk koppelen', 'en' => 'Bulk-link Drive links'],
        'bdl_explain'       => ['nl' => 'Upload een CSV-bestand (bv. opgeslagen vanuit Excel of Google Sheets als "CSV" — kolom 1 = naam, kolom 2 = link), of plak de lijst hieronder. De naam wordt exact vergeleken met de dier-titel. Titels die bewust dubbel in de boom voorkomen worden overgeslagen — die koppel je apart via de Drive-link-kolom in de Dieren-lijst. Deze pagina blijft altijd hier staan, dus je kan dit doen wanneer je maar wil.', 'en' => 'Upload a CSV file (e.g. saved from Excel or Google Sheets as "CSV" — column 1 = name, column 2 = link), or paste the list below. The name is matched exactly against the animal title. Titles that intentionally appear twice in the tree are skipped — link those separately via the Drive-link column in the Dieren list. This page always stays here, so you can do this whenever you want.'],
        'bdl_csv_label'     => ['nl' => 'CSV-bestand uploaden', 'en' => 'Upload CSV file'],
        'bdl_csv_hint'      => ['nl' => 'Werkt ook rechtstreeks met de export uit het Google Sheets-script hierboven (kolommen Pad/Naam/Link).', 'en' => 'Also works directly with the export from the Google Sheets script above (Path/Name/Link columns).'],
        'bdl_or'            => ['nl' => '— of —', 'en' => '— or —'],
        'bdl_textarea_label' => ['nl' => 'Naam, Link plakken (één per regel)', 'en' => 'Paste Name, Link (one per line)'],
        'bdl_submit_btn'    => ['nl' => 'Koppelen', 'en' => 'Link them'],
        'bdl_done_summary'  => ['nl' => 'Klaar. %d dier(en) gekoppeld aan een Drive-link.', 'en' => 'Done. %d animal(s) linked to a Drive link.'],
        'bdl_not_found_label' => ['nl' => 'Niet gevonden (naam kwam niet exact overeen met een dier-titel):', 'en' => 'Not found (name did not exactly match an animal title):'],
        'bdl_ambiguous_label' => ['nl' => 'Overgeslagen (naam komt dubbel voor, bewust twee dieren met deze titel):', 'en' => 'Skipped (name occurs twice, intentionally two animals with this title):'],
        'bdl_do_more'       => ['nl' => 'Nog een lijst koppelen', 'en' => 'Link another list'],
        'bulk_drive_links_btn' => ['nl' => 'Drive-links in bulk koppelen', 'en' => 'Bulk-link Drive links'],
    ];
}
