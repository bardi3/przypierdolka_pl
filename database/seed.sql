-- przypierdolka.pl - dane startowe (seed)
-- Uruchom PO schema.sql:
--   mysql -u root przypierdolka < database/schema.sql
--   mysql -u root przypierdolka < database/seed.sql

USE `przypierdolka`;

-- ---------------------------------------------------------------------------
-- Konta testowe
-- Hasła:
--   admin     / admin1234
--   moderator / moderator1234
-- (hashe bcrypt, cost=12 - ZMIEŃ hasła na produkcji!)
-- ---------------------------------------------------------------------------
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `status`) VALUES
('admin',     'admin@przypierdolka.pl',     '$2y$12$XBGR9BKtwBhjGHoWJJnOOurHZUqpbHsbqS38MMDhO9LhSL/de8kaa', 'admin',     'active'),
('moderator', 'moderator@przypierdolka.pl', '$2y$12$jD7pSnLMxzy2uPQfkHFDleEUKeY.y5/sb/VVnJM/G9AUkPiomxnZ.', 'moderator', 'active');

-- ---------------------------------------------------------------------------
-- Kategorie startowe
-- ---------------------------------------------------------------------------
INSERT INTO `categories` (`name`, `slug`, `description`, `sort_order`) VALUES
('Z życia wzięte', 'z-zycia-wziete', 'Codzienne absurdy i sytuacje, które naprawdę się wydarzyły.', 1),
('Praca',          'praca',          'Historie z biura, warsztatu i open space.', 2),
('Szkoła',         'szkola',         'Wpadki ze szkolnej ławki i nie tylko.', 3),
('Rodzina',        'rodzina',        'Domowe absurdy i rodzinne klasyki.', 4),
('Internet',       'internet',       'Co się odpierdala w sieci.', 5),
('Wpadki',         'wpadki',         'Sytuacje, których wolelibyśmy nie przeżyć.', 6);

-- ---------------------------------------------------------------------------
-- Przykładowe historie (opublikowane)
-- ---------------------------------------------------------------------------
INSERT INTO `stories` (`title`, `slug`, `excerpt`, `content`, `category_id`, `user_id`, `author_name`, `status`, `published_at`) VALUES
('Jak zepsułem drukarkę w pierwszym dniu pracy',
 'jak-zepsulem-drukarke-w-pierwszym-dniu-pracy',
 'Pierwszy dzień, wielkie nadzieje i jedna drukarka, która już nigdy nie wydrukowała ani jednej strony.',
 'Pierwszy dzień w nowej pracy. Chciałem zrobić dobre wrażenie, więc zgłosiłem się na ochotnika do wydrukowania prezentacji dla zarządu.\n\nWcisnąłem "drukuj". Nic. Wcisnąłem jeszcze 47 razy. Kiedy w końcu drukarka ożyła, wypluła 376 stron zanim ktokolwiek zdążył zareagować. Tonera starczyło na 12.',
 2, 1, NULL, 'published', NOW() - INTERVAL 2 DAY),

('Nauczycielka zapytała o stolicę, a ja...',
 'nauczycielka-zapytala-o-stolice-a-ja',
 'Klasyczna wpadka przy tablicy, którą pamięta cała klasa do dziś.',
 'Pani od geografii pyta: "Jaka jest stolica Australii?". Pewny siebie wstaję i mówię: "Kangur".\n\nDo dziś nie wiem, skąd mi się to wzięło. Klasa płakała ze śmiechu. Ja też, ale ze wstydu.',
 3, NULL, 'Anonimowy uczeń', 'published', NOW() - INTERVAL 1 DAY),

('Mój pies zjadł pilota i teraz oglądamy tylko TVP',
 'moj-pies-zjadl-pilota-i-teraz-ogladamy-tylko-tvp',
 'Pilot przepadł, kanał się nie zmienia, a rodzina powoli traci zmysły.',
 'Reksio postanowił, że pilot to przekąska. Od tygodnia telewizor jest zablokowany na jednym kanale.\n\nNajgorsze jest to, że nikt nie wie, gdzie jest stary pilot. Rozważamy zakup nowego telewizora. Tańsze niż terapia.',
 4, NULL, 'Pan Heniek', 'published', NOW() - INTERVAL 6 HOUR);

-- ---------------------------------------------------------------------------
-- Przykładowe oceny (ranking wymaga min. 2 ocen na historię — app.rankings.min_ratings)
-- ---------------------------------------------------------------------------
INSERT INTO `ratings` (`story_id`, `user_id`, `ip_hash`, `value`) VALUES
(1, NULL, 'seed0001a', 5),
(1, NULL, 'seed0001b', 4),
(2, NULL, 'seed0002a', 5),
(2, NULL, 'seed0002b', 5),
(3, NULL, 'seed0003a', 4),
(3, NULL, 'seed0003b', 3);

UPDATE `stories` SET
    `rating_sum` = 9,  `ratings_count` = 2, `rating_avg` = 4.50 WHERE `id` = 1;
UPDATE `stories` SET
    `rating_sum` = 10, `ratings_count` = 2, `rating_avg` = 5.00 WHERE `id` = 2;
UPDATE `stories` SET
    `rating_sum` = 7,  `ratings_count` = 2, `rating_avg` = 3.50 WHERE `id` = 3;

-- ---------------------------------------------------------------------------
-- Ustawienia startowe
-- ---------------------------------------------------------------------------
INSERT INTO `settings` (`key`, `value`) VALUES
('site_title',                 'przypierdolka.pl — krótkie historie, które przypierdalają'),
('site_description',           'Najlepsze humorystyczne historie z życia, pracy i szkoły. Dodawaj, oceniaj i udostępniaj!'),
('meta_keywords',              'historie, humor, śmieszne, memy, anegdoty'),
('stories_require_moderation', '1'),
('social_facebook',            ''),
('social_instagram',           '');
