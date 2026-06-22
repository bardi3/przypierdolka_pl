<?php
/**
 * @var array<int, array<string, mixed>> $categories
 * @var array<int, array{name:string, url?:string}>|null $breadcrumbs
 */
$breadcrumbs = $breadcrumbs ?? [];
?>
<?= $view->renderPartial('layout/partials/breadcrumb', ['items' => $breadcrumbs]) ?>

<header class="page-header">
    <h1 class="page-title">Kategorie</h1>
    <p class="page-subtitle">Przeglądaj historie według tematu.</p>
</header>

<div class="cat-grid">
    <?php foreach ($categories as $cat): ?>
        <a href="<?= e(url('/kategoria/' . $cat['slug'])) ?>" class="story-card text-dark" style="text-decoration:none">
            <h2 class="story-card__title"><?= e($cat['name']) ?></h2>
            <?php if (!empty($cat['description'])): ?>
                <p class="story-card__excerpt"><?= e($cat['description']) ?></p>
            <?php endif; ?>
            <span class="story-card__badge"><?= e($cat['stories_count'] ?? 0) ?> historii</span>
        </a>
    <?php endforeach; ?>
</div>
