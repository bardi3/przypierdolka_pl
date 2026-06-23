<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Response;
use App\Core\Seo;
use App\Models\Category;
use App\Models\Friendship;
use App\Models\Setting;
use App\Models\Story;
use App\Services\FeedService;
use App\Services\RankingService;
use App\Services\SeoSchemaService;
use App\Services\StoryService;

/**
 * Strona główna, rankingi oraz endpointy SEO (sitemap, robots).
 */
final class HomeController extends Controller
{
    private StoryService $storyService;
    private RankingService $rankingService;
    private FeedService $feedService;
    private Category $categories;
    private Setting $settings;

    public function __construct(\App\Core\App $app)
    {
        parent::__construct($app);
        $perPage = (int)Config::get('app.per_page', 12);
        $storyModel = new Story($this->db);
        $this->storyService = new StoryService($storyModel, $this->cache, $perPage);
        $this->rankingService = new RankingService($storyModel, $this->cache, $perPage);
        $this->categories = new Category($this->db);
        $this->feedService = new FeedService($this->storyService, $this->rankingService, $this->categories);
        $this->settings = new Setting($this->db);
    }

    public function index(?string $page = null): Response
    {
        $sortParam = (string)$this->input('sort', 'newest');
        if ($sortParam !== 'newest') {
            $redirect = match ($sortParam) {
                'top_week'  => '/top/tydzien',
                'top_month' => '/top/miesiac',
                'top_all', 'top_rated' => '/top/wszystkie',
                default     => null,
            };
            if ($redirect !== null) {
                return $this->redirect($redirect, 301);
            }
        }

        if ($page === null && (int)$this->input('page', 0) > 1) {
            return $this->redirect('/strona/' . (int)$this->input('page'), 301);
        }

        $pageNum = max(1, (int)($page ?? 1));
        $sort = 'newest';
        $homePerPage = $this->settings->homeFeedPerPage();

        $listing = $this->storyService->listing($sort, $pageNum, null, $homePerPage);
        if ($pageNum > $listing['pages']) {
            throw HttpException::notFound('Strona nie istnieje.');
        }

        $categories = $this->loadCategories();

        $siteTitle = $this->settings->get('site_title', (string)Config::get('app.name'));
        $siteDesc = $this->settings->get('site_description', (string)Config::get('app.tagline'));
        $keywords = $this->settings->get('meta_keywords', '');

        $canonical = $pageNum > 1 ? "/strona/{$pageNum}" : '/';
        $pageUrl = $this->url($canonical);
        $schema = new SeoSchemaService();
        $siteName = (string)Config::get('app.name');
        $description = $siteDesc ?: (string)Config::get('app.tagline');
        if ($pageNum > 1) {
            $description = mb_substr(trim($description . " Strona {$pageNum}."), 0, 300);
        }

        $documentTitle = $pageNum === 1
            ? ($siteTitle !== '' ? $siteTitle : $siteName)
            : "Najnowsze historie — strona {$pageNum} | {$siteName}";

        $schemaName = $pageNum === 1
            ? ($siteTitle !== '' ? $siteTitle : $siteName)
            : "Najnowsze historie — strona {$pageNum}";

        $breadcrumbItems = null;
        if ($pageNum > 1) {
            $breadcrumbItems = [
                ['name' => 'Strona główna', 'url' => $this->url('/')],
                ['name' => "Strona {$pageNum}", 'url' => $pageUrl],
            ];
        }

        $seo = (new Seo($siteName, (string)Config::get('app.url')))
            ->setStandaloneTitle($documentTitle)
            ->setDescription($description)
            ->setCanonical($canonical)
            ->setOgType('website')
            ->setOgImage('/assets/img/og-default.webp', 1200, 630)
            ->setOgImageAlt($siteTitle !== '' ? $siteTitle : $siteName)
            ->addJsonLd($schema->homePage(
                $pageUrl,
                $schemaName,
                $siteDesc ?: null,
                $listing['items'],
                $pageNum,
                $homePerPage,
                $breadcrumbItems
            ));

        if ($keywords !== '') {
            $seo->setKeywords($keywords);
        }
        $this->applyPaginationSeo($seo, $pageNum, $listing['pages'], $canonical);

        return $this->view('home/index', array_merge([
            'seo'            => $seo,
            'listing'        => $listing,
            'categories'     => $categories,
            'userRatings'    => $this->loadUserRatings($listing['items']),
            'sort'           => $sort,
            'activeCategory' => null,
            'baseListUrl'    => '/',
            'isRanking'      => false,
            'isHomepage'     => true,
            'wallTitle'      => $pageNum === 1
                ? $this->homeHeading($siteTitle, (string)Config::get('app.tagline'))
                : 'Tablica społeczności — strona ' . $pageNum,
            'wallSubtitle'   => $pageNum === 1
                ? $description
                : 'Najświeższe historie od społeczności — oceń gwiazdkami bez wchodzenia w treść.',
            'breadcrumbs'    => $breadcrumbItems,
        ], $this->wallMeta(FeedService::TYPE_NEWEST)));
    }

    public function friendsFeed(): Response
    {
        $userId = (int)$this->auth->id();
        $pageNum = max(1, (int)$this->input('page', 1));
        $listing = $this->storyService->friendsListing($userId, $pageNum);

        if ($pageNum > $listing['pages']) {
            throw HttpException::notFound('Strona nie istnieje.');
        }

        $categories = $this->loadCategories();
        $friendsCount = (new Friendship($this->db))->countAccepted($userId);
        $canonical = $pageNum > 1 ? "/tablica/znajomi?page={$pageNum}" : '/tablica/znajomi';

        $seo = (new Seo((string)Config::get('app.name'), (string)Config::get('app.url')))
            ->setTitle('Moi znajomi')
            ->setDescription('Historie opublikowane przez Twoich znajomych na przypierdolka.pl')
            ->setCanonical($canonical)
            ->setRobots('noindex, nofollow');

        $this->applyPaginationSeo($seo, $pageNum, $listing['pages'], '/tablica/znajomi');

        return $this->view('home/index', array_merge([
            'seo'            => $seo,
            'listing'        => $listing,
            'categories'     => $categories,
            'userRatings'    => $this->loadUserRatings($listing['items']),
            'sort'           => 'newest',
            'activeCategory' => null,
            'baseListUrl'    => '/tablica/znajomi',
            'rankingTitle'   => 'Moi znajomi',
            'isRanking'      => false,
            'friendsCount'   => $friendsCount,
        ], $this->wallMeta(FeedService::TYPE_FRIENDS)));
    }

    public function topWeek(): Response
    {
        return $this->renderRanking(Story::RANK_WEEK, 'tydzien');
    }

    public function topMonth(): Response
    {
        return $this->renderRanking(Story::RANK_MONTH, 'miesiac');
    }

    public function topAll(): Response
    {
        return $this->renderRanking(Story::RANK_ALL, 'wszystkie');
    }

    /** Legacy alias /top/{period} — przekierowanie 301 na kanoniczny URL. */
    public function top(string $period): Response
    {
        $segment = $this->rankingService->periodUrlSegment($period);
        $canonical = '/top/' . $segment;
        $requestPath = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');

        if ($requestPath !== rtrim($canonical, '/')) {
            return $this->redirect($canonical, 301);
        }

        return match ($segment) {
            'miesiac'   => $this->topMonth(),
            'wszystkie' => $this->topAll(),
            default     => $this->topWeek(),
        };
    }

    private function renderRanking(string $period, string $urlSegment): Response
    {
        $pageNum = max(1, (int)$this->input('page', 1));
        $listing = $this->rankingService->listing($period, $pageNum);

        if ($pageNum > $listing['pages']) {
            throw HttpException::notFound('Strona nie istnieje.');
        }

        $categories = $this->loadCategories();
        $title = $this->rankingService->periodLabel($period);
        $basePath = "/top/{$urlSegment}";
        $canonical = $pageNum > 1 ? "{$basePath}?page={$pageNum}" : $basePath;
        $pageUrl = $this->url($canonical);
        $schema = new SeoSchemaService();
        $breadcrumbItems = [
            ['name' => 'Start', 'url' => $this->url('/')],
            ['name' => $title, 'url' => $pageUrl],
        ];

        $seo = (new Seo((string)Config::get('app.name'), (string)Config::get('app.url')))
            ->setTitle($title)
            ->setDescription($this->rankingService->seoDescription($period))
            ->setCanonical($canonical)
            ->addJsonLd($schema->listingPage(
                $pageUrl,
                $title,
                $this->rankingService->seoDescription($period),
                $listing['items'],
                $pageNum,
                (int)Config::get('app.per_page', 12),
                $breadcrumbItems
            ));

        $this->applyPaginationSeo($seo, $pageNum, $listing['pages'], $basePath);

        $feedType = match ($period) {
            Story::RANK_WEEK  => FeedService::TYPE_TOP_WEEK,
            Story::RANK_MONTH => FeedService::TYPE_TOP_MONTH,
            default           => FeedService::TYPE_TOP_ALL,
        };

        return $this->view('home/index', array_merge([
            'seo'            => $seo,
            'listing'        => $listing,
            'categories'     => $categories,
            'userRatings'    => $this->loadUserRatings($listing['items']),
            'sort'           => $period,
            'activeCategory' => null,
            'baseListUrl'    => $basePath,
            'rankingTitle'   => $title,
            'isRanking'      => true,
            'minRatings'     => $listing['min_ratings'],
            'breadcrumbs'    => $breadcrumbItems,
        ], $this->wallMeta($feedType)));
    }

    public function sitemap(): Response
    {
        $base = rtrim((string)Config::get('app.url'), '/');
        $stories = $this->db->fetchAll(
            "SELECT slug, COALESCE(published_at, created_at) AS lastmod FROM `stories` WHERE status = 'published' ORDER BY id DESC LIMIT 5000"
        );
        $categories = $this->db->fetchAll("SELECT slug FROM `categories`");

        $xml = $this->view->renderPartial('seo/sitemap', [
            'base'       => $base,
            'stories'    => $stories,
            'categories' => $categories,
        ]);

        return Response::text($xml, 200, 'application/xml');
    }

    public function robots(): Response
    {
        $base = rtrim((string)Config::get('app.url'), '/');
        $body = "User-agent: *\n"
            . "Allow: /\n"
            . "Disallow: /admineu\n"
            . "Disallow: /logowanie\n"
            . "Disallow: /rejestracja\n"
            . "Disallow: /dodaj\n"
            . "Disallow: /konto\n\n"
            . "Sitemap: {$base}/sitemap.xml\n";

        return Response::text($body, 200, 'text/plain');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadCategories(): array
    {
        return $this->cache->remember('home:categories', fn () => $this->categories->allWithCounts(), 600);
    }

    /**
     * @return array{feedType:string, feedSlug:?string, trendingStories:array}
     */
    private function wallMeta(string $feedType, ?string $feedSlug = null): array
    {
        return [
            'feedType'         => $feedType,
            'feedSlug'         => $feedSlug,
            'trendingStories'  => $this->feedService->trending(5),
        ];
    }

    private function applyPaginationSeo(Seo $seo, int $page, int $pages, string $basePath): void
    {
        if ($page > 1) {
            $prev = $page === 2
                ? ($basePath === '/' ? '/' : $basePath)
                : ($basePath === '/' ? "/strona/" . ($page - 1) : "{$basePath}?page=" . ($page - 1));
            $seo->setPrevUrl($prev);
        }
        if ($page < $pages) {
            $next = $basePath === '/'
                ? "/strona/" . ($page + 1)
                : "{$basePath}?page=" . ($page + 1);
            $seo->setNextUrl($next);
        }
    }

    private function homeHeading(string $siteTitle, string $tagline): string
    {
        if (preg_match('/[—–\-]\s*(.+)/u', $siteTitle, $matches) === 1) {
            $part = trim($matches[1]);
            if ($part !== '') {
                return $part;
            }
        }

        return $tagline !== '' ? $tagline : 'Tablica społeczności';
    }
}
