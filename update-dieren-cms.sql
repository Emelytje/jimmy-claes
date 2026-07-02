-- Update-script voor bestaande "Dieren door de lens" installatie
-- Voer dit volledige bestand in één keer uit via phpMyAdmin > Importeren
-- (of plak de inhoud in het SQL-tabblad van je database).

ALTER TABLE animals ADD COLUMN meta_title VARCHAR(160), ADD COLUMN meta_description VARCHAR(300);

CREATE TABLE IF NOT EXISTS posts(
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(160) NOT NULL,
  slug VARCHAR(180) UNIQUE NOT NULL,
  excerpt TEXT,
  content TEXT,
  cover_image VARCHAR(255),
  published TINYINT DEFAULT 1,
  meta_title VARCHAR(160),
  meta_description VARCHAR(300),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS albums(
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(160) NOT NULL,
  slug VARCHAR(180) UNIQUE NOT NULL,
  description TEXT,
  cover_image VARCHAR(255),
  layout VARCHAR(30) DEFAULT 'masonry',
  published TINYINT DEFAULT 1,
  sort_order INT DEFAULT 0,
  meta_title VARCHAR(160),
  meta_description VARCHAR(300),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS album_photos(
  id INT AUTO_INCREMENT PRIMARY KEY,
  album_id INT NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  title VARCHAR(160),
  caption TEXT,
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(album_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages(
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160),
  email VARCHAR(160),
  message TEXT,
  is_read TINYINT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
