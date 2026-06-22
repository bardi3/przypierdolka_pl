<?php
/**
 * @var array<string, mixed> $old
 * @var array<string, array<int,string>> $errors
 * @var \App\Core\Csrf $csrf
 */
$old = $old ?? [];
$errors = $errors ?? [];
$err = static fn (string $f): string => isset($errors[$f]) ? 'is-invalid' : '';
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <h1 class="h3 mb-4 text-center">Rejestracja</h1>
        <form method="post" action="<?= e(url('/rejestracja')) ?>" class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?= $csrf->field() ?>
                <div class="mb-3">
                    <label class="form-label" for="username">Nazwa użytkownika</label>
                    <input type="text" id="username" name="username" class="form-control <?= $err('username') ?>"
                           value="<?= old($old, 'username') ?>" required>
                    <?php if (isset($errors['username'])): ?><div class="invalid-feedback"><?= e($errors['username'][0]) ?></div><?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">E-mail</label>
                    <input type="email" id="email" name="email" class="form-control <?= $err('email') ?>"
                           value="<?= old($old, 'email') ?>" required>
                    <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email'][0]) ?></div><?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Hasło</label>
                    <input type="password" id="password" name="password" class="form-control <?= $err('password') ?>" required>
                    <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= e($errors['password'][0]) ?></div><?php endif; ?>
                    <div class="form-text">Minimum 8 znaków.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password_confirmation">Powtórz hasło</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-warning w-100 fw-semibold">Załóż konto</button>
                <p class="text-center mt-3 mb-0">
                    Masz już konto? <a href="<?= e(url('/logowanie')) ?>">Zaloguj się</a>
                </p>
            </div>
        </form>
    </div>
</div>
