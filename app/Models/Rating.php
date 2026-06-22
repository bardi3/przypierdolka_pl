<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Model ocen historii (1-5 gwiazdek).
 * Unikalność: (story_id, user_id) dla zalogowanych lub (story_id, ip_hash) dla gości.
 */
final class Rating extends Model
{
    protected string $table = 'ratings';

    /**
     * Czy dany użytkownik (lub IP gościa) już ocenił historię.
     */
    public function exists(int $storyId, ?int $userId, ?string $ipHash): bool
    {
        if ($userId !== null) {
            return (int)$this->db->fetchColumn(
                "SELECT COUNT(*) FROM `ratings` WHERE story_id = ? AND user_id = ?",
                [$storyId, $userId]
            ) > 0;
        }
        if ($ipHash !== null) {
            return (int)$this->db->fetchColumn(
                "SELECT COUNT(*) FROM `ratings` WHERE story_id = ? AND ip_hash = ? AND user_id IS NULL",
                [$storyId, $ipHash]
            ) > 0;
        }
        return false;
    }

    /**
     * Zwraca ocenę użytkownika/gościa dla historii (null = brak oceny).
     */
    public function getUserRating(int $storyId, ?int $userId, ?string $ipHash): ?int
    {
        if ($userId !== null) {
            $val = $this->db->fetchColumn(
                "SELECT value FROM `ratings` WHERE story_id = ? AND user_id = ? LIMIT 1",
                [$storyId, $userId]
            );
            return $val !== false && $val !== null ? (int)$val : null;
        }
        if ($ipHash !== null) {
            $val = $this->db->fetchColumn(
                "SELECT value FROM `ratings` WHERE story_id = ? AND ip_hash = ? AND user_id IS NULL LIMIT 1",
                [$storyId, $ipHash]
            );
            return $val !== false && $val !== null ? (int)$val : null;
        }
        return null;
    }

    /**
     * @param array<int, int> $storyIds
     * @return array<int, int> mapa story_id => value
     */
    public function mapUserRatings(array $storyIds, ?int $userId, ?string $ipHash): array
    {
        $storyIds = array_values(array_unique(array_filter(array_map('intval', $storyIds))));
        if ($storyIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($storyIds), '?'));

        if ($userId !== null) {
            $rows = $this->db->fetchAll(
                "SELECT story_id, value FROM `ratings` WHERE story_id IN ({$placeholders}) AND user_id = ?",
                [...$storyIds, $userId]
            );
        } elseif ($ipHash !== null) {
            $rows = $this->db->fetchAll(
                "SELECT story_id, value FROM `ratings` WHERE story_id IN ({$placeholders}) AND ip_hash = ? AND user_id IS NULL",
                [...$storyIds, $ipHash]
            );
        } else {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['story_id']] = (int)$row['value'];
        }
        return $map;
    }

    public function add(int $storyId, int $value, ?int $userId, ?string $ipHash): int
    {
        return $this->insert([
            'story_id'   => $storyId,
            'user_id'    => $userId,
            'ip_hash'    => $ipHash,
            'value'      => $value,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Zwraca zagregowane statystyki ocen dla historii.
     * @return array{sum:int, count:int}
     */
    public function aggregate(int $storyId): array
    {
        $row = $this->db->fetch(
            "SELECT COALESCE(SUM(value), 0) AS sum, COUNT(*) AS cnt FROM `ratings` WHERE story_id = ?",
            [$storyId]
        );
        return [
            'sum'   => (int)($row['sum'] ?? 0),
            'count' => (int)($row['cnt'] ?? 0),
        ];
    }
}
