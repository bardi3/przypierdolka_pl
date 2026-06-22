<?php
/**
 * @var string $status
 */
$map = [
    'published' => ['Opublikowana', 'text-bg-success'],
    'pending'   => ['Oczekuje', 'text-bg-warning'],
    'rejected'  => ['Odrzucona', 'text-bg-danger'],
];
[$label, $cls] = $map[$status] ?? [$status, 'text-bg-secondary'];
?>
<span class="badge <?= e($cls) ?>"><?= e($label) ?></span>
