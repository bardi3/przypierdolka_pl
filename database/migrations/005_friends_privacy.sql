-- Znajomi, bio i ustawienia prywatności profilu
-- mysql -u root przypierdolka < database/migrations/005_friends_privacy.sql

USE `przypierdolka`;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'bio');
SET @sql := IF(@col = 0,
    'ALTER TABLE `users` ADD COLUMN `bio` VARCHAR(500) NULL DEFAULT NULL AFTER `status`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'profile_visibility');
SET @sql := IF(@col = 0,
    'ALTER TABLE `users` ADD COLUMN `profile_visibility` ENUM(''public'',''friends'',''private'') NOT NULL DEFAULT ''public'' AFTER `bio`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'stories_visibility');
SET @sql := IF(@col = 0,
    'ALTER TABLE `users` ADD COLUMN `stories_visibility` ENUM(''public'',''friends'',''private'') NOT NULL DEFAULT ''public'' AFTER `profile_visibility`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'friends_list_visibility');
SET @sql := IF(@col = 0,
    'ALTER TABLE `users` ADD COLUMN `friends_list_visibility` ENUM(''public'',''friends'',''private'') NOT NULL DEFAULT ''friends'' AFTER `stories_visibility`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `friendships` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `requester_id`  INT UNSIGNED NOT NULL,
    `addressee_id`  INT UNSIGNED NOT NULL,
    `status`        ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_friendship_pair` (`requester_id`, `addressee_id`),
    KEY `idx_friendships_addressee` (`addressee_id`, `status`),
    KEY `idx_friendships_requester` (`requester_id`, `status`),
    CONSTRAINT `fk_friendships_requester` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_friendships_addressee` FOREIGN KEY (`addressee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
