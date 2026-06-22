<?php
/**
 * Przyciski akcji znajomych na profilu.
 *
 * @var string $friendState
 * @var string $profileUsername
 * @var bool $isOwner
 * @var \App\Core\Auth $auth
 * @var \App\Core\Csrf $csrf
 */
use App\Services\FriendshipService;

if ($isOwner ?? false) {
    ?>
    <a href="<?= e(url('/konto/profil')) ?>" class="btn btn-accent btn-sm">Edytuj profil</a>
    <a href="<?= e(url('/konto/prywatnosc')) ?>" class="btn btn-outline btn-sm">Prywatność</a>
    <?php
    return;
}

if (!$auth->check()) {
    return;
}
?>
<?php if ($friendState === FriendshipService::STATE_NONE): ?>
    <form method="post" action="<?= e(url('/profil/' . $profileUsername . '/zapros')) ?>" class="social-profile__action-form">
        <?= $csrf->field() ?>
        <button type="submit" class="btn btn-accent btn-sm">+ Dodaj do znajomych</button>
    </form>
<?php elseif ($friendState === FriendshipService::STATE_PENDING_SENT): ?>
    <span class="social-profile__status-pill">Zaproszenie wysłane</span>
    <form method="post" action="<?= e(url('/profil/' . $profileUsername . '/odrzuc')) ?>" class="social-profile__action-form">
        <?= $csrf->field() ?>
        <button type="submit" class="btn btn-outline btn-sm">Anuluj</button>
    </form>
<?php elseif ($friendState === FriendshipService::STATE_PENDING_RECEIVED): ?>
    <form method="post" action="<?= e(url('/profil/' . $profileUsername . '/akceptuj')) ?>" class="social-profile__action-form">
        <?= $csrf->field() ?>
        <button type="submit" class="btn btn-accent btn-sm">Akceptuj</button>
    </form>
    <form method="post" action="<?= e(url('/profil/' . $profileUsername . '/odrzuc')) ?>" class="social-profile__action-form">
        <?= $csrf->field() ?>
        <button type="submit" class="btn btn-outline btn-sm">Odrzuć</button>
    </form>
<?php elseif ($friendState === FriendshipService::STATE_FRIENDS): ?>
    <span class="social-profile__status-pill social-profile__status-pill--friends">Znajomi</span>
    <form method="post" action="<?= e(url('/profil/' . $profileUsername . '/usun')) ?>" class="social-profile__action-form"
          onsubmit="return confirm('Usunąć tego użytkownika ze znajomych?');">
        <?= $csrf->field() ?>
        <button type="submit" class="btn btn-outline btn-sm">Usuń ze znajomych</button>
    </form>
<?php endif; ?>
