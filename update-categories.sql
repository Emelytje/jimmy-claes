-- Update-script: dieren-categorieën (taxonomie) met onbeperkte nesting.
-- Voer dit volledige bestand in één keer uit via phpMyAdmin > Importeren.
-- Nieuwe installaties (via install.php) hoeven dit niet te doen.

CREATE TABLE IF NOT EXISTS categories(id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(160) NOT NULL, slug VARCHAR(180) UNIQUE NOT NULL, parent_id INT DEFAULT NULL, description TEXT, cover_image VARCHAR(255), blocks LONGTEXT, published TINYINT DEFAULT 0, sort_order INT DEFAULT 0, meta_title VARCHAR(160), meta_description VARCHAR(300), views INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(parent_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE animals ADD COLUMN category_id INT DEFAULT NULL, ADD INDEX(category_id);
