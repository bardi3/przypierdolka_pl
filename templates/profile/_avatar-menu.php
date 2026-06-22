<?php
/**
 * Awatar właściciela profilu z menu (podgląd / zmiana).
 *
 * @var array<string, mixed> $profile
 */
$avatarPath = !empty($profile['avatar_path']) ? (string)$profile['avatar_path'] : null;
$avatarUrl = user_avatar_url($avatarPath);
?>
<div class="profile-avatar-menu" data-profile-avatar-menu>
    <button type="button"
            class="profile-avatar-menu__trigger"
            aria-expanded="false"
            aria-haspopup="menu"
            aria-label="Opcje zdjęcia profilowego">
        <?= $view->renderPartial('layout/partials/user-avatar', [
            'path'  => $avatarPath,
            'size'  => 'xl',
            'alt'   => 'Awatar @' . ($profile['username'] ?? ''),
            'class' => 'social-profile__avatar',
        ]) ?>
        <span class="profile-avatar-menu__caret" aria-hidden="true"></span>
    </button>
    <div class="profile-avatar-menu__menu" role="menu" hidden>
        <?php if ($avatarUrl !== null): ?>
            <button type="button"
                    class="profile-avatar-menu__item"
                    role="menuitem"
                    data-profile-avatar-view
                    data-avatar-url="<?= e($avatarUrl) ?>">
                Wyświetl fotografię
            </button>
        <?php endif; ?>
        <a href="<?= e(url('/konto/profil#account-avatar-editor')) ?>"
           class="profile-avatar-menu__item profile-avatar-menu__link"
           role="menuitem">
            Zmień foto
        </a>
    </div>
</div>

<div class="avatar-lightbox" data-avatar-lightbox hidden>
    <button type="button" class="avatar-lightbox__backdrop" data-avatar-lightbox-close aria-label="Zamknij"></button>
    <div class="avatar-lightbox__dialog" role="dialog" aria-modal="true" aria-label="Zdjęcie profilowe">
        <button type="button" class="avatar-lightbox__close" data-avatar-lightbox-close aria-label="Zamknij">&times;</button>
        <img src="" alt="Zdjęcie profilowe" class="avatar-lightbox__img" data-avatar-lightbox-img width="512" height="512">
    </div>
</div>
