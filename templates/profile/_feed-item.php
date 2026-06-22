<?php
/**
 * Wpis na tablicy profilu — delegacja do wspólnego szablonu feedu.
 *
 * @var array<string, mixed> $story
 * @var int|null $userRating
 */
echo $view->renderPartial('feed/_item', [
    'story'      => $story,
    'userRating' => $userRating ?? null,
]);
