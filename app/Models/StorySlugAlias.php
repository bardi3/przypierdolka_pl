<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Stare slugi historii — przekierowanie 301 na aktualny URL.
 */
final class StorySlugAlias extends Model
{
    protected string $table = 'story_slug_aliases';

    public function findStoryIdBySlug(string $slug): ?int
    {
        $id = $this->db->fetchColumn(
            "SELECT story_id FROM `story_slug_aliases` WHERE slug = ? LIMIT 1",
            [$slug]
        );

        return $id !== false && $id !== null ? (int)$id : null;
    }

    public function exists(string $slug): bool
    {
        return (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM `story_slug_aliases` WHERE slug = ?",
            [$slug]
        ) > 0;
    }

    public function isTakenByOtherStory(string $slug, int $exceptStoryId): bool
    {
        return (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM `story_slug_aliases` WHERE slug = ? AND story_id <> ?",
            [$slug, $exceptStoryId]
        ) > 0;
    }

    public function record(int $storyId, string $slug): void
    {
        if ($slug === '') {
            return;
        }

        if ($this->exists($slug)) {
            return;
        }

        $this->insert([
            'story_id'   => $storyId,
            'slug'       => $slug,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** Usuń alias, jeśli nowy slug pokrywa się ze starym aliasem tej historii. */
    public function deleteForStory(int $storyId, string $slug): void
    {
        $this->db->execute(
            "DELETE FROM `story_slug_aliases` WHERE story_id = ? AND slug = ?",
            [$storyId, $slug]
        );
    }
}
