<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Controller;
use App\Core\Response;
use App\Models\Rating;
use App\Models\Story;
use App\Services\RatingService;
use App\Services\StoryService;

/**
 * Endpoint AJAX do oceniania historii (JSON).
 */
final class RatingController extends Controller
{
    private RatingService $ratingService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $storyModel = new Story($this->db);
        $storyService = new StoryService($storyModel, $this->cache, 12);
        $this->ratingService = new RatingService(new Rating($this->db), $storyModel, $storyService);
    }

    /**
     * POST /ajax/rate — story_id, rating, csrf.
     */
    public function ajaxRate(): Response
    {
        return $this->processRate(
            (int)$this->input('story_id', 0),
            (int)$this->input('rating', 0)
        );
    }

    /** Legacy: POST /ocena/{id} */
    public function rateLegacy(string $id): Response
    {
        return $this->processRate((int)$id, (int)$this->input('value', 0));
    }

    private function processRate(int $storyId, int $value): Response
    {
        if (!$this->csrf->verifyRequest()) {
            return $this->json(['success' => false, 'error' => 'Nieprawidłowy token CSRF.'], 403);
        }

        if ($storyId <= 0) {
            return $this->json(['success' => false, 'error' => 'Brak identyfikatora historii.'], 422);
        }

        $identifier = $this->clientIp() . '|' . session_id();
        if (!$this->rateLimiter->attempt('rating', $identifier)) {
            return $this->json(['success' => false, 'error' => 'Zbyt wiele ocen. Spróbuj później.'], 429);
        }

        $userId = $this->auth->id();
        $ipHash = $userId === null ? $this->ratingService->hashIp($this->clientIp()) : null;

        if ($userId === null && $this->session->has('rated_' . $storyId)) {
            return $this->json(['success' => false, 'error' => 'Ta historia została już oceniona.'], 409);
        }

        $result = $this->ratingService->rate($storyId, $value, $userId, $ipHash);

        if (!$result['success']) {
            return $this->json($result, 422);
        }

        if ($userId === null) {
            $this->session->set('rated_' . $storyId, $result['user_rating'] ?? $value);
        }

        return $this->json([
            'success'       => true,
            'rating_avg'    => $result['rating_avg'],
            'ratings_count' => $result['ratings_count'],
            'user_rating'   => $result['user_rating'],
        ]);
    }
}
