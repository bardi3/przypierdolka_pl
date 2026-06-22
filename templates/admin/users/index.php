<?php
/**
 * @var array<int, array<string, mixed>> $items
 * @var int $page
 * @var int $pages
 * @var string $admin_prefix
 * @var \App\Core\Csrf $csrf
 */
$adminUrl = static fn (string $p): string => url(ltrim($admin_prefix . '/' . ltrim($p, '/'), '/'));
?>
<div class="admin-page-head">
    <h1 class="h3">Użytkownicy</h1>
    <a class="btn btn-warning btn-sm" href="<?= e($adminUrl('users/add')) ?>"><i class="bi bi-plus-lg"></i> Dodaj</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>#</th><th>Login</th><th>E-mail</th><th>Rola</th><th>Status</th><th>Ostatnie logowanie</th><th class="text-end">Akcje</th></tr>
            </thead>
            <tbody>
            <?php foreach ($items as $u): ?>
                <tr>
                    <td><?= e($u['id']) ?></td>
                    <td class="fw-semibold"><?= e($u['username']) ?></td>
                    <td><small><?= e($u['email']) ?></small></td>
                    <td><span class="badge text-bg-secondary"><?= e($u['role']) ?></span></td>
                    <td>
                        <?php if (($u['status'] ?? 'active') === 'active'): ?>
                            <span class="badge text-bg-success">aktywny</span>
                        <?php else: ?>
                            <span class="badge text-bg-danger">zablokowany</span>
                        <?php endif; ?>
                    </td>
                    <td><small><?= e($u['last_login_at'] ?? '—') ?></small></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a class="btn btn-outline-secondary" href="<?= e($adminUrl('users/edit/' . $u['id'])) ?>"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="<?= e($adminUrl('users/delete/' . $u['id'])) ?>" onsubmit="return confirm('Usunąć użytkownika?');">
                                <?= $csrf->field() ?>
                                <button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $view->renderPartial('layout/partials/pagination', [
    'page' => $page, 'pages' => $pages, 'baseUrl' => $admin_prefix . '/users', 'sort' => null,
]) ?>
