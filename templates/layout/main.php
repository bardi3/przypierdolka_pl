<?php
/**
 * @var string $content
 * @var \App\Core\Seo|null $seo
 * @var \App\Core\Auth $auth
 * @var \App\Core\Csrf $csrf
 * @var array $flashes
 */
$seo = $seo ?? null;
$flashes = $flashes ?? [];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <?php if ($seo instanceof \App\Core\Seo): ?>
    <?= $seo->renderHead() ?>

    <?php else: ?>
    <title><?= e($app_name) ?></title>
    <?php endif; ?>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body data-ajax-rate-url="<?= e(url('/ajax/rate')) ?>"
      data-ajax-feed-url="<?= e(url('/ajax/feed')) ?>"
      data-ajax-search-url="<?= e(url('/ajax/search')) ?>"
      data-ajax-story-url="<?= e(url('/ajax/story')) ?>">
    <?= $view->renderPartial('layout/partials/navbar', get_defined_vars()) ?>

    <main id="main-content" class="site-main">
        <div class="container">
            <?= $view->renderPartial('layout/partials/flash', ['flashes' => $flashes]) ?>
            <?= $content ?>
        </div>
    </main>

    <?= $view->renderPartial('layout/partials/footer', get_defined_vars()) ?>

    <script src="<?= e(asset('js/story-cache-refresh.js')) ?>" defer></script>
    <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
