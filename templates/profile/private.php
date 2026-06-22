<?php
/**
 * Profil niedostępny (prywatność).
 *
 * @var string $username
 */
?>
<div class="social-profile social-profile--locked">
    <?= $view->renderPartial('layout/partials/breadcrumb', [
        'items' => [
            ['name' => 'Start', 'url' => url('/')],
            ['name' => $username, 'url' => url('/profil/' . $username)],
        ],
    ]) ?>

    <div class="social-profile__locked-card">
        <div class="social-profile__locked-icon" aria-hidden="true">🔒</div>
        <h1 class="page-title">@<?= e($username) ?></h1>
        <p class="page-subtitle">Ten profil jest prywatny. Tylko właściciel może go zobaczyć.</p>
        <?php if (!$auth->check()): ?>
            <a href="<?= e(url('/logowanie')) ?>" class="btn btn-accent">Zaloguj się</a>
        <?php endif; ?>
    </div>
</div>
