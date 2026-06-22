-- Przykładowe oceny dla seed (ranking wymaga min. 2 ocen — app.rankings.min_ratings)
USE `przypierdolka`;

INSERT IGNORE INTO `ratings` (`story_id`, `user_id`, `ip_hash`, `value`) VALUES
(1, NULL, 'seed0001a', 5),
(1, NULL, 'seed0001b', 4),
(2, NULL, 'seed0002a', 5),
(2, NULL, 'seed0002b', 5),
(3, NULL, 'seed0003a', 4),
(3, NULL, 'seed0003b', 3);

UPDATE `stories` SET `rating_sum` = 9,  `ratings_count` = 2, `rating_avg` = 4.50 WHERE `id` = 1 AND `ratings_count` = 0;
UPDATE `stories` SET `rating_sum` = 10, `ratings_count` = 2, `rating_avg` = 5.00 WHERE `id` = 2 AND `ratings_count` = 0;
UPDATE `stories` SET `rating_sum` = 7,  `ratings_count` = 2, `rating_avg` = 3.50 WHERE `id` = 3 AND `ratings_count` = 0;
