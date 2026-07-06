-- Update-script: dieren/albums/blogposts bewerkbaar via de pagebuilder +
-- bezoekstatistieken + homepage-als-pagina.
-- Voer dit volledige bestand in één keer uit via phpMyAdmin > Importeren.
-- Nieuwe installaties (via install.php) hoeven dit niet te doen.

ALTER TABLE animals ADD COLUMN blocks LONGTEXT, ADD COLUMN views INT DEFAULT 0;
ALTER TABLE albums ADD COLUMN blocks LONGTEXT, ADD COLUMN views INT DEFAULT 0;
ALTER TABLE posts ADD COLUMN blocks LONGTEXT, ADD COLUMN views INT DEFAULT 0;
ALTER TABLE pages ADD COLUMN is_homepage TINYINT DEFAULT 0, ADD COLUMN views INT DEFAULT 0;
