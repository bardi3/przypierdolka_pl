<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Config;
use App\Core\Controller;
use App\Core\Response;
use App\Core\Seo;
use App\Models\Category;
use App\Models\Friendship;
use App\Models\Story;
use App\Models\User;
use App\Services\FeedService;
use App\Services\RankingService;
use App\Services\SearchService;
use App\Services\StoryService;

/**
 * Wyszukiwarka — strona wyników i AJAX (z rate limitem).
 */
final class SearchController extends Controller
{
    private SearchService $search;
    private FeedService $feed;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $perPage = (int)Config::get('app.per_page', 12);
        $storyModel = new Story($this->db);
        $storyService = new StoryService($storyModel, $this->cache, $perPage);
        $categories = new Category($this->db);
        $this->search = new SearchService(
            $storyModel,
            new User($this->db),
            new Friendship($this->db),
            $this->cache
        );
        $this->feed = new FeedService(
            $storyService,
            new RankingService($storyModel, $this->cache, $perPage),
            $categories
        );
    }

    public function index(): Response
    {
        $query = trim((string)$this->input('q', ''));
        $viewerId = $this->auth->id();
        $rateLimitError = null;
        $results = $this->emptySearchResult($query);

        if ($query !== '' && mb_strlen($query) >= $this->search->minLength()) {
            $rateLimitError = $this->searchRateLimitMessage();
            if ($rateLimitError === null) {
                $results = $this->search->search($query, $viewerId, true);
            }
        }

        $title = $query !== '' ? 'Szukaj: ' . $query : 'Szukaj';
        $seo = (new Seo((string)Config::get('app.name'), (string)Config::get('app.url')))
            ->setTitle($title)
            ->setRobots('noindex, follow')
            ->setCanonical($query !== '' ? '/szukaj?q=' . rawurlencode($query) : '/szukaj');

        return $this->view('search/index', array_merge([
            'seo'            => $seo,
            'query'          => $query,
            'results'        => $results,
            'rateLimitError' => $rateLimitError,
        ], $this->sidebarMeta()));
    }

    public function ajax(): Response
    {
        if ($denied = $this->rejectUnlessAjax()) {
            return $denied;
        }

        if (trim((string)$this->input('website', '')) !== '') {
            return $this->json(['success' => false, 'error' => 'Odrzucono.'], 403);
        }

        $query = trim((string)$this->input('q', ''));
        if (mb_strlen($query) < $this->search->minLength()) {
            return $this->json([
                'success' => true,
                'query'   => $query,
                'html'    => '',
                'total'   => 0,
                'hint'    => 'Wpisz co najmniej ' . $this->search->minLength() . ' znaki.',
            ]);
        }

        $rateLimitError = $this->searchRateLimitMessage();
        if ($rateLimitError !== null) {
            return $this->json(['success' => false, 'error' => $rateLimitError], 429);
        }

        $viewerId = $this->auth->id();
        $results = $this->search->search($query, $viewerId, false);

        $html = $this->view->renderPartial('search/_dropdown', [
            'results' => $results,
        ]);

        return $this->json([
            'success' => true,
            'query'   => $results['query'],
            'html'    => $html,
            'total'   => $results['total'],
        ]);
    }

    /**
     * @return array{query:string, stories:array, users:array, friends:array, total:int}
     */
    private function emptySearchResult(string $query): array
    {
        return [
            'query'   => $query,
            'stories' => [],
            'users'   => [],
            'friends' => [],
            'total'   => 0,
        ];
    }

    /**
     * @return array{categories:array, trendingStories:array}
     */
    private function sidebarMeta(): array
    {
        $categories = new Category($this->db);

        return [
            'categories'      => $this->cache->remember('home:categories', fn () => $categories->allWithCounts(), 600),
            'trendingStories' => $this->feed->trending(5),
        ];
    }
}
