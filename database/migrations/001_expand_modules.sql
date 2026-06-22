-- Migracja modułów: audit_logs, generated_image_path, sort_order
-- Uruchom na istniejącej bazie: mysql -u root przypierdolka < database/migrations/001_expand_modules.sql

USE `przypierdolka`;

-- Kolumny w stories (ignoruj błąd jeśli już istnieją)
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stories' AND COLUMN_NAME = 'generated_image_path');
SET @sql := IF(@col = 0,
    'ALTER TABLE `stories` ADD COLUMN `generated_image_path` VARCHAR(255) NULL DEFAULT NULL AFTER `views`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Kolumny w categories
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'sort_order');
SET @sql := IF(@col = 0,
    'ALTER TABLE `categories` ADD COLUMN `sort_order` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `description`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Tabela audit_logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED NULL DEFAULT NULL,
    `action`      VARCHAR(50)  NOT NULL,
    `entity_type` VARCHAR(50)  NOT NULL,
    `entity_id`   INT UNSIGNED NOT NULL,
    `details`     TEXT         NULL,
    `ip_address`  VARCHAR(45)  NULL DEFAULT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_entity` (`entity_type`, `entity_id`),
    KEY `idx_audit_user` (`user_id`),
    KEY `idx_audit_action` (`action`),
    KEY `idx_audit_created` (`created_at`),
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
