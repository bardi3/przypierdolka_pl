<?php
/**
 * @var array<string, mixed> $story
 * @var string $shareUrl
 * @var array{src:string, srcset:string, sizes:string, width:int, height:int} $shareImage
 * @var bool $alreadyRated
 * @var bool $canModerate
 * @var bool $canEditStory
 * @var array<int, array<string, mixed>> $categories
 * @var int|null $userRating
 * @var bool $isPreview
 * @var array{slug:string, title:string}|null $prevStory
 * @var array{slug:string, title:string}|null $nextStory
 * @var \App\Core\Csrf $csrf
 */
$isPreview = $isPreview ?? false;
$prevStory = $prevStory ?? null;
$nextStory = $nextStory ?? null;
$encodedUrl = rawurlencode($shareUrl);
$encodedTitle = rawurlencode((string)$story['title']);
$prevUrl = $prevStory !== null ? url('/historia/' . $prevStory['slug']) : '';
$nextUrl = $nextStory !== null ? url('/historia/' . $nextStory['slug']) : '';
$randomUrl = url('/historia/losowa?exclude=' . (int)$story['id']);
$authorName = (string)($story['author_username'] ?? $story['author_name'] ?? 'Anonim');
$authorUsername = $story['author_username'] ?? null;
$authorProfileUrl = ($authorUsername !== null && $authorUsername !== '')
    ? url('/profil/' . $authorUsername)
    : null;
$publishedAt = $story['published_at'] ?? $story['created_at'];
$ratingAvg = (float)$story['rating_avg'];
$ratingCount = (int)$story['ratings_count'];
$ratingLabel = rating_aria_label($ratingAvg, $ratingCount);
$breadcrumbs = $breadcrumbs ?? [];
$canEditStory = $canEditStory ?? false;
$canModerate = $canModerate ?? false;
$categories = $categories ?? [];
?>
<div class="page-layout story-show-page">
    <div class="page-main">
<div class="story-article">
    <?= $view->renderPartial('layout/partials/breadcrumb', ['items' => $breadcrumbs]) ?>

    <?= $view->renderPartial('stories/_mod-bar', [
        'story'       => $story,
        'canModerate' => $canModerate ?? false,
    ]) ?>

    <?php if ($isPreview): ?>
        <?= $view->renderPartial('layout/partials/alert', [
            'type'        => 'warning',
            'dismissible' => true,
            'html'        => '<strong>Podgląd moderacji.</strong> Status: <em>' . e($story['status']) . '</em>. '
                . 'Użyj przycisku <strong>Edytuj</strong> na belce moderacji.',
        ]) ?>
    <?php endif; ?>

    <div class="story-article__shell"
         <?php if (!$isPreview && ($prevStory !== null || $nextStory !== null)): ?>
         data-story-nav
         <?php if ($prevUrl !== ''): ?>data-nav-prev="<?= e($prevUrl) ?>"<?php endif; ?>
         <?php if ($nextUrl !== ''): ?>data-nav-next="<?= e($nextUrl) ?>"<?php endif; ?>
         <?php endif; ?>>

        <?php if (!$isPreview && $prevStory !== null): ?>
            <a href="<?= e($prevUrl) ?>" class="story-article__nav story-article__nav--prev"
               aria-label="Nowsza historia: <?= e($prevStory['title']) ?>" title="<?= e($prevStory['title']) ?>">
                <span aria-hidden="true">&#8249;</span>
            </a>
        <?php endif; ?>

        <article class="story-article__panel"
                 <?php if ($canEditStory): ?>
                 data-story-inline-edit
                 data-story-id="<?= e($story['id']) ?>"
                 data-story-slug="<?= e($story['slug']) ?>"
                 data-story-edit-url="<?= e(url('/ajax/story/' . (int)$story['id'] . '/edit')) ?>"
                 <?php endif; ?>>

            <div data-story-view>
            <header class="story-article__header">
                <div class="story-article__title-row">
                    <h1 class="story-article__title" data-story-title><?= e($story['title']) ?></h1>
                    <?php if ($canEditStory && !$canModerate): ?>
                        <button type="button"
                                class="btn btn-ghost btn-sm story-inline-edit-trigger"
                                data-story-inline-edit-toggle
                                aria-pressed="false">
                            Edytuj
                        </button>
                    <?php endif; ?>
                </div>

                <div class="story-article__meta">
                    <?php if ($authorProfileUrl !== null): ?>
                        <a href="<?= e($authorProfileUrl) ?>" class="story-article__meta-link" rel="author">
                            <?= $view->renderPartial('layout/partials/icon', ['name' => 'user']) ?>
                            <?= e($authorName) ?>
                        </a>
                    <?php else: ?>
                        <span class="story-article__meta-item">
                            <?= $view->renderPartial('layout/partials/icon', ['name' => 'user']) ?>
                            <?= e($authorName) ?>
                        </span>
                    <?php endif; ?>
                    <time class="story-article__meta-item" datetime="<?= e(iso8601($publishedAt)) ?>">
                        <?= $view->renderPartial('layout/partials/icon', ['name' => 'calendar']) ?>
                        <?= e(time_ago($publishedAt)) ?>
                    </time>
                    <span class="story-article__meta-item">
                        <?= $view->renderPartial('layout/partials/icon', ['name' => 'eye']) ?>
                        <?= e($story['views']) ?> wyświetleń
                    </span>
                    <?php if ($ratingCount > 0): ?>
                        <p class="story-article__rating" aria-label="<?= e($ratingLabel) ?>">
                            <span class="visually-hidden">Ocena:</span>
                            <?= star_rating($ratingAvg) ?>
                            <strong data-rating-avg="<?= e($story['id']) ?>"><?= e(number_format($ratingAvg, 2)) ?></strong>
                            (<span data-rating-count="<?= e($story['id']) ?>"><?= e($ratingCount) ?></span> ocen)
                        </p>
                    <?php else: ?>
                        <span class="story-article__rating story-article__rating--empty">Brak ocen</span>
                    <?php endif; ?>
                </div>
            </header>

            <div class="story-content" data-story-content><?= e($story['content']) ?></div>

            <div class="story-share-gallery" data-story-gallery>
                <img src="<?= e($shareImage['src']) ?>"
                     srcset="<?= e($shareImage['srcset']) ?>"
                     sizes="<?= e($shareImage['sizes']) ?>"
                     width="<?= e($shareImage['width']) ?>"
                     height="<?= e($shareImage['height']) ?>"
                     alt="<?= e($story['title']) ?>"
                     class="story-share-gallery__img"
                     data-story-share-image
                     loading="lazy"
                     decoding="async">
            </div>

            <?php if (!$isPreview): ?>
            <div class="story-engage" data-story-engage>
                <section class="story-engage__col story-engage__col--rating" aria-labelledby="story-rating-heading">
                    <h2 class="story-section__title" id="story-rating-heading">Oceń tę historię</h2>
                    <p class="visually-hidden" id="story-rating-desc">Wybierz od 1 do 5 gwiazdek. Ocena wpływa na średnią historii.</p>
                    <div class="rate-widget <?= $alreadyRated ? 'disabled is-rated' : '' ?>"
                         data-story-id="<?= e($story['id']) ?>"
                         data-csrf="<?= e($csrf->token()) ?>"
                         data-csrf-name="<?= e($csrf->tokenName()) ?>"
                         data-user-rating="<?= e((string)($userRating ?? '')) ?>"
                         role="group"
                         aria-label="Oceń historię od 1 do 5 gwiazdek"
                         aria-describedby="story-rating-desc">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <button type="button"
                                    class="rate-star<?= ($userRating !== null && $i <= $userRating) ? ' selected' : '' ?>"
                                    data-value="<?= $i ?>"
                                    title="<?= $i ?>/5"
                                    aria-label="<?= $i ?> na 5 gwiazdek"
                                    <?= $alreadyRated ? ' disabled' : '' ?>>&#9733;</button>
                        <?php endfor; ?>
                    </div>
                    <div data-rating-feedback="<?= e($story['id']) ?>" class="story-card__rating-feedback">
                        <?= $alreadyRated ? 'Już oceniłeś tę historię.' : '' ?>
                    </div>
                </section>

                <section class="story-engage__col story-engage__col--share" aria-labelledby="story-share-heading">
                    <h2 class="story-section__title" id="story-share-heading">Udostępnij</h2>
                    <div class="share-buttons">
                        <a class="btn btn-sm btn-primary" target="_blank" rel="noopener noreferrer"
                           href="https://www.facebook.com/sharer/sharer.php?u=<?= $encodedUrl ?>">Facebook</a>
                        <a class="btn btn-sm btn-dark" target="_blank" rel="noopener noreferrer"
                           href="https://twitter.com/intent/tweet?url=<?= $encodedUrl ?>&text=<?= $encodedTitle ?>">X</a>
                        <a class="btn btn-sm btn-success" target="_blank" rel="noopener noreferrer"
                           href="https://api.whatsapp.com/send?text=<?= $encodedTitle ?>%20<?= $encodedUrl ?>">WhatsApp</a>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-share-copy="<?= e($shareUrl) ?>">Kopiuj link</button>
                    </div>
                </section>
            </div>
            <?php endif; ?>
            </div>

            <?php if ($canEditStory): ?>
                <?= $view->renderPartial('stories/_inline-edit-form', [
                    'story'       => $story,
                    'categories'  => $categories,
                    'canModerate' => $canModerate,
                    'csrf'        => $csrf,
                ]) ?>
            <?php endif; ?>
        </article>

        <?php if (!$isPreview && $nextStory !== null): ?>
            <a href="<?= e($nextUrl) ?>" class="story-article__nav story-article__nav--next"
               aria-label="Starsza historia: <?= e($nextStory['title']) ?>" title="<?= e($nextStory['title']) ?>">
                <span aria-hidden="true">&#8250;</span>
            </a>
        <?php endif; ?>
    </div>

    <?php if (!$isPreview): ?>
        <div class="story-footer-actions">
            <a href="<?= e(url('/')) ?>" class="btn btn-outline">← Wróć na główną</a>
            <a href="<?= e($randomUrl) ?>" class="btn btn-accent">Losowa przypierdolka</a>
        </div>
    <?php else: ?>
        <p class="text-center mt-4">
            <a href="<?= e(url('/')) ?>" class="btn btn-outline">← Wróć na główną</a>
        </p>
    <?php endif; ?>
</div>
    </div>
    <?= $view->renderPartial('layout/partials/wall-sidebar', get_defined_vars()) ?>
</div>
