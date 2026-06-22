<?php
/**
 * Szybka nawigacja — rankingi i kategorie zawsze pod ręką (także przy czytaniu historii).
 */
$currentPath = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/') ?: '/';
$isActive = static function (string $path) use ($currentPath): string {
    $path = rtrim($path, '/') ?: '/';
    if ($path === '/') {
        return $currentPath === '/' ? ' is-active' : '';
    }
    return str_starts_with($currentPath, $path) ? ' is-active' : '';
};

$quickLinks = [
    ['label' => 'Top tygodnia', 'url' => '/top/tydzien'],
    ['label' => 'Top miesiąca', 'url' => '/top/miesiac'],
    ['label' => 'Top wszystkich', 'url' => '/top/wszystkie'],
    ['label' => 'Kategorie', 'url' => '/kategorie', 'match' => ['/kategorie', '/kategoria']],
];
?>
<nav class="site-subnav" aria-label="Rankingi i kategorie">
    <div class="container site-subnav__inner">
        <?php foreach ($quickLinks as $link): ?>
            <?php
            $active = $isActive($link['url']);
            if ($active === '' && !empty($link['match'])) {
                foreach ($link['match'] as $prefix) {
                    if (str_starts_with($currentPath, $prefix)) {
                        $active = ' is-active';
                        break;
                    }
                }
            }
            ?>
            <a class="site-subnav__link<?= $active ?>"
               href="<?= e(url(ltrim($link['url'], '/'))) ?>">
                <?= e($link['label']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</nav>
