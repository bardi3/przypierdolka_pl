<?php
/**
 * Tablica społecznościowa — strona główna, rankingi, kategorie.
 *
 * @var array{items:array, total:int, page:int, pages:int} $listing
 * @var array<int, array<string, mixed>> $categories
 * @var array<int, int> $userRatings
 * @var string $feedType
 * @var string|null $feedSlug
 * @var array<int, array<string, mixed>> $trendingStories
 * @var array<string, mixed>|null $activeCategory
 * @var string $baseListUrl
 * @var string|null $rankingTitle
 * @var bool $isRanking
 * @var int|null $minRatings
 * @var string|null $sort
 * @var \App\Core\Auth $auth
 */
use App\Services\FeedService;

$activeCategory = $activeCategory ?? null;
$rankingTitle = $rankingTitle ?? null;
$isRanking = $isRanking ?? false;
$minRatings = $minRatings ?? null;
$userRatings = $userRatings ?? [];
$friendsCount = $friendsCount ?? null;
$breadcrumbs = $breadcrumbs ?? null;
$feedType = $feedType ?? FeedService::TYPE_NEWEST;
$feedSlug = $feedSlug ?? ($activeCategory !== null ? (string)$activeCategory['slug'] : null);
$trendingStories = $trendingStories ?? [];
$isHomepage = $isHomepage ?? false;

if (!isset($wallTitle)) {
    $wallTitle = match (true) {
        $activeCategory !== null && $isRanking => $rankingTitle ?? 'Top kategorii',
        $activeCategory !== null => 'Kategoria: ' . $activeCategory['name'],
        $rankingTitle !== null => $rankingTitle,
        default => 'Tablica społeczności',
    };
}

if (!isset($wallSubtitle)) {
    $wallSubtitle = match (true) {
        $feedType === FeedService::TYPE_FRIENDS
            => 'Historie opublikowane przez Twoich znajomych — oceń gwiazdkami bez wchodzenia w treść.',
        $isRanking && $minRatings !== null
            => ranking_lead((int)$minRatings, $sort ?? null, $activeCategory !== null ? (string)$activeCategory['name'] : null),
        default => 'Najświeższe historie od społeczności — oceń gwiazdkami bez wchodzenia w treść.',
    };
}

$feedListLabel = match (true) {
    $feedType === FeedService::TYPE_FRIENDS => 'Historie znajomych',
    $isRanking => $rankingTitle ?? 'Ranking historii',
    $activeCategory !== null => 'Historie w kategorii ' . ($activeCategory['name'] ?? ''),
    default => 'Najnowsze historie',
};

$feedTabs = [
    ['label' => 'Najnowsze', 'url' => '/', 'type' => FeedService::TYPE_NEWEST],
];
if ($auth->check()) {
    $feedTabs[] = ['label' => 'Moi znajomi', 'url' => '/tablica/znajomi', 'type' => FeedService::TYPE_FRIENDS];
}
$feedTabs = array_merge($feedTabs, [
    ['label' => 'Top tygodnia', 'url' => '/top/tydzien', 'type' => FeedService::TYPE_TOP_WEEK],
    ['label' => 'Top miesiąca', 'url' => '/top/miesiac', 'type' => FeedService::TYPE_TOP_MONTH],
    ['label' => 'Top wszystkich', 'url' => '/top/wszystkie', 'type' => FeedService::TYPE_TOP_ALL],
]);

if ($activeCategory !== null) {
    $catBase = '/kategoria/' . $activeCategory['slug'];
    $feedTabs = [
        ['label' => 'Najnowsze', 'url' => $catBase, 'type' => FeedService::TYPE_CATEGORY],
        ['label' => 'Top kategorii', 'url' => $catBase . '/top', 'type' => FeedService::TYPE_CATEGORY_TOP],
    ];
}

?>
<div class="social-wall">
    <?php if ($breadcrumbs !== null): ?>
        <?= $view->renderPartial('layout/partials/breadcrumb', ['items' => $breadcrumbs]) ?>
    <?php endif; ?>

    <header class="wall-header">
        <h1 class="wall-header__title" id="wall-heading"><?= e($wallTitle) ?></h1>
        <p class="wall-header__lead"><?= e($wallSubtitle) ?></p>
    </header>

    <?php if ($auth->check()): ?>
        <?= $view->renderPartial('home/_wall-composer', [
            'categories' => $categories,
            'csrf'       => $csrf,
        ]) ?>
    <?php else: ?>
        <div class="wall-composer wall-composer--guest">
            <p>
                <a href="<?= e(url('/logowanie')) ?>">Zaloguj się</a>
                lub <a href="<?= e(url('/rejestracja')) ?>">załóż konto</a>,
                aby dodać własną historię na tablicę.
            </p>
        </div>
    <?php endif; ?>

    <div class="social-profile__layout social-wall__layout">
        <section class="social-wall__main" aria-labelledby="wall-heading">
            <nav class="wall-tabs" aria-label="Filtr tablicy">
                <?php foreach ($feedTabs as $tab): ?>
                    <a href="<?= e(url(ltrim($tab['url'], '/'))) ?>"
                       class="wall-tabs__tab<?= $feedType === $tab['type'] ? ' is-active' : '' ?>">
                        <?= e($tab['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <?php if (empty($listing['items'])): ?>
                <?php if ($feedType === FeedService::TYPE_FRIENDS): ?>
                    <?= $view->renderPartial('layout/partials/alert', [
                        'type' => 'info',
                        'html' => ($friendsCount ?? 0) === 0
                            ? 'Nie masz jeszcze znajomych. <a href="' . e(url('/konto/znajomi')) . '">Zaproś kogoś</a> lub '
                                . '<a href="' . e(url('/')) . '">przeglądaj tablicę</a>.'
                            : 'Twoi znajomi jeszcze nic nie opublikowali. <a href="' . e(url('/')) . '">Zobacz najnowsze historie</a> lub '
                                . '<a href="' . e(url('/dodaj')) . '">dodaj własną</a>.',
                    ]) ?>
                <?php elseif ($isRanking): ?>
                    <?= $view->renderPartial('layout/partials/alert', [
                        'type' => 'info',
                        'html' => e(ranking_empty_lead((int)$minRatings))
                            . ' <a href="' . e(url('/')) . '">Zobacz najnowsze historie</a> lub '
                            . '<a href="' . e(url('/dodaj')) . '">dodaj własną</a>.',
                    ]) ?>
                <?php else: ?>
                    <?= $view->renderPartial('layout/partials/alert', [
                        'type' => 'info',
                        'html' => 'Brak historii do wyświetlenia. <a href="' . e(url('/dodaj')) . '">Dodaj pierwszą!</a>',
                    ]) ?>
                <?php endif; ?>
            <?php else: ?>
                <section class="social-wall__feed-section" aria-labelledby="wall-feed-label">
                    <h2 id="wall-feed-label" class="visually-hidden"><?= e($feedListLabel) ?></h2>
                    <div class="social-wall__feed"
                         data-feed-infinite
                         data-feed-type="<?= e($feedType) ?>"
                         data-feed-slug="<?= e((string)($feedSlug ?? '')) ?>"
                         data-feed-page="<?= e($listing['page']) ?>"
                         data-feed-pages="<?= e($listing['pages']) ?>"
                         role="feed"
                         aria-busy="false">
                        <div class="social-feed" data-feed-list>
                            <?= $view->renderPartial('feed/_list', [
                                'stories'     => $listing['items'],
                                'userRatings' => $userRatings,
                            ]) ?>
                        </div>
                        <?php if ($listing['page'] < $listing['pages']): ?>
                            <div class="feed-infinite-status" data-feed-status aria-live="polite">
                                <span class="feed-infinite-status__spinner" aria-hidden="true"></span>
                                <span class="feed-infinite-status__text">Ładowanie kolejnych historii…</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <noscript>
                    <?= $view->renderPartial('layout/partials/pagination', [
                        'page'    => $listing['page'],
                        'pages'   => $listing['pages'],
                        'baseUrl' => $baseListUrl,
                        'sort'    => null,
                    ]) ?>
                </noscript>
            <?php endif; ?>
        </section>

        <?= $view->renderPartial('layout/partials/wall-sidebar', get_defined_vars()) ?>
    </div>
</div>
