<?php
/**
 * @var array<string, mixed> $old
 * @var array<string, array<int,string>> $errors
 * @var \App\Core\Csrf $csrf
 */
$old = $old ?? [];
$errors = $errors ?? [];
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <h1 class="h3 mb-4 text-center">Logowanie</h1>
        <form method="post" action="<?= e(url('/logowanie')) ?>" class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?= $csrf->field() ?>
                <div class="mb-3">
                    <label class="form-label" for="login">Login lub e-mail</label>
                    <input type="text" id="login" name="login" class="form-control <?= isset($errors['login']) ? 'is-invalid' : '' ?>"
                           value="<?= old($old, 'login') ?>" required autofocus>
                    <?php if (isset($errors['login'])): ?><div class="invalid-feedback"><?= e($errors['login'][0]) ?></div><?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Hasło</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-warning w-100 fw-semibold">Zaloguj się</button>
                <p class="text-center mt-3 mb-0">
                    Nie masz konta? <a href="<?= e(url('/rejestracja')) ?>">Zarejestruj się</a>
                </p>
                <?php if (($app_env ?? '') === 'local'): ?>
                    <p class="text-center mt-2 mb-0">
                        <small class="text-muted">
                            Dev: <a href="<?= e(url('/dev/reset-rate-limits')) ?>">reset limitów logowania</a>
                        </small>
                    </p>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
