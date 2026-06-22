<?php
/**
 * @var array<int, array<string, mixed>> $items
 * @var int $page
 * @var int $pages
 * @var string $accountNav
 * @var \App\Core\Csrf $csrf
 */
$statusLabels = [
    'published' => ['Opublikowana', 'status-published'],
    'pending'   => ['Oczekuje', 'status-pending'],
    'rejected'  => ['Odrzucona', 'status-rejected'],
];
?>
<?= $view->renderPartial('account/_page-open', ['accountNav' => $accountNav]) ?>

        <header class="page-header">
            <h1 class="page-title">Moje historie</h1>
            <p class="page-subtitle">Przeglądaj dodane historie. Oczekujące na moderację możesz cofnąć.</p>
        </header>

        <?php if (empty($items)): ?>
            <?= $view->renderPartial('layout/partials/alert', [
                'type' => 'info',
                'html' => 'Nie dodałeś jeszcze żadnej historii. <a href="' . e(url('/dodaj')) . '">Dodaj pierwszą</a>',
            ]) ?>
        <?php else: ?>
            <div class="account-stories">
                <?php foreach ($items as $story): ?>
                    <?php
                    $status = (string)($story['status'] ?? 'pending');
                    [$statusText, $statusClass] = $statusLabels[$status] ?? ['Nieznany', ''];
                    ?>
                    <article class="account-story card">
                        <div class="card-body">
                            <div class="account-story__head">
                                <h2 class="account-story__title">
                                    <?php if ($status === 'published'): ?>
                                        <a href="<?= e(url('/historia/' . $story['slug'])) ?>"><?= e($story['title']) ?></a>
                                    <?php else: ?>
                                        <?= e($story['title']) ?>
                                    <?php endif; ?>
                                </h2>
                                <span class="account-story__status <?= e($statusClass) ?>"><?= e($statusText) ?></span>
                            </div>

                            <p class="account-story__excerpt"><?= e($story['excerpt']) ?></p>

                            <div class="account-story__meta">
                                <?php if (!empty($story['category_name'])): ?>
                                    <span><?= e($story['category_name']) ?></span>
                                    <span>·</span>
                                <?php endif; ?>
                                <span><?= e(time_ago($story['created_at'])) ?></span>
                                <?php if ($status === 'published'): ?>
                                    <span>·</span>
                                    <span><?= e(number_format((float)$story['rating_avg'], 2)) ?> ★ (<?= e($story['ratings_count']) ?>)</span>
                                    <span>·</span>
                                    <span><?= e($story['views']) ?> wyśw.</span>
                                <?php endif; ?>
                            </div>

                            <?php if ($status === 'pending'): ?>
                                <form method="post"
                                      action="<?= e(url('/konto/historie/cofnij/' . $story['id'])) ?>"
                                      class="account-story__actions"
                                      onsubmit="return confirm('Cofnąć tę historię? Zostanie usunięta z moderacji.');">
                                    <?= $csrf->field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline">Cofnij z moderacji</button>
                                </form>
                            <?php elseif ($status === 'rejected'): ?>
                                <p class="text-muted small mb-0">Historia została odrzucona przez moderatora.</p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?= $view->renderPartial('layout/partials/pagination', [
                'page'    => $page,
                'pages'   => $pages,
                'baseUrl' => '/konto/historie',
                'sort'    => null,
            ]) ?>
        <?php endif; ?>

<?= $view->renderPartial('account/_page-close', get_defined_vars()) ?>
