<?php
/**
 * Spójny komunikat UI (flash, info, ostrzeżenia).
 *
 * @var string $type success|error|info|warning
 * @var string|null $message Tekst (escapowany)
 * @var string|null $html HTML od autora szablonu (bez escapowania)
 * @var bool $dismissible
 * @var string $class Dodatkowe klasy CSS
 */
$type = $type ?? 'info';
$dismissible = $dismissible ?? false;
$class = trim($class ?? '');
$map = [
    'success' => 'alert-success',
    'error'   => 'alert-danger',
    'info'    => 'alert-info',
    'warning' => 'alert-warning',
];
$alertClass = $map[$type] ?? 'alert-info';

$icons = [
    'success' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="8.25" stroke="currentColor" stroke-width="1.5"/><path d="M6.5 10.2 8.8 12.5 13.5 7.8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'error'   => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="8.25" stroke="currentColor" stroke-width="1.5"/><path d="M7.2 7.2 12.8 12.8M12.8 7.2 7.2 12.8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
    'info'    => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="8.25" stroke="currentColor" stroke-width="1.5"/><path d="M10 9.2V14M10 6.8h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
    'warning' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 3.5 17 16H3L10 3.5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M10 8.5V11.5M10 13.5h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
];
$icon = $icons[$type] ?? $icons['info'];
?>
<div class="pp-alert alert <?= e($alertClass) ?><?= $class !== '' ? ' ' . e($class) : '' ?>" role="alert">
    <span class="pp-alert__icon"><?= $icon ?></span>
    <div class="pp-alert__body">
        <?php if (isset($html)): ?>
            <?= $html ?>
        <?php else: ?>
            <?= e($message ?? '') ?>
        <?php endif; ?>
    </div>
    <?php if ($dismissible): ?>
        <button type="button" class="pp-alert__dismiss" aria-label="Zamknij">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M4.5 4.5 11.5 11.5M11.5 4.5 4.5 11.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
            </svg>
        </button>
    <?php endif; ?>
</div>
