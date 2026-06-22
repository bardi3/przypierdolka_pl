<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Rating;
use App\Models\Story;

/**
 * Logika ocen: walidacja, unikalność, zapis i aktualizacja agregatów w stories.
 */
final class RatingService
{
    private Rating $ratings;
    private Story $stories;
    private StoryService $storyService;

    public function __construct(Rating $ratings, Story $stories, StoryService $storyService)
    {
        $this->ratings = $ratings;
        $this->stories = $stories;
        $this->storyService = $storyService;
    }

    /**
     * Zapisuje ocenę i przelicza statystyki historii.
     *
     * @return array{success:bool, error?:string, rating_avg?:float, ratings_count?:int, user_rating?:int}
     */
    public function rate(int $storyId, int $value, ?int $userId, ?string $ipHash): array
    {
        if ($value < 1 || $value > 5) {
            return ['success' => false, 'error' => 'Ocena musi być w zakresie 1-5.'];
        }

        $story = $this->stories->find($storyId);
        if ($story === null || $story['status'] !== Story::STATUS_PUBLISHED) {
            return ['success' => false, 'error' => 'Historia nie istnieje lub nie jest opublikowana.'];
        }

        if ($this->ratings->exists($storyId, $userId, $ipHash)) {
            $existing = $this->ratings->getUserRating($storyId, $userId, $ipHash);
            return [
                'success'       => false,
                'error'         => 'Ta historia została już przez Ciebie oceniona.',
                'user_rating'   => $existing,
                'rating_avg'    => (float)$story['rating_avg'],
                'ratings_count' => (int)$story['ratings_count'],
            ];
        }

        $db = $this->stories->db();
        $db->beginTransaction();
        try {
            $this->ratings->add($storyId, $value, $userId, $ipHash);
            $agg = $this->ratings->aggregate($storyId);
            $this->stories->updateRatingStats($storyId, $agg['sum'], $agg['count']);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            $logFile = (string)\App\Core\Config::get('app.paths.logs') . '/app-' . date('Y-m-d') . '.log';
            @file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . "] Rating error: {$e->getMessage()}\n", FILE_APPEND | LOCK_EX);
            return ['success' => false, 'error' => 'Nie udało się zapisać oceny.'];
        }

        $this->storyService->clearRankingCache();

        $avg = $agg['count'] > 0 ? round($agg['sum'] / $agg['count'], 2) : 0.0;
        return [
            'success'       => true,
            'rating_avg'    => $avg,
            'ratings_count' => $agg['count'],
            'user_rating'   => $value,
        ];
    }

    public function hashIp(string $ip): string
    {
        $salt = (string)\App\Core\Config::get('security.ip_salt', '');
        return hash('sha256', $salt . '|' . $ip);
    }

    public function getUserRating(int $storyId, ?int $userId, ?string $ipHash): ?int
    {
        return $this->ratings->getUserRating($storyId, $userId, $ipHash);
    }
}
