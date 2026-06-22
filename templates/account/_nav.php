<?php
/**
 * Nawigacja panelu konta (poziome zakładki).
 *
 * @var string $active
 * @var int $pending_friend_invites
 */
$active = $active ?? 'profile';
$pending_friend_invites = (int)($pending_friend_invites ?? 0);
$links = [
    'profile'  => ['url' => '/konto/profil', 'label' => 'Dane konta'],
    'privacy'  => ['url' => '/konto/prywatnosc', 'label' => 'Prywatność'],
    'friends'  => ['url' => '/konto/znajomi', 'label' => 'Znajomi'],
    'password' => ['url' => '/konto/haslo', 'label' => 'Hasło'],
    'stories'  => ['url' => '/konto/historie', 'label' => 'Moje historie'],
];
?>
<nav class="account-tabs" aria-label="Panel konta">
    <?php foreach ($links as $key => $link): ?>
        <a href="<?= e(url(ltrim($link['url'], '/'))) ?>"
           class="account-tabs__tab<?= $key === $active ? ' is-active' : '' ?>">
            <?= e($link['label']) ?>
            <?php if ($key === 'friends' && $pending_friend_invites > 0): ?>
                <span class="nav-badge nav-badge--inline"><?= e($pending_friend_invites) ?> nowe</span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</nav>
