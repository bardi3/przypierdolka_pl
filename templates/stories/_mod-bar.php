<?php
/**
 * Belka szybkiej moderacji na stronie historii (admin / moderator).
 *
 * @var array<string, mixed> $story
 * @var bool $canModerate
 * @var string $admin_prefix
 * @var \App\Core\Csrf $csrf
 */
use App\Models\Story;

if (!($canModerate ?? false)) {
    return;
}

$adminUrl = static fn (string $p): string => url(ltrim($admin_prefix . '/' . ltrim($p, '/'), '/'));
$storyId = (int)$story['id'];
$status = (string)$story['status'];
?>
<div class="story-mod-bar" role="toolbar" aria-label="Moderacja historii">
    <div class="story-mod-bar__info">
        <span class="story-mod-bar__label">Moderacja</span>
        <?= $view->renderPartial('admin/stories/_status', ['status' => $status]) ?>
    </div>
    <div class="story-mod-bar__actions">
        <button type="button"
                class="story-mod-bar__btn"
                data-story-inline-edit-toggle
                aria-pressed="false">
            Edytuj
        </button>

        <?= $view->renderPartial('admin/stories/_cache-refresh-btn', [
            'storyId'      => $storyId,
            'admin_prefix' => $admin_prefix,
            'csrf'         => $csrf,
            'variant'      => 'mod-bar',
        ]) ?>

        <?php if ($status === Story::STATUS_PENDING): ?>
            <form method="post" action="<?= e($adminUrl('stories/approve/' . $storyId)) ?>" class="story-mod-bar__form">
                <input type="hidden" name="<?= e($csrf->tokenName()) ?>" value="<?= e($csrf->token()) ?>">
                <button type="submit" class="story-mod-bar__btn story-mod-bar__btn--success">Akceptuj</button>
            </form>
            <form method="post" action="<?= e($adminUrl('stories/reject/' . $storyId)) ?>" class="story-mod-bar__form">
                <input type="hidden" name="<?= e($csrf->tokenName()) ?>" value="<?= e($csrf->token()) ?>">
                <button type="submit" class="story-mod-bar__btn story-mod-bar__btn--danger">Odrzuć</button>
            </form>
        <?php endif; ?>

        <?php if ($status === Story::STATUS_PUBLISHED): ?>
            <form method="post" action="<?= e($adminUrl('stories/reject/' . $storyId)) ?>" class="story-mod-bar__form"
                  onsubmit="return confirm('Archiwizować (ukryć) tę historię?');">
                <input type="hidden" name="<?= e($csrf->tokenName()) ?>" value="<?= e($csrf->token()) ?>">
                <button type="submit" class="story-mod-bar__btn story-mod-bar__btn--warn">Archiwizuj</button>
            </form>
        <?php endif; ?>

        <?php if ($status === Story::STATUS_REJECTED): ?>
            <form method="post" action="<?= e($adminUrl('stories/approve/' . $storyId)) ?>" class="story-mod-bar__form">
                <input type="hidden" name="<?= e($csrf->tokenName()) ?>" value="<?= e($csrf->token()) ?>">
                <button type="submit" class="story-mod-bar__btn story-mod-bar__btn--success">Przywróć</button>
            </form>
        <?php endif; ?>

        <form method="post" action="<?= e($adminUrl('stories/delete/' . $storyId)) ?>" class="story-mod-bar__form"
              onsubmit="return confirm('Trwale usunąć tę historię?');">
            <input type="hidden" name="<?= e($csrf->tokenName()) ?>" value="<?= e($csrf->token()) ?>">
            <button type="submit" class="story-mod-bar__btn story-mod-bar__btn--danger">Usuń</button>
        </form>

        <a class="story-mod-bar__btn story-mod-bar__btn--ghost" href="<?= e($adminUrl('stories')) ?>">Lista</a>
    </div>
</div>
