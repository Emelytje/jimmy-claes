DIEREN DOOR DE LENS - CMS VOOR INFINITYFREE

INSTALLATIE
1. Pak deze ZIP uit.
2. Upload de INHOUD van de map naar htdocs op InfinityFree.
3. Open in je browser: jouwdomein/install.php
4. Vul je MySQL gegevens in:
   Host: bv. sql110.infinityfree.com
   Database: bv. if0_42311634_jimmy
   Gebruiker: bv. if0_42311634
   Wachtwoord: jouw InfinityFree MySQL wachtwoord
5. Kies je admin gebruikersnaam en wachtwoord.
6. Klik op installeren.
7. Login via jouwdomein/login.php of verborgen via het jaartal in de footer.

BELANGRIJK - VERWIJDER install.php NA INSTALLATIE. Het bestand blokkeert
zichzelf automatisch zodra config.php bestaat, maar verwijderen (of
hernoemen) is nog steeds de veiligste optie.

WAT KAN DIT CMS?
- Verborgen login via footer-jaartal
- Admin dashboard
- Dierenpagina's maken en aanpassen
- Coverfoto uploaden
- Foto's uploaden per dier
- Titel en tekst per foto
- Raster of Pinterest/masonry layout
- Kleuren en lettertype aanpassen
- Home tekst aanpassen
- Blogmodule (nieuwsberichten met eigen pagina)
- Albums (foto's groeperen los van een specifiek dier, bv. "Vossen", "Herten")
- Contactformulier op de site + overzicht van berichten in de admin
- SEO: meta title/description per dierenpagina, album en blogpost, automatische sitemap.php en robots.txt
- CSRF-beveiliging op alle admin-formulieren en login
- Data in MySQL, foto's in uploads map
- Drag-and-drop pagebuilder (admin/pages.php, admin/content.php): eigen
  pagina's, dierenpagina's, albums en blogposts bouwen uit blokken (hero,
  titel, tekst, foto, fotogalerij, video, knop, divider, quote, kolommen,
  vrije rijen met naast-elkaar-slepen en resizen, "recent toegevoegd",
  contactformulier, eigen HTML), met live instellingen voor lettertype
  (Google Fonts), kleur, uitlijning, padding, radius, schaduw en
  scroll-animaties. Zie "PAGEBUILDER" hieronder.
- Eén pagina instellen als homepage (vervangt de standaard-voorpagina).
- Bezoekstatistieken per pagina/dier/album/blogpost + "meest bekeken"
  overzicht in het dashboard.

PAGEBUILDER GEBRUIKEN
1. Log in en ga naar Beheer > Pagina's.
2. Maak een nieuwe pagina aan (titel invullen, "Pagina aanmaken").
3. Je komt in de canvas-editor: sleep blokken vanuit het paneel links naar
   het canvas in het midden. Versleep blokken om ze te herordenen.
4. Klik op een blok om het te selecteren; rechts verschijnt het
   instellingenpaneel (stijl + inhoud). Dubbelklik tekst in het canvas om
   ze rechtstreeks te bewerken.
5. Klap "Instellingen" open om de pagina te publiceren, in het hoofdmenu
   te tonen en SEO-titel/omschrijving in te stellen.
6. Wijzigingen worden automatisch (na 1,5 sec) en bij "Opslaan" bewaard.
7. Bekijk de live pagina via "Bekijk pagina" of jouwdomein/page.php?slug=....

BIJWERKEN VAN EEN BESTAANDE INSTALLATIE (al eerder geïnstalleerd)
Als je deze update gebruikt op een site die al draaide met de vorige
versie, moet je zelf 2 SQL-regels uitvoeren via phpMyAdmin (onderaan
in database.sql staan ze ook als commentaar):

  ALTER TABLE animals ADD COLUMN meta_title VARCHAR(160), ADD COLUMN meta_description VARCHAR(300);
  CREATE TABLE IF NOT EXISTS posts(id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(160) NOT NULL, slug VARCHAR(180) UNIQUE NOT NULL, excerpt TEXT, content TEXT, cover_image VARCHAR(255), published TINYINT DEFAULT 1, meta_title VARCHAR(160), meta_description VARCHAR(300), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  CREATE TABLE IF NOT EXISTS albums(id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(160) NOT NULL, slug VARCHAR(180) UNIQUE NOT NULL, description TEXT, cover_image VARCHAR(255), layout VARCHAR(30) DEFAULT 'masonry', published TINYINT DEFAULT 1, sort_order INT DEFAULT 0, meta_title VARCHAR(160), meta_description VARCHAR(300), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  CREATE TABLE IF NOT EXISTS album_photos(id INT AUTO_INCREMENT PRIMARY KEY, album_id INT NOT NULL, image_path VARCHAR(255) NOT NULL, title VARCHAR(160), caption TEXT, sort_order INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(album_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  CREATE TABLE IF NOT EXISTS messages(id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(160), email VARCHAR(160), message TEXT, is_read TINYINT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

Nieuwe installaties hoeven dit niet te doen, install.php maakt alles automatisch aan.

NOG NIET AANWEZIG (mogelijke volgende stappen)
- Gebruikersrollen (editor/fotograaf naast admin)
- Media-bibliotheek met zoeken/mappen
- Automatische back-ups

BIJWERKEN VOOR DE PAGEBUILDER (al eerder geïnstalleerd zonder pagebuilder)
Voer update-pagebuilder.sql uit via phpMyAdmin > Importeren. Nieuwe
installaties (via install.php) hoeven dit niet te doen.

BIJWERKEN VOOR DIEREN/ALBUMS/BLOG VIA DE PAGEBUILDER + STATISTIEKEN
Voer update-content-types.sql uit via phpMyAdmin > Importeren. Nieuwe
installaties (via install.php) hoeven dit niet te doen.
