<?php
/**
 * @var int $page
 * @var int $pages
 * @var string $baseUrl
 * @var string|null $sort
 */
$page = (int)($page ?? 1);
$pages = (int)($pages ?? 1);
$baseUrl = $baseUrl ?? '/';
$sort = $sort ?? null;

if ($pages <= 1) {
    return;
}

$buildUrl = static function (int $p) use ($baseUrl, $sort): string {
    $path = ltrim($baseUrl, '/');
    if ($path === '' || $path === '/') {
        return $p <= 1 ? url('/') : url('strona/' . $p);
    }
    $query = [];
    if ($p > 1) {
        $query['page'] = $p;
    }
    if ($sort !== null && $sort !== 'newest' && !str_starts_with($sort, 'rank_')) {
        $query['sort'] = $sort;
    }
    $qs = $query !== [] ? '?' . http_build_query($query) : '';
    return url($path) . $qs;
};

$start = max(1, $page - 2);
$end = min($pages, $page + 2);
?>
<nav class="pagination" aria-label="Paginacja">
    <a class="pagination__link<?= $page <= 1 ? ' is-disabled' : '' ?>" href="<?= e($buildUrl(max(1, $page - 1))) ?>" aria-label="Poprzednia strona">&laquo;</a>

    <?php if ($start > 1): ?>
        <a class="pagination__link" href="<?= e($buildUrl(1)) ?>">1</a>
        <?php if ($start > 2): ?><span class="pagination__gap">…</span><?php endif; ?>
    <?php endif; ?>

    <?php for ($i = $start; $i <= $end; $i++): ?>
        <a class="pagination__link<?= $i === $page ? ' is-active' : '' ?>" href="<?= e($buildUrl($i)) ?>"<?= $i === $page ? ' aria-current="page"' : '' ?>><?= e($i) ?></a>
    <?php endfor; ?>

    <?php if ($end < $pages): ?>
        <?php if ($end < $pages - 1): ?><span class="pagination__gap">…</span><?php endif; ?>
        <a class="pagination__link" href="<?= e($buildUrl($pages)) ?>"><?= e($pages) ?></a>
    <?php endif; ?>

    <a class="pagination__link<?= $page >= $pages ? ' is-disabled' : '' ?>" href="<?= e($buildUrl(min($pages, $page + 1))) ?>" aria-label="Następna strona">&raquo;</a>
</nav>
