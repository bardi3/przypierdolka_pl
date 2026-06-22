<?php
/**
 * @var array<string, mixed> $profile
 * @var array{stories_count:int, views_total:int, ratings_total:int, rating_avg:float} $stats
 * @var int $friendsCount
 * @var array<int, array<string, mixed>> $friendsPreview
 * @var array<int, array<string, mixed>> $feed
 * @var array<int, int> $userRatings
 * @var int $page
 * @var int $pages
 * @var int $total
 * @var array<int, array{name:string, url?:string}> $breadcrumbs
 * @var string $friendState
 * @var int|null $friendshipId
 * @var bool $isOwner
 * @var bool $canViewStories
 * @var bool $canViewFriends
 */
$userRatings = $userRatings ?? [];
$memberSince = date('Y-m-d', strtotime((string)$profile['created_at']));
$profileUrl = url('/profil/' . $profile['username']);
?>
<div class="social-profile">
    <?= $view->renderPartial('layout/partials/breadcrumb', ['items' => $breadcrumbs]) ?>

    <?= $view->renderPartial('profile/_mod-bar', get_defined_vars()) ?>

    <div class="social-profile__cover" aria-hidden="true"></div>

    <div class="social-profile__head">
        <?php if ($isOwner): ?>
            <?= $view->renderPartial('profile/_avatar-menu', ['profile' => $profile]) ?>
        <?php else: ?>
            <?= $view->renderPartial('layout/partials/user-avatar', [
                'path'  => $profile['avatar_path'] ?? null,
                'size'  => 'xl',
                'alt'   => 'Awatar @' . ($profile['username'] ?? ''),
                'class' => 'social-profile__avatar',
            ]) ?>
        <?php endif; ?>

        <div class="social-profile__info">
            <h1 class="social-profile__name">@<?= e($profile['username']) ?></h1>
            <?php if (!empty($profile['bio'])): ?>
                <p class="social-profile__bio"><?= e($profile['bio']) ?></p>
            <?php endif; ?>
            <p class="social-profile__since">Na serwisie od <?= e($memberSince) ?></p>

            <ul class="social-profile__stats">
                <li><strong><?= e($stats['stories_count']) ?></strong> historii</li>
                <li><strong><?= e($friendsCount) ?></strong> znajomych</li>
                <li><strong><?= e(number_format($stats['views_total'], 0, ',', ' ')) ?></strong> wyświetleń</li>
                <li><strong><?= e(number_format($stats['rating_avg'], 2)) ?></strong> śr. ocena</li>
            </ul>
        </div>

        <div class="social-profile__actions">
            <?= $view->renderPartial('profile/_friend-actions', [
                'friendState'      => $friendState,
                'profileUsername'  => $profile['username'],
                'isOwner'          => $isOwner,
            ]) ?>
        </div>
    </div>

    <div class="social-profile__layout">
        <main class="social-profile__main">
            <nav class="social-profile__tabs" aria-label="Sekcje profilu">
                <span class="social-profile__tab is-active">Tablica</span>
            </nav>

            <?php if (!$canViewStories): ?>
                <?= $view->renderPartial('layout/partials/alert', [
                    'type'    => 'info',
                    'message' => 'Historie tego użytkownika są widoczne tylko dla znajomych. Wyślij zaproszenie, aby je zobaczyć.',
                ]) ?>
            <?php elseif (empty($feed)): ?>
                <?= $view->renderPartial('layout/partials/alert', [
                    'type'    => 'info',
                    'message' => $isOwner
                        ? 'Twoja tablica jest pusta. Dodaj pierwszą historię!'
                        : 'Ten użytkownik nie ma jeszcze opublikowanych historii.',
                ]) ?>
                <?php if ($isOwner): ?>
                    <p class="text-center"><a href="<?= e(url('/dodaj')) ?>" class="btn btn-accent">+ Dodaj historię</a></p>
                <?php endif; ?>
            <?php else: ?>
                <div class="social-feed">
                    <?php foreach ($feed as $story): ?>
                        <?= $view->renderPartial('profile/_feed-item', [
                            'story'      => $story,
                            'profile'    => $profile,
                            'userRating' => $userRatings[(int)$story['id']] ?? null,
                        ]) ?>
                    <?php endforeach; ?>
                </div>

                <?= $view->renderPartial('layout/partials/pagination', [
                    'page'    => $page,
                    'pages'   => $pages,
                    'baseUrl' => '/profil/' . $profile['username'],
                    'sort'    => null,
                ]) ?>
            <?php endif; ?>
        </main>

        <aside class="social-profile__aside">
            <?php if ($canViewFriends): ?>
                <section class="sidebar-panel">
                    <h2 class="sidebar-panel__title">Znajomi</h2>
                    <?php if (empty($friendsPreview)): ?>
                        <p class="text-muted small mb-0">Brak znajomych do wyświetlenia.</p>
                    <?php else: ?>
                        <ul class="social-friends-grid">
                            <?php foreach ($friendsPreview as $friend): ?>
                                <li>
                                    <a href="<?= e(url('/profil/' . $friend['username'])) ?>" class="social-friends-grid__item">
                                        <?= $view->renderPartial('layout/partials/user-avatar', [
                                            'path'  => $friend['avatar_path'] ?? null,
                                            'size'  => 'lg',
                                            'class' => 'social-friends-grid__avatar',
                                            'alt'   => '',
                                        ]) ?>
                                        <span class="social-friends-grid__name"><?= e($friend['username']) ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if ($isOwner): ?>
                            <a href="<?= e(url('/konto/znajomi')) ?>" class="btn btn-outline btn-sm btn-block">Zarządzaj znajomymi</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
            <?php else: ?>
                <section class="sidebar-panel">
                    <h2 class="sidebar-panel__title">Znajomi</h2>
                    <p class="text-muted small mb-0">Lista znajomych jest ukryta przez ustawienia prywatności.</p>
                </section>
            <?php endif; ?>

            <?php if ($isOwner): ?>
                <section class="sidebar-panel sidebar-panel--cta">
                    <p class="sidebar-panel__lead">Co słychać?</p>
                    <a class="btn btn-accent btn-block" href="<?= e(url('/dodaj')) ?>">+ Opublikuj historię</a>
                </section>
            <?php endif; ?>
        </aside>
    </div>
</div>
