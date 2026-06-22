<?php
/**
 * @var array<string, array<int, string>> $errors
 * @var string $accountNav
 * @var \App\Core\Csrf $csrf
 */
$errors = $errors ?? [];
$err = static fn (string $f): string => isset($errors[$f]) ? 'is-invalid' : '';
?>
<?= $view->renderPartial('account/_page-open', ['accountNav' => $accountNav]) ?>

        <header class="page-header">
            <h1 class="page-title">Zmiana hasła</h1>
            <p class="page-subtitle">Użyj co najmniej 8 znaków. Po zmianie pozostaniesz zalogowany.</p>
        </header>

        <form method="post" action="<?= e(url('/konto/haslo')) ?>" class="account-form card">
            <div class="card-body">
                <?= $csrf->field() ?>

                <div class="mb-3">
                    <label class="form-label" for="current_password">Obecne hasło</label>
                    <input type="password" id="current_password" name="current_password" required
                           class="form-control <?= $err('current_password') ?>" autocomplete="current-password">
                    <?php if (isset($errors['current_password'])): ?>
                        <div class="invalid-feedback d-block"><?= e($errors['current_password'][0]) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Nowe hasło</label>
                    <input type="password" id="password" name="password" required minlength="8"
                           class="form-control <?= $err('password') ?>" autocomplete="new-password">
                    <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback d-block"><?= e($errors['password'][0]) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password_confirmation">Powtórz nowe hasło</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                           class="form-control" autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-accent">Zmień hasło</button>
            </div>
        </form>

<?= $view->renderPartial('account/_page-close', get_defined_vars()) ?>
