<?php
/**
 * AJAX — odświeżenie cache historii (lista, tablica, obrazek).
 *
 * @var int $storyId
 * @var string $admin_prefix
 * @var \App\Core\Csrf $csrf
 * @var string $variant admin|mod-bar
 */
$variant = $variant ?? 'admin';
$storyId = (int)($storyId ?? 0);
$title = 'Odśwież cache historii';
$refreshUrl = url(ltrim($admin_prefix . '/ajax/stories/refresh/' . $storyId, '/'));
$btnClass = $variant === 'mod-bar'
    ? 'story-mod-bar__btn story-mod-bar__btn--ghost story-cache-refresh'
    : 'btn btn-outline-secondary btn-sm story-cache-refresh';
?>
<button type="button"
        class="<?= e($btnClass) ?>"
        data-story-cache-refresh
        data-refresh-url="<?= e($refreshUrl) ?>"
        data-csrf="<?= e($csrf->token()) ?>"
        data-csrf-name="<?= e($csrf->tokenName()) ?>"
        title="<?= e($title) ?>"
        aria-label="<?= e($title) ?>">
    <span class="story-cache-refresh__state story-cache-refresh__state--idle" aria-hidden="true">
        <?php if ($variant === 'admin'): ?>
            <i class="bi bi-arrow-clockwise"></i>
        <?php else: ?>
            <span class="story-cache-refresh__glyph">↻</span>
        <?php endif; ?>
    </span>
    <span class="story-cache-refresh__state story-cache-refresh__state--loading" hidden aria-hidden="true">
        <span class="story-cache-refresh__spinner"></span>
    </span>
    <span class="story-cache-refresh__state story-cache-refresh__state--ok" hidden aria-hidden="true">
        <span class="story-cache-refresh__glyph story-cache-refresh__glyph--ok">✓</span>
    </span>
    <span class="story-cache-refresh__state story-cache-refresh__state--error" hidden aria-hidden="true">
        <span class="story-cache-refresh__glyph story-cache-refresh__glyph--error">✕</span>
    </span>
    <?php if ($variant === 'mod-bar'): ?>
        <span class="story-cache-refresh__label">Cache</span>
    <?php endif; ?>
</button>
