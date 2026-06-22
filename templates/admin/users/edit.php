<?php
/**
 * @var array<string, mixed>|null $user
 * @var array<int, string> $roles
 * @var array<string, array<int,string>> $errors
 * @var string $admin_prefix
 * @var \App\Core\Csrf $csrf
 */
$errors = $errors ?? [];
$isNew = $user === null || !isset($user['id']);
$err = static fn (string $f): string => isset($errors[$f]) ? 'is-invalid' : '';
$action = $isNew
    ? url(ltrim($admin_prefix . '/users/add', '/'))
    : url(ltrim($admin_prefix . '/users/edit/' . $user['id'], '/'));
?>
<h1 class="h3 mb-4"><?= $isNew ? 'Nowy użytkownik' : 'Edycja: ' . e($user['username'] ?? '') ?></h1>

<form method="post" action="<?= e($action) ?>" class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <?= $csrf->field() ?>

        <?php if ($isNew): ?>
            <div class="mb-3">
                <label class="form-label" for="username">Nazwa użytkownika</label>
                <input type="text" id="username" name="username" class="form-control <?= $err('username') ?>" value="<?= e($user['username'] ?? '') ?>" required>
                <?php if (isset($errors['username'])): ?><div class="invalid-feedback"><?= e($errors['username'][0]) ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">E-mail</label>
                <input type="email" id="email" name="email" class="form-control <?= $err('email') ?>" value="<?= e($user['email'] ?? '') ?>" required>
                <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email'][0]) ?></div><?php endif; ?>
            </div>
        <?php else: ?>
            <div class="mb-3">
                <label class="form-label" for="username">Login (nazwa użytkownika)</label>
                <input type="text" id="username" name="username" class="form-control <?= $err('username') ?>"
                       value="<?= e($user['username'] ?? '') ?>" required autocomplete="username">
                <?php if (isset($errors['username'])): ?><div class="invalid-feedback"><?= e($errors['username'][0]) ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">E-mail</label>
                <input type="email" id="email" name="email" class="form-control <?= $err('email') ?>"
                       value="<?= e($user['email'] ?? '') ?>" required autocomplete="email">
                <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email'][0]) ?></div><?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="role">Rola</label>
                <select id="role" name="role" class="form-select">
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= e($role) ?>" <?= ($user['role'] ?? 'user') === $role ? 'selected' : '' ?>><?= e($role) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (!$isNew): ?>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="status">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="active" <?= ($user['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>aktywny</option>
                    <option value="blocked" <?= ($user['status'] ?? '') === 'blocked' ? 'selected' : '' ?>>zablokowany</option>
                </select>
            </div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label class="form-label" for="password"><?= $isNew ? 'Hasło' : 'Nowe hasło (opcjonalnie)' ?></label>
            <input type="password" id="password" name="password" class="form-control <?= $err('password') ?>" <?= $isNew ? 'required' : '' ?>>
            <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= e($errors['password'][0]) ?></div><?php endif; ?>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-warning fw-semibold"><i class="bi bi-save"></i> Zapisz</button>
            <a href="<?= e(url(ltrim($admin_prefix . '/users', '/'))) ?>" class="btn btn-outline-secondary">Anuluj</a>
        </div>
    </div>
</form>
