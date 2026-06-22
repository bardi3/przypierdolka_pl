<?php
/**
 * Lista wpisów tablicy (fragment HTML pod AJAX).
 *
 * @var array<int, array<string, mixed>> $stories
 * @var array<int, int> $userRatings
 */
$userRatings = $userRatings ?? [];
foreach ($stories as $story):
    echo $view->renderPartial('feed/_item', [
        'story'      => $story,
        'userRating' => $userRatings[(int)$story['id']] ?? null,
    ]);
endforeach;
