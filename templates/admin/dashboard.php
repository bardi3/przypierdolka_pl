<?php
/**
 * @var array<string, int> $stats
 * @var array<int, array<string, mixed>> $latest
 * @var array<int, array<string, mixed>> $auditLogs
 * @var string $admin_prefix
 */
$cards = [
    ['Wszystkie historie', $stats['stories_total'], 'bi-card-text', 'text-bg-primary'],
    ['Opublikowane', $stats['stories_published'], 'bi-check-circle', 'text-bg-success'],
    ['Do moderacji', $stats['stories_pending'], 'bi-hourglass-split', 'text-bg-warning'],
    ['Odrzucone', $stats['stories_rejected'], 'bi-x-circle', 'text-bg-danger'],
    ['Użytkownicy', $stats['users_total'], 'bi-people', 'text-bg-dark'],
    ['Oceny', $stats['ratings_total'], 'bi-star', 'text-bg-secondary'],
];
?>
<h1 class="h3 mb-4">Pulpit</h1>

<div class="row g-3 mb-4">
    <?php foreach ($cards as [$label, $value, $icon, $cls]): ?>
        <div class="col-6 col-lg-2">
            <div class="card admin-stat-card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <span class="badge <?= e($cls) ?> mb-2"><i class="bi <?= e($icon) ?>"></i></span>
                    <div class="display-6"><?= e($value) ?></div>
                    <small class="text-muted"><?= e($label) ?></small>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Ostatnie historie</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>Tytuł</th><th>Status</th><th>Autor</th><th>Data</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($latest as $s): ?>
                <tr>
                    <td><?= e(mb_strimwidth((string)$s['title'], 0, 50, '…')) ?></td>
                    <td><?= $view->renderPartial('admin/stories/_status', ['status' => $s['status']]) ?></td>
                    <td><?= e($s['author_username'] ?? $s['author_name'] ?? 'Anonim') ?></td>
                    <td><small><?= e($s['created_at']) ?></small></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url(ltrim($admin_prefix . '/stories/edit/' . $s['id'], '/'))) ?>">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($latest)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Brak historii.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($auditLogs)): ?>
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white fw-semibold">Ostatnie akcje moderacji</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead><tr><th>Akcja</th><th>Obiekt</th><th>Kto</th><th>IP</th><th>Kiedy</th></tr></thead>
            <tbody>
            <?php foreach ($auditLogs as $log): ?>
                <tr>
                    <td><code><?= e($log['action']) ?></code></td>
                    <td><?= e($log['entity_type']) ?> #<?= e($log['entity_id']) ?></td>
                    <td><?= e($log['username'] ?? 'system') ?></td>
                    <td><small><?= e($log['ip_address'] ?? '—') ?></small></td>
                    <td><small><?= e($log['created_at']) ?></small></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
