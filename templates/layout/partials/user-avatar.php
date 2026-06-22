<?php
/**
 * Awatar użytkownika — zdjęcie lub ikona domyślna.
 *
 * @var string|null $path         Ścieżka względna (avatar_path / author_avatar_path)
 * @var string      $size         sm | md | lg | xl
 * @var string|null $href         Opcjonalny link
 * @var string      $class        Dodatkowe klasy CSS
 * @var string      $alt          Tekst alternatywny
 * @var bool        $hideIcon     Ukryj ikonę gdy brak zdjęcia (pusty okrąg)
 */
$path = $path ?? null;
$size = $size ?? 'md';
$href = $href ?? null;
$class = trim('pp-avatar pp-avatar--' . $size . ' ' . ($class ?? ''));
$alt = $alt ?? 'Awatar';
$hideIcon = $hideIcon ?? false;
$imgUrl = user_avatar_url($path);

$inner = '';
if ($imgUrl !== null) {
    $inner = '<img src="' . e($imgUrl) . '" alt="' . e($alt) . '" class="pp-avatar__img" width="256" height="256" loading="lazy" decoding="async">';
} elseif (!$hideIcon) {
    $inner = $view->renderPartial('layout/partials/icon', ['name' => 'user', 'class' => 'pp-avatar__icon']);
}

if ($href !== null && $href !== '') {
    echo '<a href="' . e($href) . '" class="' . e($class) . '">' . $inner . '</a>';
} else {
    echo '<span class="' . e($class) . '">' . $inner . '</span>';
}
