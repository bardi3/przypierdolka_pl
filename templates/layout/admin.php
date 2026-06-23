<?php
/**
 * Layout panelu admina.
 * @var string $content
 * @var \App\Core\Seo|null $seo
 * @var \App\Core\Auth $auth
 * @var \App\Core\Csrf $csrf
 * @var string $admin_prefix
 * @var int $pendingCount
 * @var array $flashes
 */
$seo = $seo ?? null;
$flashes = $flashes ?? [];
$pendingCount = $pendingCount ?? 0;
$isAdmin = \App\Core\Permissions::isAdmin($auth->role());
$nav = [
    ['', 'Pulpit', 'bi-speedometer2', $isAdmin],
    ['stories', 'Historie', 'bi-card-text', true],
    ['stories/pending', 'Moderacja', 'bi-hourglass-split', true],
    ['categories', 'Kategorie', 'bi-tags', $isAdmin],
    ['users', 'Użytkownicy', 'bi-people', $isAdmin],
    ['settings', 'Ustawienia', 'bi-gear', $isAdmin],
    ['llms', 'llms.txt', 'bi-robot', $isAdmin],
];
$currentUri = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php if ($seo instanceof \App\Core\Seo): ?>
    <?= $seo->renderHead() ?>

    <?php else: ?>
    <title>Panel — przypierdolka.pl</title>
    <meta name="robots" content="noindex, nofollow">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preload" href="<?= e(asset('css/app.css')) ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>"></noscript>
    <link rel="preload" href="<?= e(asset('css/admin.css')) ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>"></noscript>
</head>
<body class="admin-body">
    <header class="admin-topbar navbar navbar-dark bg-dark">
        <div class="admin-topbar__inner container-fluid">
            <button type="button" class="admin-sidebar-toggle" id="adminSidebarToggle"
                    aria-controls="adminSidebar" aria-expanded="false" aria-label="Menu panelu">
                <i class="bi bi-list fs-4" aria-hidden="true"></i>
            </button>
            <a class="admin-brand" href="<?= e(url(ltrim($admin_prefix, '/'))) ?>">
                <i class="bi bi-shield-lock" aria-hidden="true"></i>
                <span class="admin-brand__short">Panel</span>
                <span class="admin-brand__full">przypierdolka.pl</span>
            </a>
            <div class="admin-topbar__actions">
                <a class="btn btn-sm btn-outline-light" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">
                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                    <span class="admin-topbar__label">Strona</span>
                </a>
                <form method="post" action="<?= e(url('/wyloguj')) ?>">
                    <?= $csrf->field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-warning">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                        <span class="admin-topbar__label">Wyloguj</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <div class="admin-layout">
        <button type="button" class="admin-overlay" id="adminOverlay" hidden aria-label="Zamknij menu"></button>

        <aside class="admin-sidebar" id="adminSidebar" aria-label="Nawigacja panelu">
            <nav class="admin-sidebar__nav">
                <ul class="nav flex-column gap-1">
                    <?php foreach ($nav as [$path, $label, $icon, $visible]): ?>
                        <?php if (!$visible) { continue; } ?>
                        <?php $href = $admin_prefix . ($path !== '' ? '/' . $path : ''); ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentUri === rtrim($href, '/') ? 'active' : '' ?>"
                               href="<?= e(url(ltrim($href, '/'))) ?>">
                                <i class="bi <?= e($icon) ?>" aria-hidden="true"></i>
                                <?= e($label) ?>
                                <?php if ($path === 'stories/pending' && $pendingCount > 0): ?>
                                    <span class="badge text-bg-warning"><?= e($pendingCount) ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </aside>

        <main class="admin-main">
            <div class="admin-main__inner">
                <?= $view->renderPartial('layout/partials/flash', ['flashes' => $flashes]) ?>
                <?= $content ?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= e(asset('js/story-cache-refresh.js')) ?>" defer></script>
    <script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</body>
</html>
