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
- Drag-and-drop pagebuilder / thema-editor
- Gebruikersrollen (editor/fotograaf naast admin)
- Media-bibliotheek met zoeken/mappen
- Automatische back-ups
