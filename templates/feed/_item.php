<?php
/**
 * Wpis tablicy (wall / social feed).
 *
 * @var array<string, mixed> $story
 * @var int|null $userRating
 * @var \App\Core\Csrf $csrf
 */
$userRating = $userRating ?? null;
$alreadyRated = $userRating !== null;
$storyId = (int)$story['id'];
$storyUrl = url('/historia/' . $story['slug']);
$authorName = (string)($story['author_username'] ?? $story['author_name'] ?? 'Anonim');
$authorUsername = $story['author_username'] ?? null;
$authorProfileUrl = ($authorUsername !== null && $authorUsername !== '')
    ? url('/profil/' . $authorUsername)
    : null;
$publishedAt = $story['published_at'] ?? $story['created_at'];
?>
<article class="feed-post">
    <header class="feed-post__head">
        <?php if ($authorProfileUrl !== null): ?>
            <a href="<?= e($authorProfileUrl) ?>" class="feed-post__avatar-link" aria-hidden="true">
                <?= $view->renderPartial('layout/partials/user-avatar', [
                    'path'  => $story['author_avatar_path'] ?? null,
                    'size'  => 'md',
                    'class' => 'feed-post__avatar',
                    'alt'   => '',
                ]) ?>
            </a>
        <?php else: ?>
            <?= $view->renderPartial('layout/partials/user-avatar', [
                'path'  => $story['author_avatar_path'] ?? null,
                'size'  => 'md',
                'class' => 'feed-post__avatar',
                'alt'   => '',
            ]) ?>
        <?php endif; ?>
        <div class="feed-post__meta">
            <?php if ($authorProfileUrl !== null): ?>
                <a href="<?= e($authorProfileUrl) ?>" class="feed-post__author">@<?= e($authorName) ?></a>
            <?php else: ?>
                <span class="feed-post__author">@<?= e($authorName) ?></span>
            <?php endif; ?>
            <div class="feed-post__sub">
                <time datetime="<?= e(iso8601($publishedAt)) ?>"><?= e(time_ago($publishedAt)) ?></time>
                <?php if (!empty($story['category_name'])): ?>
                    · <a href="<?= e(url('/kategoria/' . $story['category_slug'])) ?>" class="feed-post__cat"><?= e($story['category_name']) ?></a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="feed-post__body">
        <h2 class="feed-post__title"><a href="<?= e($storyUrl) ?>"><?= e($story['title']) ?></a></h2>
        <p class="feed-post__text"><?= e($story['excerpt']) ?></p>
    </div>

    <footer class="feed-post__foot">
        <div class="feed-post__rating">
            <strong data-rating-avg="<?= e($storyId) ?>"><?= e(number_format((float)$story['rating_avg'], 2)) ?></strong>
            <div class="rate-widget rate-widget--compact <?= $alreadyRated ? 'disabled is-rated' : '' ?>"
                 data-story-id="<?= e($storyId) ?>"
                 data-csrf="<?= e($csrf->token()) ?>"
                 data-csrf-name="<?= e($csrf->tokenName()) ?>"
                 data-user-rating="<?= e((string)($userRating ?? '')) ?>"
                 role="group"
                 aria-label="Oceń historię">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <button type="button"
                            class="rate-star<?= ($userRating !== null && $i <= $userRating) ? ' selected' : '' ?>"
                            data-value="<?= $i ?>"
                            title="<?= $i ?>/5"
                            aria-label="<?= $i ?> na 5"
                            <?= $alreadyRated ? ' disabled' : '' ?>>&#9733;</button>
                <?php endfor; ?>
            </div>
            <span class="feed-post__rating-count">(<span data-rating-count="<?= e($storyId) ?>"><?= e($story['ratings_count']) ?></span>)</span>
        </div>
        <a href="<?= e($storyUrl) ?>" class="btn btn-accent btn-sm">Czytaj</a>
    </footer>
</article>
