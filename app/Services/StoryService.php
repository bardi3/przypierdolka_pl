<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Cache;
use App\Core\Slugger;
use App\Models\Setting;
use App\Models\Story;
use App\Models\StorySlugAlias;

/**
 * Logika domenowa historii: tworzenie, listowanie z cache, generowanie slug i excerpt.
 */
final class StoryService
{
    private Story $stories;
    private StorySlugAlias $slugAliases;
    private Cache $cache;
    private ShareImageService $shareImage;
    private Setting $settings;
    private int $perPage;
    private int $listTtl;

    public function __construct(
        Story $stories,
        Cache $cache,
        int $perPage = 12,
        ?ShareImageService $shareImage = null,
        ?Setting $settings = null
    ) {
        $this->stories = $stories;
        $this->slugAliases = new StorySlugAlias($stories->db());
        $this->cache = $cache;
        $this->perPage = $perPage;
        $this->shareImage = $shareImage ?? new ShareImageService($stories);
        $this->settings = $settings ?? new Setting($stories->db());
        $this->listTtl = (int)\App\Core\Config::get('app.cache.ttl', 300);
    }

    /**
     * @return array{items:array<int,array<string,mixed>>, total:int, page:int, pages:int, sort:string}
     */
    public function listing(string $sort = 'newest', int $page = 1, ?int $categoryId = null): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $this->perPage;
        $sort = $this->normalizeSort($sort);

        $cacheKey = sprintf('stories:list:%s:%s:%d', $sort, $categoryId ?? 'all', $page);

        return $this->cache->remember($cacheKey, function () use ($sort, $offset, $categoryId, $page): array {
            $items = $this->stories->published($sort, $this->perPage, $offset, $categoryId);
            $total = $this->stories->countPublished($categoryId);
            return [
                'items' => $items,
                'total' => $total,
                'page'  => $page,
                'pages' => (int)max(1, ceil($total / $this->perPage)),
                'sort'  => $sort,
            ];
        }, $this->listTtl);
    }

    /**
     * Tablica znajomych — spersonalizowany feed bez cache globalnego.
     *
     * @return array{items:array, total:int, page:int, pages:int}
     */
    public function friendsListing(int $userId, int $page = 1): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $this->perPage;
        $items = $this->stories->publishedByFriends($userId, $this->perPage, $offset);
        $total = $this->stories->countPublishedByFriends($userId);

        return [
            'items' => $items,
            'total' => $total,
            'page'  => $page,
            'pages' => (int)max(1, ceil($total / $this->perPage)),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getBySlug(string $slug): ?array
    {
        $story = $this->stories->findBySlug($slug);
        if ($story !== null) {
            return $story;
        }

        $storyId = $this->slugAliases->findStoryIdBySlug($slug);
        if ($storyId === null) {
            return null;
        }

        $canonicalSlug = $this->stories->db()->fetchColumn(
            "SELECT slug FROM `stories` WHERE id = ? LIMIT 1",
            [$storyId]
        );

        if ($canonicalSlug === false || $canonicalSlug === null) {
            return $this->stories->findFull($storyId);
        }

        return $this->stories->findBySlug((string)$canonicalSlug) ?? $this->stories->findFull($storyId);
    }

    /**
     * Aktualizacja z panelu admina — przy zmianie tytułu: nowy slug + alias starego URL.
     *
     * @param array{title:string, content:string, category_id:int, status:string} $data
     */
    public function updateAdmin(int $storyId, array $data): void
    {
        $story = $this->stories->find($storyId);
        if ($story === null) {
            return;
        }

        $oldSlug = (string)$story['slug'];
        $newSlug = Slugger::unique(
            $data['title'],
            fn (string $s): bool => $this->isSlugTaken($s, $storyId)
        );

        $update = [
            'title'       => $data['title'],
            'content'     => $data['content'],
            'excerpt'     => $this->makeExcerpt($data['content']),
            'category_id' => $data['category_id'],
            'status'      => $data['status'],
        ];

        if ($newSlug !== $oldSlug) {
            $this->slugAliases->record($storyId, $oldSlug);
            $this->slugAliases->deleteForStory($storyId, $newSlug);
            $update['slug'] = $newSlug;
        }

        if ($data['status'] === Story::STATUS_PUBLISHED && empty($story['published_at'])) {
            $update['published_at'] = date('Y-m-d H:i:s');
        }

        $contentChanged = $data['content'] !== (string)$story['content']
            || $data['title'] !== (string)$story['title'];

        $this->stories->update($storyId, $update);

        if ($data['status'] === Story::STATUS_PUBLISHED && $contentChanged) {
            $full = $this->stories->findFull($storyId);
            if ($full !== null) {
                $this->shareImage->regenerateAndStore($full);
            }
        }

        $this->clearAllCaches();
    }

    public function isSlugTaken(string $slug, ?int $exceptStoryId = null): bool
    {
        if ($exceptStoryId !== null) {
            if ((int)$this->stories->db()->fetchColumn(
                "SELECT COUNT(*) FROM `stories` WHERE slug = ? AND id <> ?",
                [$slug, $exceptStoryId]
            ) > 0) {
                return true;
            }

            return $this->slugAliases->isTakenByOtherStory($slug, $exceptStoryId);
        }

        return $this->stories->slugExists($slug) || $this->slugAliases->exists($slug);
    }

    /**
     * @param array<string, mixed> $data
     * @return array{id:int, slug:string, status:string}
     */
    public function create(array $data, ?int $userId, ?string $authorName): array
    {
        $title = trim((string)$data['title']);
        $content = trim((string)$data['content']);
        $status = $this->resolveStatus($userId);
        $slug = Slugger::unique($title, fn (string $s): bool => $this->isSlugTaken($s));
        $now = date('Y-m-d H:i:s');

        $id = $this->stories->insert([
            'title'                => $title,
            'slug'                 => $slug,
            'content'              => $content,
            'excerpt'              => $this->makeExcerpt($content),
            'category_id'          => (int)$data['category_id'],
            'user_id'              => $userId,
            'author_name'          => $authorName !== null && $authorName !== '' ? $authorName : null,
            'status'               => $status,
            'rating_avg'           => 0,
            'rating_sum'           => 0,
            'ratings_count'        => 0,
            'views'                => 0,
            'generated_image_path' => null,
            'created_at'           => $now,
            'published_at'         => $status === Story::STATUS_PUBLISHED ? $now : null,
        ]);

        if ($status === Story::STATUS_PUBLISHED) {
            $story = $this->stories->findFull($id);
            if ($story !== null) {
                $this->shareImage->generateAndStore($story);
            }
        }

        $this->clearAllCaches();

        return ['id' => $id, 'slug' => $slug, 'status' => $status];
    }

    private function resolveStatus(?int $userId): string
    {
        if ($userId === null) {
            return Story::STATUS_PENDING;
        }
        $requireModeration = $this->settings->get('stories_require_moderation', '1') === '1';
        return $requireModeration ? Story::STATUS_PENDING : Story::STATUS_PUBLISHED;
    }

    public function makeExcerpt(string $content, int $length = 160): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($content)) ?? '');
        if (mb_strlen($clean) <= $length) {
            return $clean;
        }
        return rtrim(mb_substr($clean, 0, $length)) . '…';
    }

    public function normalizeSort(string $sort): string
    {
        $allowed = ['newest', 'top_week', 'top_month', 'top_rated', 'top_all'];
        return in_array($sort, $allowed, true) ? $sort : 'newest';
    }

    public function clearAllCaches(): void
    {
        foreach (['stories', 'home', 'rankings', 'admin'] as $prefix) {
            $this->cache->clearByPrefix($prefix);
        }
    }

    /**
     * Odświeża cache list/wyszukiwarki i regeneruje obrazek historii.
     *
     * @return array{story_id:int, image_url:?string}|null
     */
    public function refreshStoryCache(int $storyId): ?array
    {
        $story = $this->stories->findFull($storyId);
        if ($story === null) {
            return null;
        }

        $this->clearAllCaches();
        $this->cache->clearByPrefix('search');

        $imageUrl = null;
        if ((string)$story['status'] === Story::STATUS_PUBLISHED) {
            $path = $this->shareImage->regenerateAndStore($story);
            $imageUrl = $this->shareImage->versionedUrl($path);
        }

        return [
            'story_id'  => $storyId,
            'image_url' => $imageUrl,
        ];
    }

    public function clearRankingCache(): void
    {
        $this->cache->clearByPrefix('rankings');
    }

    /** @deprecated Użyj clearAllCaches() */
    public function clearListingCache(): void
    {
        $this->clearAllCaches();
    }

    public function model(): Story
    {
        return $this->stories;
    }
}
