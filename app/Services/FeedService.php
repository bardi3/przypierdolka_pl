<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Story;

/**
 * Jednolita tablica / wall — najnowsze, rankingi, kategorie.
 */
final class FeedService
{
    public const TYPE_NEWEST       = 'newest';
    public const TYPE_FRIENDS      = 'friends';
    public const TYPE_TOP_WEEK     = 'top_week';
    public const TYPE_TOP_MONTH    = 'top_month';
    public const TYPE_TOP_ALL      = 'top_all';
    public const TYPE_CATEGORY     = 'category';
    public const TYPE_CATEGORY_TOP = 'category_top';

    private StoryService $storyService;
    private RankingService $rankingService;
    private Category $categories;

    public function __construct(
        StoryService $storyService,
        RankingService $rankingService,
        Category $categories
    ) {
        $this->storyService = $storyService;
        $this->rankingService = $rankingService;
        $this->categories = $categories;
    }

    /**
     * @return array{items:array, total:int, page:int, pages:int, min_ratings?:int}
     */
    public function listing(string $feedType, int $page = 1, ?string $categorySlug = null, ?int $viewerId = null): array
    {
        $categoryId = $this->resolveCategoryId($categorySlug);
        $page = max(1, $page);

        return match ($feedType) {
            self::TYPE_FRIENDS => $viewerId !== null
                ? $this->storyService->friendsListing($viewerId, $page)
                : ['items' => [], 'total' => 0, 'page' => $page, 'pages' => 1],
            self::TYPE_TOP_WEEK => $this->rankingService->listing(Story::RANK_WEEK, $page, $categoryId),
            self::TYPE_TOP_MONTH => $this->rankingService->listing(Story::RANK_MONTH, $page, $categoryId),
            self::TYPE_TOP_ALL, self::TYPE_CATEGORY_TOP
                => $this->rankingService->listing(Story::RANK_ALL, $page, $categoryId),
            self::TYPE_CATEGORY => $this->storyService->listing('newest', $page, $categoryId),
            default => $this->storyService->listing('newest', $page, null),
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function trending(int $limit = 5): array
    {
        $listing = $this->rankingService->listing(Story::RANK_WEEK, 1);

        return array_slice($listing['items'], 0, $limit);
    }

    public function isRankingFeed(string $feedType): bool
    {
        return in_array($feedType, [
            self::TYPE_TOP_WEEK,
            self::TYPE_TOP_MONTH,
            self::TYPE_TOP_ALL,
            self::TYPE_CATEGORY_TOP,
        ], true);
    }

    private function resolveCategoryId(?string $slug): ?int
    {
        if ($slug === null || $slug === '') {
            return null;
        }
        $category = $this->categories->findBySlug($slug);

        return $category !== null ? (int)$category['id'] : null;
    }
}
