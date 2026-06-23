<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Config;
use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Permissions;
use App\Core\Response;
use App\Core\Seo;
use App\Core\Validator;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Rating;
use App\Models\Story;
use App\Models\StoryImage;
use App\Services\AuditLogService;
use App\Services\FeedService;
use App\Services\ImageStoryService;
use App\Services\ModerationService;
use App\Services\RankingService;
use App\Services\RatingService;
use App\Services\SeoSchemaService;
use App\Services\ShareImageService;
use App\Services\StoryService;

/**
 * Wyświetlanie i dodawanie historii.
 */
final class StoryController extends Controller
{
    private StoryService $storyService;
    private Category $categories;
    private FeedService $feedService;
    private ImageStoryService $imageService;
    private ShareImageService $shareImage;
    private RatingService $ratingService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $perPage = (int)Config::get('app.per_page', 12);
        $storyModel = new Story($this->db);
        $shareImage = new ShareImageService($storyModel);
        $this->storyService = new StoryService($storyModel, $this->cache, $perPage, $shareImage);
        $this->categories = new Category($this->db);
        $this->feedService = new FeedService(
            $this->storyService,
            new RankingService($storyModel, $this->cache, $perPage),
            $this->categories
        );
        $this->imageService = new ImageStoryService(new StoryImage($this->db), $shareImage);
        $this->shareImage = $shareImage;
        $this->ratingService = new RatingService(new Rating($this->db), $storyModel, $this->storyService);
    }

    public function show(string $slug): Response
    {
        $requestedSlug = $slug;
        $story = $this->storyService->getBySlug($slug);
        if ($story === null) {
            throw HttpException::notFound('Taka historia nie istnieje.');
        }

        if ($requestedSlug !== $story['slug']) {
            return $this->redirect('/historia/' . $story['slug'], 301);
        }

        $isPreview = false;
        if ($story['status'] !== Story::STATUS_PUBLISHED) {
            if (!Permissions::canModerate($this->auth->role())) {
                throw HttpException::notFound('Taka historia nie istnieje.');
            }
            $isPreview = true;
        } else {
            $this->storyService->model()->incrementViews((int)$story['id']);
        }

        $url = $this->url('/historia/' . $story['slug']);
        $imagePath = $this->imageService->shareImagePath($story);
        $imageMeta = $this->shareImage->responsiveMeta($imagePath);
        $imageUrl = $this->shareImage->versionedUrl($imageMeta['src']);
        $shareImage = [
            'src'    => $imageUrl,
            'srcset' => $this->buildSrcset($imageMeta['variants']),
            'sizes'  => $imageMeta['sizes'],
            'width'  => $imageMeta['width'],
            'height' => $imageMeta['height'],
        ];

        $breadcrumbItems = [
            ['name' => 'Start', 'url' => $this->url('/')],
        ];
        if (!empty($story['category_name']) && !empty($story['category_slug'])) {
            $breadcrumbItems[] = [
                'name' => (string)$story['category_name'],
                'url'  => $this->url('/kategoria/' . $story['category_slug']),
            ];
        }
        $breadcrumbItems[] = [
            'name' => (string)$story['title'],
            'url'  => $url,
        ];

        $schema = new SeoSchemaService();
        $authorName = (string)($story['author_username'] ?? $story['author_name'] ?? 'Anonim');
        $publishedIso = iso8601($story['published_at'] ?? $story['created_at'] ?? null);

        $seo = (new Seo((string)Config::get('app.name'), (string)Config::get('app.url')))
            ->setTitle((string)$story['title'])
            ->setDescription((string)$story['excerpt'])
            ->setCanonical($url)
            ->setOgType('article')
            ->setOgImage($imageUrl)
            ->setArticleTimes($publishedIso, $publishedIso)
            ->setArticleAuthor($authorName)
            ->setArticleSection(!empty($story['category_name']) ? (string)$story['category_name'] : null)
            ->addJsonLd($schema->storyPage(
                $story,
                $url,
                $imageUrl,
                $breadcrumbItems,
                ['width' => $shareImage['width'], 'height' => $shareImage['height']]
            ));

        if ($isPreview) {
            $seo->setRobots('noindex, nofollow');
        }

        $userId = $this->auth->id();
        $storyOwnerId = (int)($story['user_id'] ?? 0);
        $canModerate = Permissions::canModerate($this->auth->role());
        $canEditStory = Permissions::canEditStory($this->auth->role(), $userId, $storyOwnerId);
        $ipHash = $userId === null ? $this->ratingService->hashIp($this->clientIp()) : null;
        $userRating = $this->ratingService->getUserRating((int)$story['id'], $userId, $ipHash);
        $alreadyRated = $isPreview || $userRating !== null;

        $prevStory = null;
        $nextStory = null;
        if (!$isPreview) {
            $model = $this->storyService->model();
            $publishedAt = $story['published_at'] ?? $story['created_at'] ?? null;
            $prevStory = $model->adjacentPublished((int)$story['id'], $publishedAt !== null ? (string)$publishedAt : null, 'prev');
            $nextStory = $model->adjacentPublished((int)$story['id'], $publishedAt !== null ? (string)$publishedAt : null, 'next');
        }

        return $this->view('stories/show', array_merge([
            'seo'          => $seo,
            'story'        => $story,
            'shareUrl'     => $url,
            'shareImage'   => $shareImage,
            'canModerate'  => $canModerate,
            'canEditStory' => $canEditStory,
            'breadcrumbs'  => $breadcrumbItems,
            'alreadyRated' => $alreadyRated,
            'userRating'   => $userRating,
            'isPreview'    => $isPreview,
            'prevStory'    => $prevStory,
            'nextStory'    => $nextStory,
        ], $this->sidebarMeta($this->activeCategoryFromStory($story))));
    }

    /** Losowa opublikowana historia. */
    public function random(): Response
    {
        $excludeId = (int)$this->input('exclude', 0);
        $slug = $this->storyService->model()->randomPublishedSlug($excludeId > 0 ? $excludeId : null);

        if ($slug === null) {
            $slug = $this->storyService->model()->randomPublishedSlug(null);
        }
        if ($slug === null) {
            throw HttpException::notFound('Brak opublikowanych historii.');
        }

        return $this->redirect('/historia/' . $slug);
    }

    public function create(): Response
    {
        return $this->renderCreateForm('/dodaj');
    }

    /** Legacy URL — przekierowanie na kanoniczny /dodaj. */
    public function createLegacy(): Response
    {
        return $this->redirect('/dodaj', 301);
    }

    private function renderCreateForm(string $canonicalPath): Response
    {
        $categories = $this->loadCategories();
        $seo = (new Seo((string)Config::get('app.name'), (string)Config::get('app.url')))
            ->setTitle('Dodaj historię')
            ->setDescription('Dodaj swoją humorystyczną historię na przypierdolka.pl')
            ->setRobots('noindex, follow')
            ->setCanonical($canonicalPath);

        return $this->view('stories/create', array_merge([
            'seo'         => $seo,
            'categories'  => $categories,
            'old'         => [],
            'errors'      => [],
            'formAction'  => '/dodaj',
        ], $this->sidebarMeta()));
    }

    public function store(): Response
    {
        return $this->handleStore(false);
    }

    public function ajaxStore(): Response
    {
        if ($denied = $this->rejectUnlessAjax()) {
            return $denied;
        }

        return $this->handleStore(true);
    }

    public function ajaxUpdate(string $id): Response
    {
        if ($denied = $this->rejectUnlessAjax()) {
            return $denied;
        }

        try {
            $this->verifyCsrf();
        } catch (HttpException $e) {
            return $this->json(['success' => false, 'error' => 'Nieprawidłowy token CSRF.'], 403);
        }

        $storyId = (int)$id;
        $story = $this->storyService->model()->findFull($storyId);
        if ($story === null) {
            return $this->json(['success' => false, 'error' => 'Historia nie istnieje.'], 404);
        }

        $userId = $this->auth->id();
        $canModerate = Permissions::canModerate($this->auth->role());
        if (!Permissions::canEditStory($this->auth->role(), $userId, (int)($story['user_id'] ?? 0))) {
            return $this->json(['success' => false, 'error' => 'Brak uprawnień do edycji.'], 403);
        }

        if ($story['status'] !== Story::STATUS_PUBLISHED && !$canModerate) {
            return $this->json(['success' => false, 'error' => 'Nie można edytować tej historii.'], 403);
        }

        $data = [
            'title'       => trim((string)$this->input('title', '')),
            'content'     => trim((string)$this->input('content', '')),
            'category_id' => (int)$this->input('category_id', 0),
            'status'      => $canModerate
                ? (string)$this->input('status', $story['status'])
                : (string)$story['status'],
        ];

        $fieldLabels = [
            'title'       => 'tytuł',
            'content'     => 'treść',
            'category_id' => 'kategoria',
        ];
        $rules = [
            'title'       => 'required|min:3|max:200',
            'content'     => 'required|min:10|max:5000',
            'category_id' => 'required|int',
        ];
        if ($canModerate) {
            $fieldLabels['status'] = 'status';
            $rules['status'] = 'required|in:published,pending,rejected';
        }

        $validator = new Validator($data, $fieldLabels);
        $validator->validate($rules);

        if ($data['category_id'] > 0 && $this->categories->find($data['category_id']) === null) {
            $validator->addError('category_id', 'Wybrana kategoria nie istnieje.');
        }

        if ($validator->fails()) {
            return $this->json([
                'success' => false,
                'error'   => 'Popraw błędy w formularzu.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $oldSlug = (string)$story['slug'];
        $this->storyService->updateAdmin($storyId, $data);

        (new ModerationService(
            $this->storyService->model(),
            $this->storyService,
            new AuditLogService(new AuditLog($this->db)),
            $this->cache,
            $this->shareImage
        ))->logEdit($storyId, $userId, [
            'title'  => $data['title'],
            'status' => $data['status'],
            'source' => 'inline',
        ], $this->clientIp());

        $updated = $this->storyService->model()->findFull($storyId);
        if ($updated === null) {
            return $this->json(['success' => false, 'error' => 'Nie udało się zapisać historii.'], 500);
        }

        $newSlug = (string)$updated['slug'];
        $imageUrl = null;
        if ((string)$updated['status'] === Story::STATUS_PUBLISHED) {
            $imagePath = $this->imageService->shareImagePath($updated);
            $imageUrl = $this->shareImage->versionedUrl($imagePath);
        }

        $payload = [
            'success'        => true,
            'message'        => 'Historia zapisana.',
            'title'          => (string)$updated['title'],
            'content'        => (string)$updated['content'],
            'slug'           => $newSlug,
            'status'         => (string)$updated['status'],
            'category_name'  => (string)($updated['category_name'] ?? ''),
            'category_slug'  => (string)($updated['category_slug'] ?? ''),
            'image_url'      => $imageUrl,
        ];

        if ($newSlug !== $oldSlug) {
            $payload['redirect'] = $this->url('/historia/' . $newSlug);
        }

        return $this->json($payload);
    }

    private function handleStore(bool $asJson): Response
    {
        try {
            $this->verifyCsrf();
        } catch (HttpException $e) {
            if ($asJson) {
                return $this->json(['success' => false, 'error' => 'Nieprawidłowy token CSRF.'], 403);
            }
            throw $e;
        }

        $userId = $this->auth->id();
        $rateAction = $userId === null ? 'story_add_guest' : 'story_add';
        $identifier = $this->clientIp() . '|' . session_id();

        if (!$this->rateLimiter->attempt($rateAction, $identifier)) {
            $retry = $this->rateLimiter->retryAfter($rateAction, $identifier);
            $msg = $userId === null
                ? "Limit historii dla gości wyczerpany. Spróbuj za {$retry} s lub zaloguj się."
                : 'Zbyt wiele prób dodania historii. Spróbuj później.';
            if ($asJson) {
                return $this->json(['success' => false, 'error' => $msg], 429);
            }
            $this->session->flash('error', $msg);
            return $this->redirect('/dodaj');
        }

        $isQuick = (string)$this->input('quick', '') === '1';
        $title = trim((string)$this->input('title', ''));
        $content = trim((string)$this->input('content', ''));
        if ($isQuick && $title === '') {
            $title = $this->deriveQuickTitle($content);
        }

        $data = [
            'title'            => $title,
            'content'          => $content,
            'category_id'      => (int)$this->input('category_id', 0),
            'author_name_guest'=> trim((string)$this->input('author_name_guest', '')),
            'website'          => trim((string)$this->input('website', '')),
        ];

        if ($data['website'] !== '') {
            if ($asJson) {
                return $this->json(['success' => false, 'error' => 'Wykryto spam.'], 403);
            }
            throw HttpException::forbidden('Wykryto spam.');
        }

        $validator = new Validator($data, [
            'title'       => 'tytuł',
            'content'     => 'treść',
            'category_id' => 'kategoria',
        ]);
        $validator->validate([
            'title'       => 'required|min:3|max:200',
            'content'     => 'required|min:10|max:5000',
            'category_id' => 'required|int',
        ]);

        if ($data['category_id'] > 0 && $this->categories->find($data['category_id']) === null) {
            $validator->addError('category_id', 'Wybrana kategoria nie istnieje.');
        }

        if ($userId === null && $data['author_name_guest'] !== '') {
            if (mb_strlen($data['author_name_guest']) > 60) {
                $validator->addError('author_name_guest', 'Pseudonim może mieć maksymalnie 60 znaków.');
            }
            if ($data['author_name_guest'] !== strip_tags($data['author_name_guest'])) {
                $validator->addError('author_name_guest', 'Pseudonim zawiera niedozwolone znaki.');
            }
        }

        if ($validator->fails()) {
            if ($asJson) {
                return $this->json([
                    'success' => false,
                    'error'   => 'Popraw błędy w formularzu.',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $seo = (new Seo((string)Config::get('app.name'), (string)Config::get('app.url')))
                ->setTitle('Dodaj historię')
                ->setRobots('noindex, follow')
                ->setCanonical('/dodaj');
            return $this->view('stories/create', array_merge([
                'seo'        => $seo,
                'categories' => $this->loadCategories(),
                'old'        => $data,
                'errors'     => $validator->errors(),
                'formAction' => '/dodaj',
            ], $this->sidebarMeta()), 422);
        }

        $authorName = $userId === null
            ? ($data['author_name_guest'] !== '' ? $data['author_name_guest'] : 'Anonim')
            : null;

        $result = $this->storyService->create($data, $userId, $authorName);

        if ($result['status'] === Story::STATUS_PUBLISHED) {
            $message = 'Historia została opublikowana!';
            $redirect = '/historia/' . $result['slug'];
            if ($asJson) {
                $story = $this->storyService->getBySlug($result['slug']);
                return $this->json([
                    'success'  => true,
                    'status'   => $result['status'],
                    'slug'     => $result['slug'],
                    'message'  => $message,
                    'redirect' => $this->url($redirect),
                    'html'     => $story !== null
                        ? $this->view->renderPartial('feed/_item', [
                            'story'      => $story,
                            'userRating' => null,
                        ])
                        : null,
                ]);
            }
            $this->session->flash('success', $message);
            return $this->redirect($redirect);
        }

        $message = 'Dziękujemy! Historia trafiła do moderacji i pojawi się po akceptacji.';
        if ($asJson) {
            return $this->json([
                'success'  => true,
                'status'   => $result['status'],
                'slug'     => $result['slug'],
                'message'  => $message,
                'redirect' => $this->url('/'),
            ]);
        }

        $this->session->flash('success', $message);
        return $this->redirect('/');
    }

    private function deriveQuickTitle(string $content): string
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $content) ?? '');
        if ($normalized === '') {
            return '';
        }

        if (preg_match('/^(.+?[.!?])(?:\s|$)/u', $normalized, $matches) === 1) {
            $title = trim($matches[1]);
        } else {
            $title = $normalized;
        }

        if (mb_strlen($title) > 200) {
            $title = rtrim(mb_substr($title, 0, 197)) . '…';
        }

        if (mb_strlen($title) < 3) {
            $title = rtrim(mb_substr($normalized, 0, 200));
        }

        return $title;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadCategories(): array
    {
        return $this->cache->remember('home:categories', fn () => $this->categories->allWithCounts(), 600);
    }

    /**
     * @return array{categories:array, trendingStories:array, activeCategory:?array, isRanking:bool}
     */
    private function sidebarMeta(?array $activeCategory = null): array
    {
        return [
            'categories'      => $this->loadCategories(),
            'trendingStories' => $this->feedService->trending(5),
            'activeCategory'  => $activeCategory,
            'isRanking'       => false,
        ];
    }

    /**
     * @param array<string, mixed> $story
     * @return array<string, mixed>|null
     */
    private function activeCategoryFromStory(array $story): ?array
    {
        if (empty($story['category_id']) || empty($story['category_slug'])) {
            return null;
        }

        return [
            'id'   => (int)$story['category_id'],
            'slug' => (string)$story['category_slug'],
            'name' => (string)($story['category_name'] ?? ''),
        ];
    }

    /**
     * @param list<array{path:string, width:int}> $variants
     */
    private function buildSrcset(array $variants): string
    {
        $parts = [];
        foreach ($variants as $variant) {
            $parts[] = $this->shareImage->versionedUrl($variant['path']) . ' ' . $variant['width'] . 'w';
        }

        return implode(', ', $parts);
    }
}
