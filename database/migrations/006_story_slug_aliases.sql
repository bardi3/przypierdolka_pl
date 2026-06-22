-- Aliasy slugów historii (stary URL → aktualna historia po zmianie tytułu)
-- mysql -u root -h 127.0.0.1 przypierdolka < database/migrations/006_story_slug_aliases.sql

USE `przypierdolka`;

CREATE TABLE IF NOT EXISTS `story_slug_aliases` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `story_id`   INT UNSIGNED NOT NULL,
    `slug`       VARCHAR(200) NOT NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_story_slug_aliases_slug` (`slug`),
    KEY `idx_story_slug_aliases_story` (`story_id`),
    CONSTRAINT `fk_story_slug_aliases_story` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
