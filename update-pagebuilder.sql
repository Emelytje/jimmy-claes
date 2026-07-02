-- Update-script voor bestaande "Dieren door de lens" installatie: pagebuilder
-- Voer dit volledige bestand in één keer uit via phpMyAdmin > Importeren
-- (of plak de inhoud in het SQL-tabblad van je database).
-- Nieuwe installaties (via install.php) hoeven dit niet te doen.

CREATE TABLE IF NOT EXISTS pages(
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(160) NOT NULL,
  slug VARCHAR(180) UNIQUE NOT NULL,
  blocks LONGTEXT,
  published TINYINT DEFAULT 0,
  show_in_nav TINYINT DEFAULT 0,
  meta_title VARCHAR(160),
  meta_description VARCHAR(300),
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
