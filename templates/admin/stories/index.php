<?php
/**
 * @var string $title
 * @var array<int, array<string, mixed>> $items
 * @var string|null $status
 * @var int $page
 * @var int $pages
 * @var string $admin_prefix
 * @var \App\Core\Csrf $csrf
 */
$adminUrl = static fn (string $p): string => url(ltrim($admin_prefix . '/' . ltrim($p, '/'), '/'));
?>
<div class="admin-page-head">
    <h1 class="h3"><?= e($title) ?></h1>
    <div class="admin-filter-bar">
        <a class="btn btn-sm <?= $status === null ? 'btn-dark' : 'btn-outline-dark' ?>" href="<?= e($adminUrl('stories')) ?>">Wszystkie</a>
        <a class="btn btn-sm <?= $status === 'published' ? 'btn-dark' : 'btn-outline-dark' ?>" href="<?= e($adminUrl('stories')) ?>?status=published">Opublikowane</a>
        <a class="btn btn-sm <?= $status === 'pending' ? 'btn-dark' : 'btn-outline-dark' ?>" href="<?= e($adminUrl('stories/pending')) ?>">Do moderacji</a>
        <a class="btn btn-sm <?= $status === 'rejected' ? 'btn-dark' : 'btn-outline-dark' ?>" href="<?= e($adminUrl('stories')) ?>?status=rejected">Odrzucone</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>Tytuł</th><th>Kategoria</th><th>Status</th><th>Oceny</th><th>Autor</th><th class="text-end">Akcje</th></tr>
            </thead>
            <tbody>
            <?php foreach ($items as $s): ?>
                <tr>
                    <td>
                        <?php
                        $storyHref = $s['status'] === 'published'
                            ? url('/historia/' . $s['slug'])
                            : $adminUrl('stories/edit/' . $s['id']);
                        ?>
                        <a href="<?= e($storyHref) ?>" <?= $s['status'] === 'published' ? 'target="_blank"' : '' ?> class="text-decoration-none fw-semibold">
                            <?= e(mb_strimwidth((string)$s['title'], 0, 45, '…')) ?>
                        </a>
                        <?php if ($s['status'] === 'pending'): ?>
                            <span class="badge text-bg-warning ms-1">moderacja</span>
                        <?php elseif ($s['status'] === 'rejected'): ?>
                            <span class="badge text-bg-danger ms-1">odrzucona</span>
                        <?php endif; ?>
                    </td>
                    <td><small><?= e($s['category_name'] ?? '—') ?></small></td>
                    <td><?= $view->renderPartial('admin/stories/_status', ['status' => $s['status']]) ?></td>
                    <td><small><?= e(number_format((float)$s['rating_avg'], 2)) ?> (<?= e($s['ratings_count']) ?>)</small></td>
                    <td><small><?= e($s['author_username'] ?? $s['author_name'] ?? 'Anonim') ?></small></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <?php if ($s['status'] !== 'published'): ?>
                                <form method="post" action="<?= e($adminUrl('stories/approve/' . $s['id'])) ?>">
                                    <?= $csrf->field() ?>
                                    <button class="btn btn-success" title="Akceptuj"><i class="bi bi-check-lg"></i></button>
                                </form>
                            <?php endif; ?>
                            <?php if ($s['status'] !== 'rejected'): ?>
                                <form method="post" action="<?= e($adminUrl('stories/reject/' . $s['id'])) ?>">
                                    <?= $csrf->field() ?>
                                    <button class="btn btn-outline-danger" title="Odrzuć"><i class="bi bi-x-lg"></i></button>
                                </form>
                            <?php endif; ?>
                            <a class="btn btn-outline-secondary" href="<?= e($adminUrl('stories/edit/' . $s['id'])) ?>" title="Edytuj"><i class="bi bi-pencil"></i></a>
                            <?= $view->renderPartial('admin/stories/_cache-refresh-btn', [
                                'storyId'      => (int)$s['id'],
                                'admin_prefix' => $admin_prefix,
                                'csrf'         => $csrf,
                                'variant'      => 'admin',
                            ]) ?>
                            <form method="post" action="<?= e($adminUrl('stories/delete/' . $s['id'])) ?>" onsubmit="return confirm('Usunąć historię?');">
                                <?= $csrf->field() ?>
                                <button class="btn btn-outline-danger" title="Usuń"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Brak historii.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $view->renderPartial('layout/partials/pagination', [
    'page'    => $page,
    'pages'   => $pages,
    'baseUrl' => $admin_prefix . ($status === 'pending' ? '/stories/pending' : ($status !== null ? '/stories?status=' . $status : '/stories')),
    'sort'    => null,
]) ?>
