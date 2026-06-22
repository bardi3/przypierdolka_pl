<?php
/**
 * Karta historii z interaktywnym ocenianiem.
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
$ratingAvg = (float)$story['rating_avg'];
$ratingCount = (int)$story['ratings_count'];
?>
<article class="story-card">
    <?php if (!empty($story['category_name'])): ?>
        <a href="<?= e(url('/kategoria/' . $story['category_slug'])) ?>" class="story-card__badge" rel="tag">
            <?= e($story['category_name']) ?>
        </a>
    <?php endif; ?>

    <h2 class="story-card__title">
        <a href="<?= e($storyUrl) ?>" rel="bookmark"><?= e($story['title']) ?></a>
    </h2>

    <p class="story-card__excerpt"><?= e($story['excerpt']) ?></p>

    <div class="story-card__actions">
        <div class="story-card__actions-col story-card__actions-col--rating">
            <div class="story-card__rating-line" aria-label="<?= e(rating_aria_label($ratingAvg, $ratingCount)) ?>">
                <strong class="story-card__rating-avg" data-rating-avg="<?= e($storyId) ?>">
                    <?= e(number_format($ratingAvg, 2)) ?>
                </strong>
                <div class="rate-widget rate-widget--compact <?= $alreadyRated ? 'disabled is-rated' : '' ?>"
                     data-story-id="<?= e($storyId) ?>"
                     data-csrf="<?= e($csrf->token()) ?>"
                     data-csrf-name="<?= e($csrf->tokenName()) ?>"
                     data-user-rating="<?= e((string)($userRating ?? '')) ?>"
                     role="group"
                     aria-label="Oceń historię «<?= e($story['title']) ?>» od 1 do 5 gwiazdek">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <button type="button"
                                class="rate-star<?= ($userRating !== null && $i <= $userRating) ? ' selected' : '' ?>"
                                data-value="<?= $i ?>"
                                title="<?= $i ?>/5"
                                aria-label="<?= $i ?> na 5"
                                <?= $alreadyRated ? ' disabled' : '' ?>>&#9733;</button>
                    <?php endfor; ?>
                </div>
                <span class="story-card__rating-count">(<span data-rating-count="<?= e($storyId) ?>"><?= e($story['ratings_count']) ?></span>)</span>
            </div>
            <div data-rating-feedback="<?= e($storyId) ?>" class="story-card__rating-feedback">
                <?= $alreadyRated ? 'Już oceniłeś' : '' ?>
            </div>
        </div>
        <div class="story-card__actions-col story-card__actions-col--read">
            <a href="<?= e($storyUrl) ?>" class="btn btn-accent btn-sm story-card__read-btn">Czytaj</a>
        </div>
    </div>

    <footer class="story-card__footer">
        <?php if ($authorProfileUrl !== null): ?>
            <a href="<?= e($authorProfileUrl) ?>" class="story-card__meta-item story-card__author">
                <?= $view->renderPartial('layout/partials/icon', ['name' => 'user']) ?>
                <span><?= e($authorName) ?></span>
            </a>
        <?php else: ?>
            <span class="story-card__meta-item story-card__author">
                <?= $view->renderPartial('layout/partials/icon', ['name' => 'user']) ?>
                <span><?= e($authorName) ?></span>
            </span>
        <?php endif; ?>

        <span class="story-card__meta-item story-card__time">
            <?= $view->renderPartial('layout/partials/icon', ['name' => 'calendar']) ?>
            <time datetime="<?= e(iso8601($publishedAt)) ?>"><?= e(time_ago($publishedAt)) ?></time>
        </span>

        <span class="story-card__meta-item story-card__views" aria-label="<?= e($story['views']) ?> wyświetleń">
            <?= $view->renderPartial('layout/partials/icon', ['name' => 'eye']) ?>
            <span><?= e($story['views']) ?></span>
        </span>
    </footer>
</article>
