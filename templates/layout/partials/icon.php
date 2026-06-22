<?php
/**
 * Inline SVG icons (bez zewnętrznych fontów).
 *
 * @var string $name user|calendar|eye|users|logout|search
 * @var string $class
 */
$name = $name ?? 'user';
$class = trim('pp-icon ' . ($class ?? ''));
?>
<?php if ($name === 'user'): ?>
<svg class="<?= e($class) ?>" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
    <circle cx="8" cy="5" r="2.75" stroke="currentColor" stroke-width="1.4"/>
    <path d="M3.25 13.25c.65-2.45 2.45-3.75 4.75-3.75s4.1 1.3 4.75 3.75" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
</svg>
<?php elseif ($name === 'calendar'): ?>
<svg class="<?= e($class) ?>" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
    <rect x="2.5" y="3.5" width="11" height="10" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
    <path d="M5 2v2.5M11 2v2.5M2.5 6.5h11" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
</svg>
<?php elseif ($name === 'eye'): ?>
<svg class="<?= e($class) ?>" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
    <path d="M1.75 8s2.25-4.25 6.25-4.25S14.25 8 14.25 8s-2.25 4.25-6.25 4.25S1.75 8 1.75 8Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
    <circle cx="8" cy="8" r="1.75" stroke="currentColor" stroke-width="1.4"/>
</svg>
<?php elseif ($name === 'users'): ?>
<svg class="<?= e($class) ?>" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
    <circle cx="5.5" cy="5" r="2" stroke="currentColor" stroke-width="1.35"/>
    <path d="M1.5 13c.55-2.1 2.1-3.25 4-3.25s3.45 1.15 4 3.25" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>
    <circle cx="11.25" cy="5.75" r="1.65" stroke="currentColor" stroke-width="1.25"/>
    <path d="M9.25 13c.35-1.45 1.35-2.25 2.75-2.25 1.1 0 1.95.55 2.35 1.55" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/>
</svg>
<?php elseif ($name === 'logout'): ?>
<svg class="<?= e($class) ?>" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
    <path d="M6.25 2.75h4.5a1 1 0 0 1 1 1v8.5a1 1 0 0 1-1 1h-4.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
    <path d="M9.25 8H2.75M7.25 5.5 9.75 8l-2.5 2.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
<?php elseif ($name === 'search'): ?>
<svg class="<?= e($class) ?>" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
    <circle cx="7" cy="7" r="4.25" stroke="currentColor" stroke-width="1.4"/>
    <path d="m10.5 10.5 3.25 3.25" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
</svg>
<?php endif; ?>
