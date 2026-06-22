<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Cache;
use App\Core\Config;
use App\Models\Story;

/**
 * Rankingi historii z cache i minimalną liczbą ocen.
 *
 * Okresy: top_week (tydzien), top_month (miesiac), top_all (wszystkie).
 */
final class RankingService
{
    private Story $stories;
    private Cache $cache;
    private int $perPage;
    private int $minRatings;
    private int $cacheTtl;

    public function __construct(Story $stories, Cache $cache, int $perPage = 12)
    {
        $this->stories = $stories;
        $this->cache = $cache;
        $this->perPage = $perPage;
        $this->minRatings = (int)Config::get('app.rankings.min_ratings', 2);
        $this->cacheTtl = (int)Config::get('app.cache.ttl_rankings', 600);
    }

    /**
     * @return array{items:array, total:int, page:int, pages:int, period:string, min_ratings:int}
     */
    public function listing(string $period, int $page = 1, ?int $categoryId = null): array
    {
        $page = max(1, $page);
        $period = $this->normalizePeriod($period);
        $offset = ($page - 1) * $this->perPage;

        $cacheKey = sprintf(
            'rankings:%s:%s:%d:%d',
            $period,
            $categoryId ?? 'all',
            $page,
            $this->minRatings
        );

        return $this->cache->remember($cacheKey, function () use ($period, $offset, $categoryId, $page): array {
            $items = $this->stories->ranking($period, $this->perPage, $offset, $categoryId, $this->minRatings);
            $total = $this->stories->countRanking($period, $categoryId, $this->minRatings);

            return [
                'items'       => $items,
                'total'       => $total,
                'page'        => $page,
                'pages'       => (int)max(1, ceil($total / $this->perPage)),
                'period'      => $period,
                'min_ratings' => $this->minRatings,
            ];
        }, $this->cacheTtl);
    }

    public function normalizePeriod(string $period): string
    {
        $map = [
            'tydzien'   => Story::RANK_WEEK,
            'miesiac'   => Story::RANK_MONTH,
            'wszystkie' => Story::RANK_ALL,
            'week'      => Story::RANK_WEEK,
            'month'     => Story::RANK_MONTH,
            'all'       => Story::RANK_ALL,
        ];
        return $map[$period] ?? (in_array($period, [Story::RANK_WEEK, Story::RANK_MONTH, Story::RANK_ALL], true)
            ? $period
            : Story::RANK_WEEK);
    }

    public function periodLabel(string $period): string
    {
        return match ($this->normalizePeriod($period)) {
            Story::RANK_MONTH => 'Top miesiąca',
            Story::RANK_ALL   => 'Top wszystkich czasów',
            default           => 'Top tygodnia',
        };
    }

    public function periodUrlSegment(string $period): string
    {
        return match ($this->normalizePeriod($period)) {
            Story::RANK_MONTH => 'miesiac',
            Story::RANK_ALL     => 'wszystkie',
            default             => 'tydzien',
        };
    }

    /**
     * Meta description rankingu — naturalny język, słowa kluczowe bez sztuczności.
     */
    public function seoDescription(string $period, ?string $categoryName = null): string
    {
        $minPhrase = ranking_min_accusative($this->minRatings);

        if ($categoryName !== null && $categoryName !== '') {
            return "Najlepiej oceniane historie w kategorii {$categoryName}. "
                . "Ranking obejmuje opowieści, które zebrały {$minPhrase}.";
        }

        $label = $this->periodLabel($period);

        return "{$label} na przypierdolka.pl — ranking najwyżej ocenianych historii. "
            . "Do listy trafiają tylko opowieści, które zebrały {$minPhrase}.";
    }

    public function clearCache(): void
    {
        $this->cache->clearByPrefix('rankings');
    }
}
