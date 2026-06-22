-- llms.txt — wpisy i ustawienia meta

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `llms_entries` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `entry_key`    VARCHAR(100) NULL DEFAULT NULL,
    `section`      VARCHAR(80)  NOT NULL,
    `title`        VARCHAR(200) NOT NULL,
    `url`          VARCHAR(500) NOT NULL,
    `description`  VARCHAR(500) NULL DEFAULT NULL,
    `sort_order`   INT UNSIGNED NOT NULL DEFAULT 0,
    `is_optional`  TINYINT(1)   NOT NULL DEFAULT 0,
    `is_active`    TINYINT(1)   NOT NULL DEFAULT 1,
    `is_system`    TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_llms_entry_key` (`entry_key`),
    KEY `idx_llms_section_sort` (`section`, `sort_order`, `id`),
    KEY `idx_llms_active` (`is_active`, `is_optional`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`key`, `value`) VALUES
    ('llms_summary', 'Serwis z krótkimi, humorystycznymi historiami — czytaj, oceniaj gwiazdkami i dodawaj własne opowieści.'),
    ('llms_body', 'Ten plik opisuje najważniejsze strony przypierdolka.pl dla modeli językowych i agentów AI. Linki prowadzą do treści po polsku. Publicznie dostępne są wyłącznie opublikowane historie.'),
    ('llms_stories_limit', '200')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
