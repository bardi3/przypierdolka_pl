<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Config;
use App\Core\Controller;
use App\Core\Response;
use App\Models\Category;
use App\Models\Setting;
use App\Models\Story;
use App\Services\FeedService;
use App\Services\RankingService;
use App\Services\StoryService;

/**
 * AJAX — kolejne wpisy tablicy (infinite scroll).
 */
final class FeedController extends Controller
{
    private FeedService $feed;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $perPage = (int)Config::get('app.per_page', 12);
        $storyModel = new Story($this->db);
        $this->feed = new FeedService(
            new StoryService($storyModel, $this->cache, $perPage),
            new RankingService($storyModel, $this->cache, $perPage),
            new Category($this->db)
        );
    }

    public function load(): Response
    {
        if ($denied = $this->rejectUnlessAjax()) {
            return $denied;
        }

        $rateMsg = $this->feedRateLimitMessage();
        if ($rateMsg !== null) {
            return $this->json(['success' => false, 'error' => $rateMsg], 429);
        }

        $page = max(1, min(500, (int)$this->input('page', 1)));
        $feedType = (string)$this->input('feed', FeedService::TYPE_NEWEST);
        $slug = trim((string)$this->input('slug', ''));

        $allowed = [
            FeedService::TYPE_NEWEST,
            FeedService::TYPE_FRIENDS,
            FeedService::TYPE_TOP_WEEK,
            FeedService::TYPE_TOP_MONTH,
            FeedService::TYPE_TOP_ALL,
            FeedService::TYPE_CATEGORY,
            FeedService::TYPE_CATEGORY_TOP,
        ];
        if (!in_array($feedType, $allowed, true)) {
            $feedType = FeedService::TYPE_NEWEST;
        }

        if ($slug !== '' && !$this->isValidSlug($slug)) {
            return $this->json(['success' => false, 'error' => 'Nieprawidłowy parametr.'], 400);
        }

        if ($feedType === FeedService::TYPE_FRIENDS && !$this->auth->check()) {
            return $this->json(['success' => false, 'error' => 'Wymagane logowanie.'], 401);
        }

        $viewerId = $feedType === FeedService::TYPE_FRIENDS ? $this->auth->id() : null;
        $perPage = $this->resolveFeedPerPage($feedType, $slug);
        $listing = $this->feed->listing(
            $feedType,
            $page,
            $slug !== '' ? $slug : null,
            $viewerId,
            $perPage
        );

        if ($page > $listing['pages']) {
            return $this->json(['success' => false, 'error' => 'Koniec tablicy.'], 404);
        }

        $userRatings = $this->loadUserRatings($listing['items']);
        $html = $this->view->renderPartial('feed/_list', [
            'stories'     => $listing['items'],
            'userRatings' => $userRatings,
        ]);

        return $this->json([
            'success'  => true,
            'html'     => $html,
            'page'     => $page,
            'pages'    => $listing['pages'],
            'has_more' => $page < $listing['pages'],
        ]);
    }

    private function resolveFeedPerPage(string $feedType, string $slug): ?int
    {
        if ($feedType === FeedService::TYPE_NEWEST && $slug === '') {
            return (new Setting($this->db))->homeFeedPerPage();
        }

        return null;
    }
}
