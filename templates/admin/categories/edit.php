<?php
/**
 * @var array<string, mixed>|null $category
 * @var array<string, array<int,string>> $errors
 * @var string $admin_prefix
 * @var \App\Core\Csrf $csrf
 */
$errors = $errors ?? [];
$isNew = $category === null || !isset($category['id']);
$err = static fn (string $f): string => isset($errors[$f]) ? 'is-invalid' : '';
$action = $isNew
    ? url(ltrim($admin_prefix . '/categories/add', '/'))
    : url(ltrim($admin_prefix . '/categories/edit/' . $category['id'], '/'));
?>
<h1 class="h3 mb-4"><?= $isNew ? 'Nowa kategoria' : 'Edycja kategorii' ?></h1>

<form method="post" action="<?= e($action) ?>" class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <?= $csrf->field() ?>
        <div class="mb-3">
            <label class="form-label" for="name">Nazwa</label>
            <input type="text" id="name" name="name" maxlength="60" class="form-control <?= $err('name') ?>" value="<?= e($category['name'] ?? '') ?>" required>
            <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name'][0]) ?></div><?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label" for="slug">Slug</label>
            <input type="text" id="slug" name="slug" maxlength="80" class="form-control <?= $err('slug') ?>" value="<?= e($category['slug'] ?? '') ?>" placeholder="auto z nazwy">
            <?php if (isset($errors['slug'])): ?><div class="invalid-feedback"><?= e($errors['slug'][0]) ?></div><?php endif; ?>
            <div class="form-text">Zostaw puste, aby wygenerować automatycznie.</div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="description">Opis</label>
            <textarea id="description" name="description" rows="3" class="form-control"><?= e($category['description'] ?? '') ?></textarea>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-warning fw-semibold"><i class="bi bi-save"></i> Zapisz</button>
            <a href="<?= e(url(ltrim($admin_prefix . '/categories', '/'))) ?>" class="btn btn-outline-secondary">Anuluj</a>
        </div>
    </div>
</form>
