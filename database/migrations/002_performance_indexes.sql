-- Indeksy pod listy i rankingi (002)
USE `przypierdolka`;

ALTER TABLE `stories`
    ADD KEY `idx_stories_status_cat_pub` (`status`, `category_id`, `published_at`),
    ADD KEY `idx_stories_status_pub_rating` (`status`, `published_at`, `rating_avg`, `ratings_count`);
