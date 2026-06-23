<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Cache;
use App\Core\Config;
use App\Models\Friendship;
use App\Models\Story;
use App\Models\User;

/**
 * Wyszukiwarka: historie, użytkownicy, znajomi (zalogowany).
 * Wyniki cache'owane per zapytanie + viewer.
 */
final class SearchService
{
    private Story $stories;
    private User $users;
    private Friendship $friendships;
    private Cache $cache;
    private int $minLength;
    private int $maxResults;
    private int $cacheTtl;

    public function __construct(Story $stories, User $users, Friendship $friendships, Cache $cache)
    {
        $this->stories = $stories;
        $this->users = $users;
        $this->friendships = $friendships;
        $this->cache = $cache;
        $this->minLength = (int)Config::get('app.search.min_length', 2);
        $this->maxResults = (int)Config::get('app.search.max_results', 8);
        $this->cacheTtl = (int)Config::get('app.search.cache_ttl', 120);
    }

    public function minLength(): int
    {
        return $this->minLength;
    }

    /**
     * @return array{
     *   query:string,
     *   stories:array,
     *   users:array,
     *   friends:array,
     *   total:int
     * }
     */
    public function search(string $query, ?int $viewerId = null, bool $searchContent = false): array
    {
        $query = $this->normalize($query);
        if (mb_strlen($query) < $this->minLength) {
            return $this->emptyResult($query);
        }

        $cacheKey = sprintf(
            'search:%s:%s:%d',
            $viewerId ?? 'guest',
            sha1(mb_strtolower($query)),
            $searchContent ? 1 : 0
        );

        return $this->cache->remember($cacheKey, function () use ($query, $viewerId, $searchContent): array {
            $stories = $this->stories->searchPublished($query, $this->maxResults, $searchContent);
            $users = $this->users->searchPublic($query, $this->maxResults);
            $friends = $viewerId !== null
                ? $this->friendships->searchFriends($viewerId, $query, $this->maxResults)
                : [];

            return [
                'query'   => $query,
                'stories' => $stories,
                'users'   => $users,
                'friends' => $friends,
                'total'   => count($stories) + count($users) + count($friends),
            ];
        }, $this->cacheTtl);
    }

    private function normalize(string $query): string
    {
        $query = trim(preg_replace('/\s+/u', ' ', $query) ?? '');
        if (mb_strlen($query) > 80) {
            $query = mb_substr($query, 0, 80);
        }

        return $query;
    }

    private function emptyResult(string $query): array
    {
        return [
            'query'   => $query,
            'stories' => [],
            'users'   => [],
            'friends' => [],
            'total'   => 0,
        ];
    }
}
