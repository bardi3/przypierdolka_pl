<?php
/**
 * @var \App\Core\Auth $auth
 * @var \App\Core\Csrf $csrf
 * @var string $app_name
 * @var string $admin_prefix
 * @var int $pending_friend_invites
 */
$currentPath = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/') ?: '/';
$isActive = static function (string $path) use ($currentPath): string {
    $path = rtrim($path, '/') ?: '/';
    if ($path === '/') {
        return $currentPath === '/' ? ' is-active' : '';
    }
    return str_starts_with($currentPath, $path) ? ' is-active' : '';
};
$pending_friend_invites = (int)($pending_friend_invites ?? 0);
?>
<header class="site-header">
    <div class="container site-header__inner">
        <a class="site-logo" href="<?= e(url('/')) ?>" aria-label="<?= e($app_name) ?> — strona główna">
            przypierdolka<span class="site-logo__accent">.pl</span>
        </a>

        <?= $view->renderPartial('layout/partials/search') ?>

        <button type="button" class="nav-toggle" aria-controls="mainNav" aria-expanded="false" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>

        <nav id="mainNav" class="site-nav" aria-label="Główne menu">
            <?php
            /** @var list<array{label:string, url:string, match?:list<string>}> $headerLinks */
            $headerLinks = [];
            ?>
            <ul class="site-nav__list" aria-label="Nawigacja">
                <?php foreach ($headerLinks as $link): ?>
                    <?php
                    $active = $isActive($link['url']);
                    if ($active === '' && !empty($link['match'])) {
                        foreach ($link['match'] as $prefix) {
                            if (str_starts_with($currentPath, $prefix)) {
                                $active = ' is-active';
                                break;
                            }
                        }
                    }
                    ?>
                    <li>
                        <a class="site-nav__link<?= $active ?>"
                           href="<?= e(url(ltrim($link['url'], '/'))) ?>">
                            <?= e($link['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="site-nav__actions">
                <a class="btn btn-accent btn-sm nav-btn" href="<?= e(url('/dodaj')) ?>">+ Dodaj historię</a>
                <?php if ($auth->check()): ?>
                    <?php $u = $auth->user(); ?>
                    <?php $username = (string)($u['username'] ?? ''); ?>
                    <?php if ($username !== ''): ?>
                        <a class="btn btn-ghost btn-sm nav-btn nav-btn--profile<?= $isActive('/profil/' . $username) ?>"
                           href="<?= e(url('/profil/' . $username)) ?>"
                           title="Mój profil"
                           aria-label="Mój profil — @<?= e($username) ?>">
                            <?= $view->renderPartial('layout/partials/user-avatar', [
                                'path'  => $u['avatar_path'] ?? null,
                                'size'  => 'sm',
                                'class' => 'nav-profile__avatar',
                                'alt'   => '',
                            ]) ?>
                            <span class="nav-profile__nick">@<?= e($username) ?></span>
                        </a>
                    <?php endif; ?>
                    <div class="site-nav__user-bar">
                        <a href="<?= e(url('/konto/znajomi')) ?>"
                           class="btn btn-ghost btn-sm nav-btn nav-btn--with-icon<?= $isActive('/konto/znajomi') ?>"
                           title="Znajomi"
                           aria-label="<?= $pending_friend_invites > 0 ? 'Znajomi — masz nowe zaproszenia' : 'Znajomi' ?>">
                            <?= $view->renderPartial('layout/partials/icon', ['name' => 'users']) ?>
                            <span>Znajomi</span>
                            <?php if ($pending_friend_invites > 0): ?>
                                <span class="nav-badge nav-badge--inline" aria-hidden="true">nowe</span>
                            <?php endif; ?>
                        </a>

                        <?php if (\App\Core\Permissions::canModerate($auth->role())): ?>
                            <a class="btn btn-ghost btn-sm nav-btn<?= $isActive($admin_prefix) ?>"
                               href="<?= e(url(ltrim($admin_prefix, '/'))) ?>">Panel</a>
                        <?php endif; ?>

                        <form method="post" action="<?= e(url('/wyloguj')) ?>" class="site-nav__logout-form">
                            <?= $csrf->field() ?>
                            <button type="submit"
                                    class="btn btn-ghost btn-sm nav-btn nav-btn--with-icon nav-btn--logout"
                                    title="Wyloguj się"
                                    aria-label="Wyloguj się">
                                <?= $view->renderPartial('layout/partials/icon', ['name' => 'logout']) ?>
                                <span class="nav-btn__label">Wyloguj się</span>
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <a class="btn btn-ghost btn-sm nav-btn" href="<?= e(url('/logowanie')) ?>">Zaloguj</a>
                    <a class="btn btn-outline btn-sm nav-btn" href="<?= e(url('/rejestracja')) ?>">Rejestracja</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
    <?= $view->renderPartial('layout/partials/subnav') ?>
</header>
