<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Prosty rate limiter oparty na cache plikowym.
 * Klucz budowany z nazwy akcji + identyfikatora (IP/sesja/user).
 */
final class RateLimiter
{
    private Cache $cache;

    /** @var array<string, array{max:int, window:int}> */
    private array $limits;

    /**
     * @param array<string, array{max:int, window:int}> $limits
     */
    public function __construct(Cache $cache, array $limits = [])
    {
        $this->cache = $cache;
        $this->limits = $limits;
    }

    /**
     * Czy akcja jest dozwolona (i rejestracja próby).
     */
    public function attempt(string $action, string $identifier): bool
    {
        [$max, $window] = $this->resolve($action);
        $key = $this->key($action, $identifier);

        $entry = $this->cache->get($key);
        $now = time();

        if (!is_array($entry) || ($entry['reset'] ?? 0) < $now) {
            $entry = ['count' => 0, 'reset' => $now + $window];
        }

        if ($entry['count'] >= $max) {
            return false;
        }

        $entry['count']++;
        $ttl = max(1, $entry['reset'] - $now);
        $this->cache->set($key, $entry, $ttl);

        return true;
    }

    /**
     * Sprawdza bez rejestrowania próby.
     */
    public function tooManyAttempts(string $action, string $identifier): bool
    {
        [$max] = $this->resolve($action);
        $entry = $this->cache->get($this->key($action, $identifier));
        if (!is_array($entry) || ($entry['reset'] ?? 0) < time()) {
            return false;
        }
        return ($entry['count'] ?? 0) >= $max;
    }

    public function clear(string $action, string $identifier): void
    {
        $this->cache->forget($this->key($action, $identifier));
    }

    public function retryAfter(string $action, string $identifier): int
    {
        $entry = $this->cache->get($this->key($action, $identifier));
        if (!is_array($entry)) {
            return 0;
        }
        return max(0, (int)($entry['reset'] ?? 0) - time());
    }

    /** Czyści wszystkie wpisy rate limitera (dev / CLI). */
    public function clearAll(): void
    {
        $this->cache->clearByPrefix('ratelimit');
    }

    /**
     * @return array{0:int,1:int} [max, window]
     */
    private function resolve(string $action): array
    {
        $limit = $this->limits[$action] ?? ['max' => 10, 'window' => 60];
        return [(int)$limit['max'], (int)$limit['window']];
    }

    private function key(string $action, string $identifier): string
    {
        return 'ratelimit:' . $action . ':' . sha1($identifier);
    }
}
