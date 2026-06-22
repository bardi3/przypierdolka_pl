<?php
/**
 * @var array<int, array<string, mixed>> $friends
 * @var array<int, array<string, mixed>> $incoming
 * @var array<int, array<string, mixed>> $outgoing
 * @var string $accountNav
 * @var \App\Core\Csrf $csrf
 */
?>
<?= $view->renderPartial('account/_page-open', ['accountNav' => $accountNav]) ?>

        <header class="page-header">
            <h1 class="page-title">Znajomi</h1>
            <p class="page-subtitle">Zaproszenia, akceptacja i lista znajomych.</p>
        </header>

        <?php if (!empty($incoming)): ?>
            <section class="account-section card">
                <div class="card-body">
                    <h2 class="h6">Oczekujące zaproszenia (<?= e(count($incoming)) ?>)</h2>
                    <ul class="account-friend-list">
                        <?php foreach ($incoming as $req): ?>
                            <li class="account-friend-list__item">
                                <a href="<?= e(url('/profil/' . $req['username'])) ?>">@<?= e($req['username']) ?></a>
                                <div class="account-friend-list__actions">
                                    <form method="post" action="<?= e(url('/konto/znajomi/akceptuj/' . $req['id'])) ?>">
                                        <?= $csrf->field() ?>
                                        <button type="submit" class="btn btn-accent btn-sm">Akceptuj</button>
                                    </form>
                                    <form method="post" action="<?= e(url('/konto/znajomi/odrzuc/' . $req['id'])) ?>">
                                        <?= $csrf->field() ?>
                                        <button type="submit" class="btn btn-outline btn-sm">Odrzuć</button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
        <?php else: ?>
            <?= $view->renderPartial('layout/partials/alert', [
                'type' => 'info',
                'message' => 'Brak oczekujących zaproszeń. Odwiedź czyjeś profile i wyślij zaproszenie.',
            ]) ?>
        <?php endif; ?>

        <?php if (!empty($outgoing)): ?>
            <section class="account-section card">
                <div class="card-body">
                    <h2 class="h6">Wysłane zaproszenia</h2>
                    <ul class="account-friend-list">
                        <?php foreach ($outgoing as $req): ?>
                            <li class="account-friend-list__item">
                                <a href="<?= e(url('/profil/' . $req['username'])) ?>">@<?= e($req['username']) ?></a>
                                <form method="post" action="<?= e(url('/konto/znajomi/odrzuc/' . $req['id'])) ?>">
                                    <?= $csrf->field() ?>
                                    <button type="submit" class="btn btn-outline btn-sm">Anuluj</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
        <?php endif; ?>

        <section class="account-section card">
            <div class="card-body">
                <h2 class="h6">Twoi znajomi (<?= e(count($friends)) ?>)</h2>
                <?php if (empty($friends)): ?>
                    <p class="text-muted mb-0">Nie masz jeszcze znajomych. Odwiedź czyjeś profile i wyślij zaproszenie.</p>
                <?php else: ?>
                    <ul class="account-friend-list">
                        <?php foreach ($friends as $friend): ?>
                            <li class="account-friend-list__item">
                                <a href="<?= e(url('/profil/' . $friend['username'])) ?>">@<?= e($friend['username']) ?></a>
                                <form method="post" action="<?= e(url('/konto/znajomi/usun/' . $friend['friendship_id'])) ?>"
                                      onsubmit="return confirm('Usunąć ze znajomych?');">
                                    <?= $csrf->field() ?>
                                    <button type="submit" class="btn btn-outline btn-sm">Usuń</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>

<?= $view->renderPartial('account/_page-close', get_defined_vars()) ?>
