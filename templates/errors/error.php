<?php
/**
 * @var int $status
 * @var string $message
 */
$status = $status ?? 500;
$message = $message ?? 'Coś poszło nie tak.';
?>
<div class="row justify-content-center text-center py-5">
    <div class="col-md-7">
        <div class="display-1 fw-bold text-warning"><?= e($status) ?></div>
        <p class="fs-4 mb-4"><?= e($message) ?></p>
        <a href="<?= e(url('/')) ?>" class="btn btn-dark">Strona główna</a>
    </div>
</div>
