<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Cache;
use App\Models\Story;

/**
 * Moderacja historii: akceptacja, odrzucenie + log audytu.
 */
final class ModerationService
{
    private Story $stories;
    private StoryService $storyService;
    private AuditLogService $audit;
    private ShareImageService $shareImage;
    private Cache $cache;

    public function __construct(
        Story $stories,
        StoryService $storyService,
        AuditLogService $audit,
        Cache $cache,
        ?ShareImageService $shareImage = null
    ) {
        $this->stories = $stories;
        $this->storyService = $storyService;
        $this->audit = $audit;
        $this->cache = $cache;
        $this->shareImage = $shareImage ?? new ShareImageService($stories);
    }

    public function approve(int $storyId, ?int $actorId, ?string $ip = null): bool
    {
        $story = $this->stories->find($storyId);
        if ($story === null) {
            return false;
        }

        $data = ['status' => Story::STATUS_PUBLISHED];
        if (empty($story['published_at'])) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }

        $this->stories->update($storyId, $data);

        $full = $this->stories->findFull($storyId);
        if ($full !== null) {
            $this->shareImage->generateAndStore($full);
        }

        $this->audit->record($actorId, 'story.approve', 'story', $storyId, [
            'title'  => $story['title'],
            'status' => Story::STATUS_PUBLISHED,
        ], $ip);

        $this->storyService->clearAllCaches();
        $this->cacheClearAdminPending();

        return true;
    }

    public function reject(int $storyId, ?int $actorId, ?string $ip = null): bool
    {
        $story = $this->stories->find($storyId);
        if ($story === null) {
            return false;
        }

        $this->stories->update($storyId, ['status' => Story::STATUS_REJECTED]);

        $this->audit->record($actorId, 'story.reject', 'story', $storyId, [
            'title'  => $story['title'],
            'status' => Story::STATUS_REJECTED,
        ], $ip);

        $this->storyService->clearAllCaches();
        $this->cacheClearAdminPending();

        return true;
    }

    public function logEdit(int $storyId, ?int $actorId, array $changes, ?string $ip = null): void
    {
        $this->audit->record($actorId, 'story.edit', 'story', $storyId, $changes, $ip);
    }

    public function pendingCount(): int
    {
        return $this->stories->countByStatus(Story::STATUS_PENDING);
    }

    private function cacheClearAdminPending(): void
    {
        $this->cache->clearByPrefix('admin');
    }
}
