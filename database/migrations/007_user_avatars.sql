-- Awatary użytkowników (ścieżka względna do public/)
ALTER TABLE `users`
    ADD COLUMN `avatar_path` VARCHAR(255) NULL DEFAULT NULL AFTER `bio`;
