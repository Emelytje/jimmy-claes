<?php
// Kopieer dit bestand naar config.php en vul je InfinityFree databasegegevens in.
// Die vind je bij InfinityFree > MySQL Databases.

define('DB_HOST', 'sqlXXX.infinityfree.com');
define('DB_NAME', 'if0_XXXXXXX_naam');
define('DB_USER', 'if0_XXXXXXX');
define('DB_PASS', 'wachtwoord');
define('SITE_URL', ''); // leeg laten mag

// Optioneel: Google Drive API-key voor automatisch foto's ophalen uit een
// gekoppelde Drive-map per dier (zie Drive-link bij een dier in de admin).
// Leeg laten = die functie staat gewoon uit, de handmatige Drive-link
// (dubbelklik op een foto) blijft altijd werken zonder key nodig.
define('GOOGLE_DRIVE_API_KEY', '');
