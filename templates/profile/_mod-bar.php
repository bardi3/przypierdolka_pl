<?php
/**
 * Pasek moderacji profilu użytkownika.
 *
 * @var array<string, mixed> $profile
 * @var bool $canModerate
 * @var \App\Core\Csrf $csrf
 */
if (!($canModerate ?? false)) {
    return;
}

$hasAvatar = !empty($profile['avatar_path']);
if (!$hasAvatar) {
    return;
}
?>
<div class="profile-mod-bar" role="toolbar" aria-label="Moderacja profilu">
    <div class="profile-mod-bar__info">
        <span class="profile-mod-bar__label">Moderacja profilu</span>
        <span class="profile-mod-bar__user">@<?= e($profile['username']) ?></span>
    </div>
    <form method="post"
          action="<?= e(url('/profil/' . $profile['username'] . '/avatar/usun')) ?>"
          class="profile-mod-bar__form"
          onsubmit="return confirm('Usunąć własne zdjęcie profilowe tego użytkownika?');">
        <?= $csrf->field() ?>
        <button type="submit" class="story-mod-bar__btn story-mod-bar__btn--warn">
            Usuń awatar użytkownika
        </button>
    </form>
</div>
