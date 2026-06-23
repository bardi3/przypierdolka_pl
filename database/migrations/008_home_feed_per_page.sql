-- 008: konfigurowalna liczba historii na stronie głównej (infinite scroll)
USE `przypierdolka`;

INSERT INTO `settings` (`key`, `value`) VALUES
('home_feed_per_page', '5')
ON DUPLICATE KEY UPDATE `key` = `key`;
