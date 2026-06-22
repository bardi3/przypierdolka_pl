<?php
/**
 * Semantyczne okruszki (HTML) — dane strukturalne w JSON-LD (@graph).
 *
 * @var array<int, array{name:string, url?:string}> $items
 */
$items = $items ?? [];
if ($items === []) {
    return;
}
$lastIndex = count($items) - 1;
?>
<nav class="pp-breadcrumb" aria-label="Breadcrumb">
    <ol class="pp-breadcrumb__list">
        <?php foreach ($items as $i => $item): ?>
            <li class="pp-breadcrumb__item">
                <?php if ($i > 0): ?>
                    <span class="pp-breadcrumb__sep" aria-hidden="true">/</span>
                <?php endif; ?>
                <?php if ($i < $lastIndex && !empty($item['url'])): ?>
                    <a class="pp-breadcrumb__link" href="<?= e($item['url']) ?>"><?= e($item['name']) ?></a>
                <?php else: ?>
                    <span class="pp-breadcrumb__current" aria-current="page"><?= e($item['name']) ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
