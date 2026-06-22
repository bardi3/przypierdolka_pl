<?php
/**
 * @var string $query
 * @var array{query:string, stories:array, users:array, friends:array, total:int} $results
 * @var \App\Core\Auth $auth
 */
$stories = $results['stories'] ?? [];
$users = $results['users'] ?? [];
$friends = $results['friends'] ?? [];
$total = (int)($results['total'] ?? 0);
?>
<div class="page-layout search-page">
    <div class="page-main">
        <header class="page-header">
            <h1 class="page-title">Szukaj</h1>
            <p class="page-subtitle">Historie, użytkownicy<?= $auth->check() ? ' i znajomi' : '' ?>.</p>
        </header>

        <form class="site-search site-search--page card" action="<?= e(url('/szukaj')) ?>" method="get" role="search">
            <div class="card-body site-search__form site-search__form--page">
                <label class="visually-hidden" for="search-page-q">Szukaj</label>
                <input type="search" id="search-page-q" name="q" class="site-search__input site-search__input--page form-control"
                       value="<?= e($query) ?>" placeholder="Wpisz frazę…" maxlength="80" autofocus>
                <button type="submit" class="btn btn-accent">Szukaj</button>
            </div>
        </form>

        <?php if ($query === ''): ?>
            <?= $view->renderPartial('layout/partials/alert', [
                'type' => 'info',
                'message' => 'Wpisz co najmniej 2 znaki, aby rozpocząć wyszukiwanie.',
            ]) ?>
        <?php elseif ($total === 0): ?>
            <?= $view->renderPartial('layout/partials/alert', [
                'type' => 'info',
                'html' => 'Brak wyników dla „' . e($query) . '”.',
            ]) ?>
        <?php else: ?>
            <?php if (!empty($stories)): ?>
                <section class="search-results card">
                    <div class="card-body">
                        <h2 class="h6">Historie (<?= e(count($stories)) ?>)</h2>
                        <ul class="search-results__list">
                            <?php foreach ($stories as $story): ?>
                                <li>
                                    <a href="<?= e(url('/historia/' . $story['slug'])) ?>" class="search-results__link">
                                        <strong><?= e($story['title']) ?></strong>
                                        <span><?= e($story['excerpt']) ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($users)): ?>
                <section class="search-results card">
                    <div class="card-body">
                        <h2 class="h6">Użytkownicy (<?= e(count($users)) ?>)</h2>
                        <ul class="search-results__list">
                            <?php foreach ($users as $user): ?>
                                <li>
                                    <a href="<?= e(url('/profil/' . $user['username'])) ?>" class="search-results__link">
                                        <strong>@<?= e($user['username']) ?></strong>
                                        <?php if (!empty($user['bio'])): ?>
                                            <span><?= e($user['bio']) ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($friends)): ?>
                <section class="search-results card">
                    <div class="card-body">
                        <h2 class="h6">Znajomi (<?= e(count($friends)) ?>)</h2>
                        <ul class="search-results__list">
                            <?php foreach ($friends as $friend): ?>
                                <li>
                                    <a href="<?= e(url('/profil/' . $friend['username'])) ?>" class="search-results__link">
                                        <strong>@<?= e($friend['username']) ?></strong>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?= $view->renderPartial('layout/partials/wall-sidebar', get_defined_vars()) ?>
</div>
