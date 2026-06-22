<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Model kategorii historii.
 */
final class Category extends Model
{
    protected string $table = 'categories';

    /**
     * @return array<string, mixed>|null
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->findBy(['slug' => $slug]);
    }

    /**
     * Wszystkie kategorie z liczbą opublikowanych historii.
     * @return array<int, array<string, mixed>>
     */
    public function allWithCounts(): array
    {
        return $this->db->fetchAll(
            "SELECT c.*, COUNT(s.id) AS stories_count
             FROM `categories` c
             LEFT JOIN `stories` s ON s.category_id = c.id AND s.status = 'published'
             GROUP BY c.id
             ORDER BY c.sort_order ASC, c.name ASC"
        );
    }

    /**
     * @param array<int, int> $orderMap id => sort_order
     */
    public function updateSortOrders(array $orderMap): void
    {
        foreach ($orderMap as $id => $sortOrder) {
            $this->update((int)$id, ['sort_order' => (int)$sortOrder]);
        }
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        if ($exceptId !== null) {
            return (int)$this->db->fetchColumn(
                "SELECT COUNT(*) FROM `categories` WHERE slug = ? AND id <> ?",
                [$slug, $exceptId]
            ) > 0;
        }
        return (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM `categories` WHERE slug = ?",
            [$slug]
        ) > 0;
    }
}
