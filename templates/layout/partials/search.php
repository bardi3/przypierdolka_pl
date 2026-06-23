<?php
/**
 * Widget wyszukiwarki w nagłówku.
 *
 * @var \App\Core\Auth $auth
 */
$searchValue = trim((string)($_GET['q'] ?? ''));
$isSearchPage = str_starts_with(
    rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/'),
    '/szukaj'
);
if ($isSearchPage) {
    $searchValue = trim((string)($_GET['q'] ?? $searchValue));
}
?>
<div class="site-search" data-site-search>
    <form class="site-search__form" action="<?= e(url('/szukaj')) ?>" method="get" role="search">
        <label class="visually-hidden" for="site-search-input">Szukaj</label>
        <input type="search"
               id="site-search-input"
               name="q"
               class="site-search__input"
               placeholder="Szukaj historii, użytkowników…"
               value="<?= e($searchValue) ?>"
               autocomplete="off"
               maxlength="80"
               data-site-search-input>
        <button type="submit" class="site-search__submit nav-icon-btn" title="Szukaj" aria-label="Szukaj">
            <?= $view->renderPartial('layout/partials/icon', ['name' => 'search']) ?>
        </button>
        <input type="text" name="website" class="site-search__trap visually-hidden" tabindex="-1" autocomplete="off" value="">
    </form>
    <div class="site-search__dropdown" data-site-search-dropdown hidden></div>
</div>
