<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Model historii.
 *
 * Statusy: 'published', 'pending', 'rejected'.
 * Sortowania: 'newest', 'top_week', 'top_month', 'top_rated'.
 */
final class Story extends Model
{
    protected string $table = 'stories';

    public const STATUS_PUBLISHED = 'published';
    public const STATUS_PENDING   = 'pending';
    public const STATUS_REJECTED  = 'rejected';

    public const RANK_WEEK  = 'top_week';
    public const RANK_MONTH = 'top_month';
    public const RANK_ALL   = 'top_all';

    private const SELECT_CARD = "
        s.id, s.title, s.slug, s.excerpt, s.status,
        s.category_id, s.user_id, s.author_name,
        s.rating_avg, s.rating_sum, s.ratings_count, s.views,
        s.generated_image_path,
        s.created_at, s.published_at,
        c.name AS category_name, c.slug AS category_slug,
        u.username AS author_username
    ";

    private const SELECT_FULL = "
        s.id, s.title, s.slug, s.excerpt, s.content, s.status,
        s.category_id, s.user_id, s.author_name,
        s.rating_avg, s.rating_sum, s.ratings_count, s.views,
        s.generated_image_path,
        s.created_at, s.published_at,
        c.name AS category_name, c.slug AS category_slug,
        u.username AS author_username
    ";

    private const FROM_JOINS = "
        FROM `stories` s
        LEFT JOIN `categories` c ON c.id = s.category_id
        LEFT JOIN `users` u ON u.id = s.user_id
    ";

    /**
     * Lista opublikowanych historii z sortowaniem i paginacją.
     *
     * @return array<int, array<string, mixed>>
     */
    public function published(string $sort = 'newest', int $limit = 12, int $offset = 0, ?int $categoryId = null): array
    {
        [$orderBy, $extraWhere, $extraParams] = $this->sortClause($sort);

        $where = "WHERE s.status = 'published'";
        $params = [];
        if ($categoryId !== null) {
            $where .= " AND s.category_id = ?";
            $params[] = $categoryId;
        }
        if ($extraWhere !== '') {
            $where .= " AND " . $extraWhere;
            $params = array_merge($params, $extraParams);
        }

        $params[] = $limit;
        $params[] = $offset;

        $sql = "SELECT " . self::SELECT_CARD . self::FROM_JOINS . " {$where} ORDER BY {$orderBy} LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, $params);
    }

    public function countPublished(?int $categoryId = null): int
    {
        $where = "WHERE status = 'published'";
        $params = [];
        if ($categoryId !== null) {
            $where .= " AND category_id = ?";
            $params[] = $categoryId;
        }
        return (int)$this->db->fetchColumn("SELECT COUNT(*) FROM `stories` {$where}", $params);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findBySlug(string $slug): ?array
    {
        $sql = "SELECT " . self::SELECT_FULL . self::FROM_JOINS . " WHERE s.slug = ? LIMIT 1";
        return $this->db->fetch($sql, [$slug]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findFull(int $id): ?array
    {
        $sql = "SELECT " . self::SELECT_FULL . self::FROM_JOINS . " WHERE s.id = ? LIMIT 1";
        return $this->db->fetch($sql, [$id]);
    }

    public function slugExists(string $slug): bool
    {
        return (int)$this->db->fetchColumn("SELECT COUNT(*) FROM `stories` WHERE slug = ?", [$slug]) > 0;
    }

    public function incrementViews(int $id): void
    {
        $this->db->execute("UPDATE `stories` SET views = views + 1 WHERE id = ?", [$id]);
    }

    /**
     * Lista do moderacji z opcjonalnym filtrem statusu.
     * @return array<int, array<string, mixed>>
     */
    public function forAdmin(?string $status, int $limit, int $offset): array
    {
        $where = '';
        $params = [];
        if ($status !== null && $status !== '') {
            $where = "WHERE s.status = ?";
            $params[] = $status;
        }
        $params[] = $limit;
        $params[] = $offset;

        $sql = "SELECT " . self::SELECT_CARD . self::FROM_JOINS . " {$where} ORDER BY s.created_at DESC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, $params);
    }

    public function countByStatus(?string $status = null): int
    {
        if ($status === null) {
            return (int)$this->db->fetchColumn("SELECT COUNT(*) FROM `stories`");
        }
        return (int)$this->db->fetchColumn("SELECT COUNT(*) FROM `stories` WHERE status = ?", [$status]);
    }

    /**
     * Aktualizuje zagregowane statystyki ocen.
     */
    public function updateRatingStats(int $id, int $sum, int $count): void
    {
        $avg = $count > 0 ? round($sum / $count, 2) : 0.0;
        $this->db->execute(
            "UPDATE `stories` SET rating_sum = ?, ratings_count = ?, rating_avg = ? WHERE id = ?",
            [$sum, $count, $avg, $id]
        );
    }

    public function setGeneratedImagePath(int $id, string $path): void
    {
        $this->update($id, ['generated_image_path' => $path]);
    }

    /**
     * Ranking opublikowanych historii z minimalną liczbą ocen.
     *
     * @return array<int, array<string, mixed>>
     */
    public function ranking(string $period, int $limit, int $offset, ?int $categoryId, int $minRatings): array
    {
        [$extraWhere, $extraParams] = $this->rankingPeriodClause($period);

        $where = "WHERE s.status = 'published' AND s.ratings_count >= ?";
        $params = [$minRatings];

        if ($categoryId !== null) {
            $where .= " AND s.category_id = ?";
            $params[] = $categoryId;
        }
        if ($extraWhere !== '') {
            $where .= " AND " . $extraWhere;
            $params = array_merge($params, $extraParams);
        }

        $params[] = $limit;
        $params[] = $offset;

        $sql = "SELECT " . self::SELECT_CARD . self::FROM_JOINS
            . " {$where} ORDER BY s.rating_avg DESC, s.ratings_count DESC, s.id DESC LIMIT ? OFFSET ?";

        return $this->db->fetchAll($sql, $params);
    }

    public function countRanking(string $period, ?int $categoryId, int $minRatings): int
    {
        [$extraWhere, $extraParams] = $this->rankingPeriodClause($period);

        $where = "WHERE status = 'published' AND ratings_count >= ?";
        $params = [$minRatings];

        if ($categoryId !== null) {
            $where .= " AND category_id = ?";
            $params[] = $categoryId;
        }
        if ($extraWhere !== '') {
            $where .= " AND " . str_replace('s.', '', $extraWhere);
            $params = array_merge($params, $extraParams);
        }

        return (int)$this->db->fetchColumn("SELECT COUNT(*) FROM `stories` {$where}", $params);
    }

    /**
     * @return array{0:string, 1:array<int,mixed>}
     */
    private function rankingPeriodClause(string $period): array
    {
        return match ($period) {
            self::RANK_MONTH => ['s.published_at >= (NOW() - INTERVAL 30 DAY)', []],
            self::RANK_ALL   => ['', []],
            default          => ['s.published_at >= (NOW() - INTERVAL 7 DAY)', []],
        };
    }

    /**
     * @return array{0:string, 1:string, 2:array<int,mixed>} [orderBy, extraWhere, extraParams]
     */
    private function sortClause(string $sort): array
    {
        return match ($sort) {
            'top_week' => [
                's.rating_avg DESC, s.ratings_count DESC, s.id DESC',
                's.published_at >= (NOW() - INTERVAL 7 DAY)',
                [],
            ],
            'top_month' => [
                's.rating_avg DESC, s.ratings_count DESC, s.id DESC',
                's.published_at >= (NOW() - INTERVAL 30 DAY)',
                [],
            ],
            'top_rated' => [
                's.rating_avg DESC, s.ratings_count DESC, s.id DESC',
                's.ratings_count >= 1',
                [],
            ],
            'top_all' => [
                's.rating_avg DESC, s.ratings_count DESC, s.id DESC',
                's.ratings_count >= 1',
                [],
            ],
            default => [
                's.published_at DESC, s.id DESC',
                '',
                [],
            ],
        };
    }

    /**
     * Historie dodane przez użytkownika (wszystkie statusy).
     *
     * @return array<int, array<string, mixed>>
     */
    public function forUser(int $userId, int $limit, int $offset): array
    {
        return $this->db->fetchAll(
            "SELECT " . self::SELECT_CARD . self::FROM_JOINS
            . " WHERE s.user_id = ? ORDER BY s.created_at DESC LIMIT ? OFFSET ?",
            [$userId, $limit, $offset]
        );
    }

    public function countForUser(int $userId): int
    {
        return (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM `stories` WHERE user_id = ?",
            [$userId]
        );
    }

    /**
     * Cofa (usuwa) historię oczekującą na moderację — tylko właściciel.
     */
    public function withdrawPendingByUser(int $storyId, int $userId): bool
    {
        $story = $this->find($storyId);
        if ($story === null) {
            return false;
        }
        if ((int)($story['user_id'] ?? 0) !== $userId) {
            return false;
        }
        if ($story['status'] !== self::STATUS_PENDING) {
            return false;
        }

        $this->delete($storyId);
        return true;
    }

    /**
     * Poprzednia / następna opublikowana historia (kolejność jak na liście — najnowsze pierwsze).
     *
     * @return array{slug:string, title:string}|null
     */
    public function adjacentPublished(int $id, ?string $publishedAt, string $direction): ?array
    {
        if (!in_array($direction, ['prev', 'next'], true)) {
            return null;
        }

        $ts = $publishedAt ?: date('Y-m-d H:i:s');

        if ($direction === 'next') {
            $sql = "SELECT s.slug, s.title FROM `stories` s
                WHERE s.status = 'published'
                AND (
                    COALESCE(s.published_at, s.created_at) < ?
                    OR (COALESCE(s.published_at, s.created_at) = ? AND s.id < ?)
                )
                ORDER BY COALESCE(s.published_at, s.created_at) DESC, s.id DESC
                LIMIT 1";

            return $this->db->fetch($sql, [$ts, $ts, $id]) ?: null;
        }

        $sql = "SELECT s.slug, s.title FROM `stories` s
            WHERE s.status = 'published'
            AND (
                COALESCE(s.published_at, s.created_at) > ?
                OR (COALESCE(s.published_at, s.created_at) = ? AND s.id > ?)
            )
            ORDER BY COALESCE(s.published_at, s.created_at) ASC, s.id ASC
            LIMIT 1";

        return $this->db->fetch($sql, [$ts, $ts, $id]) ?: null;
    }

    public function randomPublishedSlug(?int $excludeId = null): ?string
    {
        $where = "WHERE status = 'published'";
        $params = [];
        if ($excludeId !== null && $excludeId > 0) {
            $where .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $slug = $this->db->fetchColumn(
            "SELECT slug FROM `stories` {$where} ORDER BY RAND() LIMIT 1",
            $params
        );

        return $slug !== false && $slug !== null ? (string)$slug : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function publishedByUser(int $userId, int $limit, int $offset): array
    {
        return $this->db->fetchAll(
            "SELECT " . self::SELECT_CARD . self::FROM_JOINS . "
             WHERE s.status = 'published' AND s.user_id = ?
             ORDER BY s.published_at DESC, s.id DESC
             LIMIT ? OFFSET ?",
            [$userId, $limit, $offset]
        );
    }

    public function countPublishedByUser(int $userId): int
    {
        return (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM `stories` WHERE status = 'published' AND user_id = ?",
            [$userId]
        );
    }

    /**
     * Opublikowane historie od zaakceptowanych znajomych (bez własnych).
     *
     * @return array<int, array<string, mixed>>
     */
    public function publishedByFriends(int $userId, int $limit, int $offset): array
    {
        return $this->db->fetchAll(
            "SELECT " . self::SELECT_CARD . self::FROM_JOINS . "
             INNER JOIN `friendships` f ON f.status = 'accepted'
               AND (
                   (f.requester_id = ? AND f.addressee_id = s.user_id)
                   OR (f.addressee_id = ? AND f.requester_id = s.user_id)
               )
             WHERE s.status = 'published'
             ORDER BY s.published_at DESC, s.id DESC
             LIMIT ? OFFSET ?",
            [$userId, $userId, $limit, $offset]
        );
    }

    public function countPublishedByFriends(int $userId): int
    {
        return (int)$this->db->fetchColumn(
            "SELECT COUNT(DISTINCT s.id)
             FROM `stories` s
             INNER JOIN `friendships` f ON f.status = 'accepted'
               AND (
                   (f.requester_id = ? AND f.addressee_id = s.user_id)
                   OR (f.addressee_id = ? AND f.requester_id = s.user_id)
               )
             WHERE s.status = 'published'",
            [$userId, $userId]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function topRatedByUser(int $userId, int $limit, int $minRatings = 2): array
    {
        return $this->db->fetchAll(
            "SELECT " . self::SELECT_CARD . self::FROM_JOINS . "
             WHERE s.status = 'published' AND s.user_id = ? AND s.ratings_count >= ?
             ORDER BY s.rating_avg DESC, s.ratings_count DESC, s.id DESC
             LIMIT ?",
            [$userId, $minRatings, $limit]
        );
    }

    /**
     * @return array{stories_count:int, views_total:int, ratings_total:int, rating_avg:float}
     */
    public function statsForUser(int $userId): array
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS stories_count,
                    COALESCE(SUM(views), 0) AS views_total,
                    COALESCE(SUM(ratings_count), 0) AS ratings_total,
                    COALESCE(AVG(CASE WHEN ratings_count >= 1 THEN rating_avg END), 0) AS rating_avg
             FROM `stories`
             WHERE status = 'published' AND user_id = ?",
            [$userId]
        );

        return [
            'stories_count'  => (int)($row['stories_count'] ?? 0),
            'views_total'    => (int)($row['views_total'] ?? 0),
            'ratings_total'  => (int)($row['ratings_total'] ?? 0),
            'rating_avg'     => round((float)($row['rating_avg'] ?? 0), 2),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchPublished(string $query, int $limit = 8): array
    {
        $like = '%' . $this->escapeLike($query) . '%';

        return $this->db->fetchAll(
            "SELECT s.id, s.title, s.slug, s.excerpt,
                    c.name AS category_name, c.slug AS category_slug
             FROM `stories` s
             LEFT JOIN `categories` c ON c.id = s.category_id
             WHERE s.status = 'published'
               AND (s.title LIKE ? OR s.excerpt LIKE ? OR s.content LIKE ?)
             ORDER BY s.published_at DESC
             LIMIT ?",
            [$like, $like, $like, $limit]
        );
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
