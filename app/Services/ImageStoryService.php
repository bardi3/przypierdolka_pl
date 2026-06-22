<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Models\StoryImage;

/**
 * Orkiestracja obrazków historii — preferuje stories.generated_image_path.
 */
final class ImageStoryService
{
    private StoryImage $images;
    private ShareImageService $shareImage;
    private string $publicPath;

    public function __construct(StoryImage $images, ShareImageService $shareImage)
    {
        $this->images = $images;
        $this->shareImage = $shareImage;
        $this->publicPath = (string)Config::get('app.paths.public');
    }

    public function attachUpload(int $storyId, string $publicPath): int
    {
        return $this->images->add($storyId, $publicPath, StoryImage::TYPE_UPLOAD);
    }

    /**
     * @param array<string, mixed> $story
     */
    public function shareImagePath(array $story): string
    {
        $path = $this->shareImage->getOrGenerate($story);

        if (str_starts_with($path, 'assets/uploads/generated/')) {
            $existing = $this->images->generatedFor((int)$story['id']);
            if ($existing === null) {
                $this->images->add((int)$story['id'], $path, StoryImage::TYPE_GENERATED);
            }
        }

        return $path;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forStory(int $storyId): array
    {
        return $this->images->forStory($storyId);
    }
}
