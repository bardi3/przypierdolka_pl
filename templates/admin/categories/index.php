<?php
/**
 * @var array<int, array<string, mixed>> $items
 * @var string $admin_prefix
 * @var \App\Core\Csrf $csrf
 */
$adminUrl = static fn (string $p): string => url(ltrim($admin_prefix . '/' . ltrim($p, '/'), '/'));
?>
<div class="admin-page-head">
    <h1 class="h3">Kategorie</h1>
    <a class="btn btn-warning btn-sm" href="<?= e($adminUrl('categories/add')) ?>"><i class="bi bi-plus-lg"></i> Dodaj</a>
</div>

<form method="post" action="<?= e($adminUrl('categories/sort')) ?>" id="category-sort-form">
    <?= $csrf->field() ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:52px" aria-hidden="true"></th>
                        <th style="width:70px">Lp.</th>
                        <th>Nazwa</th>
                        <th>Slug</th>
                        <th>Historie</th>
                        <th class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody id="category-sortable">
                <?php foreach ($items as $i => $c): ?>
                    <tr data-category-id="<?= e($c['id']) ?>">
                        <td class="drag-handle text-muted" title="Przeciągnij, aby zmienić kolejność">
                            <i class="bi bi-grip-vertical fs-5"></i>
                        </td>
                        <td>
                            <span class="sort-index badge text-bg-light border"><?= e($i + 1) ?></span>
                            <input type="hidden" name="sort_order[<?= e($c['id']) ?>]" value="<?= e($i + 1) ?>"
                                   class="sort-order-input">
                        </td>
                        <td class="fw-semibold"><?= e($c['name']) ?></td>
                        <td><code><?= e($c['slug']) ?></code></td>
                        <td><span class="badge text-bg-secondary"><?= e($c['stories_count'] ?? 0) ?></span></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a class="btn btn-outline-secondary" href="<?= e($adminUrl('categories/edit/' . $c['id'])) ?>"><i class="bi bi-pencil"></i></a>
                                <button type="button" class="btn btn-outline-danger" onclick="if(confirm('Usunąć?')){this.closest('tr').querySelector('form.del').submit();}"><i class="bi bi-trash"></i></button>
                            </div>
                            <form class="del d-none" method="post" action="<?= e($adminUrl('categories/delete/' . $c['id'])) ?>">
                                <?= $csrf->field() ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Brak kategorii.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (!empty($items)): ?>
        <p class="text-muted small mb-2"><i class="bi bi-arrows-move"></i> Przeciągnij wiersze za uchwyt, potem zapisz kolejność.</p>
        <button type="submit" class="btn btn-warning btn-sm"><i class="bi bi-sort-down"></i> Zapisz kolejność</button>
    <?php endif; ?>
</form>

<?php if (!empty($items)): ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
    var tbody = document.getElementById('category-sortable');
    if (!tbody || typeof Sortable === 'undefined') {
        return;
    }

    function refreshOrder() {
        tbody.querySelectorAll('tr[data-category-id]').forEach(function (row, idx) {
            var order = idx + 1;
            var badge = row.querySelector('.sort-index');
            var input = row.querySelector('.sort-order-input');
            if (badge) {
                badge.textContent = order;
            }
            if (input) {
                input.value = order;
            }
        });
    }

    Sortable.create(tbody, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        onEnd: refreshOrder
    });
})();
</script>
<?php endif; ?>
