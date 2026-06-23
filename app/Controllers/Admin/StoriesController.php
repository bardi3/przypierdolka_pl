<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Config;
use App\Core\HttpException;
use App\Core\Response;
use App\Core\Validator;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Story;
use App\Services\AuditLogService;
use App\Services\ModerationService;
use App\Services\ShareImageService;
use App\Services\StoryService;

/**
 * Zarządzanie historiami w panelu: lista, moderacja, edycja, usuwanie.
 */
final class StoriesController extends AdminController
{
    private Story $stories;
    private StoryService $storyService;
    private ModerationService $moderation;
    private Category $categories;

    public function __construct(\App\Core\App $app)
    {
        parent::__construct($app);
        $this->stories = new Story($this->db);
        $shareImage = new ShareImageService($this->stories);
        $this->storyService = new StoryService($this->stories, $this->cache, (int)Config::get('app.per_page', 12), $shareImage);
        $this->moderation = new ModerationService(
            $this->stories,
            $this->storyService,
            new AuditLogService(new AuditLog($this->db)),
            $this->cache,
            $shareImage
        );
        $this->categories = new Category($this->db);
    }

    public function index(): Response
    {
        $filter = (string)$this->input('status', '');
        $status = in_array($filter, [Story::STATUS_PUBLISHED, Story::STATUS_PENDING, Story::STATUS_REJECTED], true)
            ? $filter
            : null;

        return $this->renderList($status, $status !== null ? 'Historie: ' . $status : 'Wszystkie historie');
    }

    public function pending(): Response
    {
        return $this->renderList(Story::STATUS_PENDING, 'Historie do moderacji');
    }

    private function renderList(?string $status, string $title): Response
    {
        $page = max(1, (int)$this->input('page', 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $items = $this->stories->forAdmin($status, $perPage, $offset);
        $total = $this->stories->countByStatus($status);

        return $this->view('admin/stories/index', [
            'seo'      => $this->adminSeo($title),
            'title'    => $title,
            'items'    => $items,
            'status'   => $status,
            'page'     => $page,
            'pages'    => (int)max(1, ceil($total / $perPage)),
        ]);
    }

    public function edit(string $id): Response
    {
        $story = $this->stories->findFull((int)$id);
        if ($story === null) {
            throw HttpException::notFound('Historia nie istnieje.');
        }

        return $this->view('admin/stories/edit', [
            'seo'        => $this->adminSeo('Edycja historii'),
            'story'      => $story,
            'categories' => $this->categories->allWithCounts(),
            'errors'     => [],
        ]);
    }

    public function update(string $id): Response
    {
        $this->verifyCsrf();
        $storyId = (int)$id;
        $story = $this->stories->find($storyId);
        if ($story === null) {
            throw HttpException::notFound('Historia nie istnieje.');
        }

        $data = [
            'title'       => trim((string)$this->input('title', '')),
            'content'     => trim((string)$this->input('content', '')),
            'category_id' => (int)$this->input('category_id', 0),
            'status'      => (string)$this->input('status', $story['status']),
        ];

        $validator = new Validator($data, [
            'title' => 'tytuł', 'content' => 'treść', 'category_id' => 'kategoria',
        ]);
        $validator->validate([
            'title'       => 'required|min:3|max:200',
            'content'     => 'required|min:10|max:5000',
            'category_id' => 'required|int',
            'status'      => 'required|in:published,pending,rejected',
        ]);

        if ($validator->fails()) {
            $full = $this->stories->findFull($storyId);
            return $this->view('admin/stories/edit', [
                'seo'        => $this->adminSeo('Edycja historii'),
                'story'      => array_merge($full ?? [], $data),
                'categories' => $this->categories->allWithCounts(),
                'errors'     => $validator->errors(),
            ], 422);
        }

        $update = [
            'title'       => $data['title'],
            'content'     => $data['content'],
            'category_id' => $data['category_id'],
            'status'      => $data['status'],
        ];

        $this->storyService->updateAdmin($storyId, $update);
        $this->moderation->logEdit($storyId, $this->auth->id(), [
            'title'  => $data['title'],
            'status' => $data['status'],
        ], $this->clientIp());

        $this->session->flash('success', 'Historia zaktualizowana.');
        return $this->redirect($this->adminUrl('stories'));
    }

    public function refreshCache(string $id): Response
    {
        if ($denied = $this->rejectUnlessAjax()) {
            return $denied;
        }

        if (!$this->csrf->verifyRequest()) {
            return $this->json(['success' => false, 'error' => 'Nieprawidłowy token CSRF.'], 403);
        }

        $storyId = (int)$id;
        $result = $this->storyService->refreshStoryCache($storyId);
        if ($result === null) {
            return $this->json(['success' => false, 'error' => 'Historia nie istnieje.'], 404);
        }

        return $this->json([
            'success'   => true,
            'message'   => 'Cache historii odświeżony.',
            'story_id'  => $result['story_id'],
            'image_url' => $result['image_url'],
        ]);
    }

    public function approve(string $id): Response
    {
        $this->verifyCsrf();
        $this->moderation->approve((int)$id, $this->auth->id(), $this->clientIp());
        $this->session->flash('success', 'Historia zaakceptowana i opublikowana.');
        return $this->back();
    }

    public function reject(string $id): Response
    {
        $this->verifyCsrf();
        $this->moderation->reject((int)$id, $this->auth->id(), $this->clientIp());
        $this->session->flash('success', 'Historia odrzucona.');
        return $this->back();
    }

    public function delete(string $id): Response
    {
        $this->verifyCsrf();
        $storyId = (int)$id;
        $story = $this->stories->find($storyId);
        $this->stories->delete($storyId);
        if ($story !== null) {
            (new AuditLogService(new AuditLog($this->db)))->record(
                $this->auth->id(),
                'story.delete',
                'story',
                $storyId,
                ['title' => $story['title']],
                $this->clientIp()
            );
        }
        $this->storyService->clearAllCaches();
        $this->session->flash('success', 'Historia usunięta.');
        return $this->back();
    }
}
