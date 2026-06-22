<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Model obrazków powiązanych z historią:
 *  - 'upload'    : obrazek dodany przez użytkownika
 *  - 'generated' : wygenerowany obrazek z logo (do og:image / social share)
 */
final class StoryImage extends Model
{
    protected string $table = 'story_images';

    public const TYPE_UPLOAD    = 'upload';
    public const TYPE_GENERATED = 'generated';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forStory(int $storyId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `story_images` WHERE story_id = ? ORDER BY id ASC",
            [$storyId]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function generatedFor(int $storyId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `story_images` WHERE story_id = ? AND type = ? ORDER BY id DESC LIMIT 1",
            [$storyId, self::TYPE_GENERATED]
        );
    }

    public function add(int $storyId, string $path, string $type = self::TYPE_UPLOAD): int
    {
        return $this->insert([
            'story_id'   => $storyId,
            'path'       => $path,
            'type'       => $type,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
