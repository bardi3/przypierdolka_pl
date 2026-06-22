<?php
/**
 * @var array<string, mixed> $user
 * @var array<string, array<int, string>> $errors
 * @var string $accountNav
 * @var \App\Core\Csrf $csrf
 */
$errors = $errors ?? [];
$err = static fn (string $f): string => isset($errors[$f]) ? 'is-invalid' : '';
?>
<?= $view->renderPartial('account/_page-open', ['accountNav' => $accountNav]) ?>

        <header class="page-header">
            <h1 class="page-title">Dane konta</h1>
            <p class="page-subtitle">Edytuj profil widoczny na tablicy publicznej.</p>
        </header>

        <?= $view->renderPartial('account/_avatar-editor', get_defined_vars()) ?>

        <form method="post" action="<?= e(url('/konto/profil')) ?>" class="account-form card">
            <div class="card-body">
                <?= $csrf->field() ?>

                <div class="mb-3">
                    <label class="form-label" for="username">Nazwa użytkownika</label>
                    <input type="text" id="username" name="username" maxlength="30" required
                           class="form-control <?= $err('username') ?>"
                           value="<?= old($old ?? [], 'username', (string)($user['username'] ?? '')) ?>">
                    <?php if (isset($errors['username'])): ?>
                        <div class="invalid-feedback d-block"><?= e($errors['username'][0]) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="email">E-mail</label>
                    <input type="email" id="email" name="email" maxlength="120" required
                           class="form-control <?= $err('email') ?>"
                           value="<?= old($old ?? [], 'email', (string)($user['email'] ?? '')) ?>">
                    <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback d-block"><?= e($errors['email'][0]) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="bio">Opis profilu (bio)</label>
                    <textarea id="bio" name="bio" maxlength="500" rows="3" class="form-control <?= $err('bio') ?>"
                              placeholder="Kilka słów o sobie — widoczne na tablicy profilu."><?= old($old ?? [], 'bio', (string)($user['bio'] ?? '')) ?></textarea>
                    <?php if (isset($errors['bio'])): ?>
                        <div class="invalid-feedback d-block"><?= e($errors['bio'][0]) ?></div>
                    <?php endif; ?>
                </div>

                <p class="text-muted small mb-3">
                    Rola: <strong><?= e($user['role'] ?? 'user') ?></strong>
                    · Konto od <?= e(date('Y-m-d', strtotime((string)($user['created_at'] ?? 'now')))) ?>
                    · <a href="<?= e(url('/profil/' . ($user['username'] ?? ''))) ?>">Zobacz publiczny profil</a>
                </p>

                <button type="submit" class="btn btn-accent">Zapisz zmiany</button>
            </div>
        </form>

<?= $view->renderPartial('account/_page-close', get_defined_vars()) ?>
