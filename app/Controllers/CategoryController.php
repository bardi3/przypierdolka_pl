<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Config;
use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Response;
use App\Core\Seo;
use App\Models\Category;
use App\Models\Story;
use App\Services\FeedService;
use App\Services\RankingService;
use App\Services\SeoSchemaService;
use App\Services\StoryService;

/**
 * Lista kategorii, historie w kategorii oraz ranking kategorii.
 */
final class CategoryController extends Controller
{
    private Category $categories;
    private StoryService $storyService;
    private RankingService $rankingService;
    private FeedService $feedService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->categories = new Category($this->db);
        $perPage = (int)Config::get('app.per_page', 12);
        $storyModel = new Story($this->db);
        $this->storyService = new StoryService($storyModel, $this->cache, $perPage);
        $this->rankingService = new RankingService($storyModel, $this->cache, $perPage);
        $this->feedService = new FeedService(
            $this->storyService,
            $this->rankingService,
            new Category($this->db)
        );
    }

    public function index(): Response
    {
        $categories = $this->cache->remember('home:categories', fn () => $this->categories->allWithCounts(), 600);

        $seo = (new Seo((string)Config::get('app.name'), (string)Config::get('app.url')))
            ->setTitle('Kategorie')
            ->setDescription('Przeglądaj humorystyczne historie według kategorii na przypierdolka.pl')
            ->setCanonical('/kategorie')
            ->setOgType('website')
            ->addJsonLd((new SeoSchemaService())->listingPage(
                $this->url('/kategorie'),
                'Kategorie',
                'Przeglądaj humorystyczne historie według kategorii na przypierdolka.pl',
                [],
                1,
                1,
                [
                    ['name' => 'Start', 'url' => $this->url('/')],
                    ['name' => 'Kategorie', 'url' => $this->url('/kategorie')],
                ]
            ));

        return $this->view('category/index', [
            'seo'        => $seo,
            'categories' => $categories,
            'breadcrumbs'=> [
                ['name' => 'Start', 'url' => url('/')],
                ['name' => 'Kategorie', 'url' => url('/kategorie')],
            ],
        ]);
    }

    public function show(string $slug): Response
    {
        $category = $this->categories->findBySlug($slug);
        if ($category === null) {
            throw HttpException::notFound('Taka kategoria nie istnieje.');
        }

        $pageNum = max(1, (int)$this->input('page', 1));
        $sort = $this->storyService->normalizeSort((string)$this->input('sort', 'newest'));
        $listing = $this->storyService->listing($sort, $pageNum, (int)$category['id']);

        if ($pageNum > $listing['pages']) {
            throw HttpException::notFound('Strona nie istnieje.');
        }

        $categories = $this->cache->remember('home:categories', fn () => $this->categories->allWithCounts(), 600);

        $desc = (string)($category['description'] ?? ('Historie z kategorii ' . $category['name']));
        $canonical = '/kategoria/' . $category['slug'];
        $pageUrl = $this->url($pageNum > 1 ? $canonical . '?page=' . $pageNum : $canonical);
        $breadcrumbItems = [
            ['name' => 'Start', 'url' => $this->url('/')],
            ['name' => 'Kategorie', 'url' => $this->url('/kategorie')],
            ['name' => (string)$category['name'], 'url' => $this->url($canonical)],
        ];

        $seo = (new Seo((string)Config::get('app.name'), (string)Config::get('app.url')))
            ->setTitle((string)$category['name'])
            ->setDescription($desc)
            ->setCanonical($pageNum > 1 ? $canonical . '?page=' . $pageNum : $canonical)
            ->addJsonLd((new SeoSchemaService())->categoryPage(
                $category,
                $pageUrl,
                $listing['items'],
                $breadcrumbItems,
                $pageNum,
                (int)Config::get('app.per_page', 12)
            ));

        return $this->view('home/index', array_merge([
            'seo'            => $seo,
            'listing'        => $listing,
            'categories'     => $categories,
            'userRatings'    => $this->loadUserRatings($listing['items']),
            'sort'           => $sort,
            'activeCategory' => $category,
            'baseListUrl'    => $canonical,
            'isRanking'      => false,
            'breadcrumbs'    => $breadcrumbItems,
        ], $this->wallMeta(FeedService::TYPE_CATEGORY, (string)$category['slug'])));
    }

    public function top(string $slug): Response
    {
        $category = $this->categories->findBySlug($slug);
        if ($category === null) {
            throw HttpException::notFound('Taka kategoria nie istnieje.');
        }

        $pageNum = max(1, (int)$this->input('page', 1));
        $listing = $this->rankingService->listing(Story::RANK_ALL, $pageNum, (int)$category['id']);

        if ($pageNum > $listing['pages']) {
            throw HttpException::notFound('Strona nie istnieje.');
        }

        $categories = $this->cache->remember('home:categories', fn () => $this->categories->allWithCounts(), 600);

        $title = 'Top kategorii: ' . $category['name'];
        $canonical = '/kategoria/' . $category['slug'] . '/top';
        $pageUrl = $this->url($pageNum > 1 ? $canonical . '?page=' . $pageNum : $canonical);
        $breadcrumbItems = [
            ['name' => 'Start', 'url' => $this->url('/')],
            ['name' => 'Kategorie', 'url' => $this->url('/kategorie')],
            ['name' => (string)$category['name'], 'url' => $this->url('/kategoria/' . $category['slug'])],
            ['name' => $title, 'url' => $pageUrl],
        ];

        $seo = (new Seo((string)Config::get('app.name'), (string)Config::get('app.url')))
            ->setTitle($title)
            ->setDescription($this->rankingService->seoDescription(Story::RANK_ALL, (string)$category['name']))
            ->setCanonical($pageNum > 1 ? $canonical . '?page=' . $pageNum : $canonical)
            ->addJsonLd((new SeoSchemaService())->listingPage(
                $pageUrl,
                $title,
                $this->rankingService->seoDescription(Story::RANK_ALL, (string)$category['name']),
                $listing['items'],
                $pageNum,
                (int)Config::get('app.per_page', 12),
                $breadcrumbItems
            ));

        return $this->view('home/index', array_merge([
            'seo'            => $seo,
            'listing'        => $listing,
            'categories'     => $categories,
            'userRatings'    => $this->loadUserRatings($listing['items']),
            'sort'           => Story::RANK_ALL,
            'activeCategory' => $category,
            'baseListUrl'    => $canonical,
            'rankingTitle'   => $title,
            'isRanking'      => true,
            'minRatings'     => $listing['min_ratings'],
            'breadcrumbs'    => $breadcrumbItems,
        ], $this->wallMeta(FeedService::TYPE_CATEGORY_TOP, (string)$category['slug'])));
    }

    /**
     * @return array{feedType:string, feedSlug:?string, trendingStories:array}
     */
    private function wallMeta(string $feedType, ?string $feedSlug = null): array
    {
        return [
            'feedType'        => $feedType,
            'feedSlug'        => $feedSlug,
            'trendingStories' => $this->feedService->trending(5),
        ];
    }
}
