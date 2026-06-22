<?php
/**
 * @var array<string, array<int, string>> $flashes
 */
$flashes = $flashes ?? [];
$typeMap = [
    'success' => 'success',
    'error'   => 'error',
    'info'    => 'info',
    'warning' => 'warning',
];
?>
<?php foreach ($flashes as $type => $messages): ?>
    <?php foreach ($messages as $message): ?>
        <?= $view->renderPartial('layout/partials/alert', [
            'type'        => $typeMap[$type] ?? 'info',
            'message'     => $message,
            'dismissible' => true,
        ]) ?>
    <?php endforeach; ?>
<?php endforeach; ?>
