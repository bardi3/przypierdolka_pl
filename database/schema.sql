-- przypierdolka.pl - schemat bazy danych
-- MySQL 8 / MariaDB 10.4+
-- Kodowanie: utf8mb4

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `przypierdolka`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `przypierdolka`;

-- ---------------------------------------------------------------------------
-- Użytkownicy
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username`      VARCHAR(30)  NOT NULL,
    `email`         VARCHAR(120) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role`          ENUM('admin','moderator','user') NOT NULL DEFAULT 'user',
    `status`        ENUM('active','blocked')         NOT NULL DEFAULT 'active',
    `bio`           VARCHAR(500) NULL DEFAULT NULL,
    `profile_visibility`       ENUM('public','friends','private') NOT NULL DEFAULT 'public',
    `stories_visibility`       ENUM('public','friends','private') NOT NULL DEFAULT 'public',
    `friends_list_visibility`  ENUM('public','friends','private') NOT NULL DEFAULT 'friends',
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_login_at` DATETIME     NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_users_username` (`username`),
    UNIQUE KEY `uniq_users_email` (`email`),
    KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Znajomi (zaproszenia)
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `friendships`;
CREATE TABLE `friendships` (
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

-- ---------------------------------------------------------------------------
-- Kategorie
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(60)  NOT NULL,
    `slug`        VARCHAR(80)  NOT NULL,
    `description` VARCHAR(255) NULL DEFAULT NULL,
    `sort_order`  INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_categories_slug` (`slug`),
    KEY `idx_categories_sort` (`sort_order`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Historie
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `stories`;
CREATE TABLE `stories` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`         VARCHAR(200) NOT NULL,
    `slug`          VARCHAR(200) NOT NULL,
    `excerpt`       VARCHAR(255) NOT NULL DEFAULT '',
    `content`       TEXT         NOT NULL,
    `category_id`   INT UNSIGNED NULL DEFAULT NULL,
    `user_id`       INT UNSIGNED NULL DEFAULT NULL,
    `author_name`   VARCHAR(60)  NULL DEFAULT NULL,
    `status`        ENUM('published','pending','rejected') NOT NULL DEFAULT 'pending',
    `rating_avg`    DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    `rating_sum`    INT UNSIGNED NOT NULL DEFAULT 0,
    `ratings_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `views`                INT UNSIGNED NOT NULL DEFAULT 0,
    `generated_image_path` VARCHAR(255) NULL DEFAULT NULL,
    `created_at`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `published_at`         DATETIME     NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_stories_slug` (`slug`),
    KEY `idx_stories_status_published` (`status`, `published_at`),
    KEY `idx_stories_category` (`category_id`),
    KEY `idx_stories_user` (`user_id`),
    KEY `idx_stories_rating` (`rating_avg`, `ratings_count`),
    KEY `idx_stories_status_rating` (`status`, `rating_avg`),
    KEY `idx_stories_status_cat_pub` (`status`, `category_id`, `published_at`),
    KEY `idx_stories_status_pub_rating` (`status`, `published_at`, `rating_avg`, `ratings_count`),
    CONSTRAINT `fk_stories_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_stories_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Aliasy slugów historii (stary URL po zmianie tytułu)
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `story_slug_aliases`;
CREATE TABLE `story_slug_aliases` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `story_id`   INT UNSIGNED NOT NULL,
    `slug`       VARCHAR(200) NOT NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_story_slug_aliases_slug` (`slug`),
    KEY `idx_story_slug_aliases_story` (`story_id`),
    CONSTRAINT `fk_story_slug_aliases_story` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Oceny (1-5 gwiazdek)
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `ratings`;
CREATE TABLE `ratings` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `story_id`   INT UNSIGNED NOT NULL,
    `user_id`    INT UNSIGNED NULL DEFAULT NULL,
    `ip_hash`    CHAR(64)     NULL DEFAULT NULL,
    `value`      TINYINT UNSIGNED NOT NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ratings_story` (`story_id`),
    -- jedna ocena na (historia, zalogowany użytkownik)
    UNIQUE KEY `uniq_ratings_story_user` (`story_id`, `user_id`),
    -- jedna ocena na (historia, IP gościa)
    UNIQUE KEY `uniq_ratings_story_ip` (`story_id`, `ip_hash`),
    CONSTRAINT `fk_ratings_story` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ratings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `chk_ratings_value` CHECK (`value` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Obrazki historii (upload + wygenerowane z logo)
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `story_images`;
CREATE TABLE `story_images` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `story_id`   INT UNSIGNED NOT NULL,
    `path`       VARCHAR(255) NOT NULL,
    `type`       ENUM('upload','generated') NOT NULL DEFAULT 'upload',
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_story_images_story` (`story_id`, `type`),
    CONSTRAINT `fk_story_images_story` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Log audytu (moderacja, edycje admina)
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
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

-- ---------------------------------------------------------------------------
-- Ustawienia serwisu (klucz => wartość)
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
    `key`   VARCHAR(64)  NOT NULL,
    `value` TEXT         NULL,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- llms.txt — wpisy dla modeli językowych
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS `llms_entries`;
CREATE TABLE `llms_entries` (
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

SET FOREIGN_KEY_CHECKS = 1;
