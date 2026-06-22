<?php
/**
 * Boczny panel tablicy — rankingi, kategorie, trending.
 *
 * @var array<int, array<string, mixed>> $categories
 * @var array<int, array<string, mixed>> $trendingStories
 * @var string|null $feedType
 * @var array<string, mixed>|null $activeCategory
 * @var bool $isRanking
 * @var string|null $sidebarActive  add|account
 * @var \App\Core\Auth $auth
 */
use App\Services\FeedService;

$trendingStories = $trendingStories ?? [];
$categories = $categories ?? [];
$feedType = $feedType ?? null;
$activeCategory = $activeCategory ?? null;
$isRanking = $isRanking ?? false;
$sidebarActive = $sidebarActive ?? null;
$hideAddLinks = $sidebarActive === 'add';

$rankLinks = [
    ['label' => 'Top tygodnia', 'url' => '/top/tydzien', 'type' => FeedService::TYPE_TOP_WEEK],
    ['label' => 'Top miesiąca', 'url' => '/top/miesiac', 'type' => FeedService::TYPE_TOP_MONTH],
    ['label' => 'Top wszystkich', 'url' => '/top/wszystkie', 'type' => FeedService::TYPE_TOP_ALL],
];
if ($activeCategory !== null) {
    $rankLinks[] = [
        'label' => 'Top: ' . $activeCategory['name'],
        'url' => '/kategoria/' . $activeCategory['slug'] . '/top',
        'type' => FeedService::TYPE_CATEGORY_TOP,
    ];
}
?>
<aside class="page-sidebar social-wall__sidebar">
    <?php if ($auth->check()): ?>
        <section class="sidebar-panel">
            <h2 class="sidebar-panel__title">Twoje konto</h2>
            <nav class="sidebar-nav" aria-label="Konto">
                <?php $me = $auth->user(); ?>
                <?php if ($me !== null && !empty($me['username'])): ?>
                    <a href="<?= e(url('/profil/' . $me['username'])) ?>">Mój profil</a>
                <?php endif; ?>
                <a href="<?= e(url('/konto/znajomi')) ?>">Znajomi</a>
                <?php if (!$hideAddLinks): ?>
                    <a href="<?= e(url('/dodaj')) ?>">+ Dodaj historię</a>
                <?php endif; ?>
            </nav>
        </section>
    <?php endif; ?>

    <?php if (!empty($trendingStories)): ?>
        <section class="sidebar-panel">
            <h2 class="sidebar-panel__title">Na fali tygodnia</h2>
            <ol class="sidebar-trending">
                <?php foreach ($trendingStories as $i => $story): ?>
                    <li class="sidebar-trending__item">
                        <span class="sidebar-trending__rank"><?= e($i + 1) ?></span>
                        <div class="sidebar-trending__body">
                            <a href="<?= e(url('/historia/' . $story['slug'])) ?>" class="sidebar-trending__title">
                                <?= e($story['title']) ?>
                            </a>
                            <span class="sidebar-trending__meta">
                                <?= e(number_format((float)$story['rating_avg'], 2)) ?> ★
                                · <?= e($story['ratings_count']) ?> ocen
                            </span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>
    <?php endif; ?>

    <section class="sidebar-panel">
        <h2 class="sidebar-panel__title">Rankingi</h2>
        <nav class="sidebar-nav" aria-label="Rankingi">
            <?php foreach ($rankLinks as $link): ?>
                <a href="<?= e(url(ltrim($link['url'], '/'))) ?>"
                   class="<?= ($feedType !== null && $feedType === $link['type']) ? 'is-active' : '' ?>">
                    <?= e($link['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </section>

    <section class="sidebar-panel">
        <h2 class="sidebar-panel__title">Kategorie</h2>
        <nav class="sidebar-nav sidebar-nav--cats" aria-label="Kategorie">
            <?php foreach ($categories as $cat): ?>
                <a href="<?= e(url('/kategoria/' . $cat['slug'])) ?>"
                   class="<?= ($activeCategory !== null && (int)$activeCategory['id'] === (int)$cat['id'] && !$isRanking) ? 'is-active' : '' ?>">
                    <span><?= e($cat['name']) ?></span>
                    <span class="sidebar-nav__count"><?= e($cat['stories_count'] ?? 0) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </section>

    <?php if (!$hideAddLinks): ?>
    <section class="sidebar-panel sidebar-panel--cta">
        <p class="sidebar-panel__lead">Masz dobrą historię?</p>
        <a class="btn btn-accent btn-block" href="<?= e(url('/dodaj')) ?>">+ Dodaj historię</a>
    </section>
    <?php endif; ?>
</aside>
