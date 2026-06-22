<?php
/**
 * @var array<string, mixed> $story
 * @var array<int, array<string, mixed>> $categories
 * @var array<string, array<int,string>> $errors
 * @var string $admin_prefix
 * @var \App\Core\Csrf $csrf
 */
$errors = $errors ?? [];
$err = static fn (string $f): string => isset($errors[$f]) ? 'is-invalid' : '';
$statuses = ['published' => 'Opublikowana', 'pending' => 'Oczekuje', 'rejected' => 'Odrzucona'];
?>
<h1 class="h3 mb-4 d-flex flex-wrap align-items-center gap-2">
    Edycja historii #<?= e($story['id']) ?>
    <?= $view->renderPartial('admin/stories/_cache-refresh-btn', [
        'storyId'      => (int)$story['id'],
        'admin_prefix' => $admin_prefix,
        'csrf'         => $csrf,
        'variant'      => 'admin',
    ]) ?>
</h1>

<form method="post" action="<?= e(url(ltrim($admin_prefix . '/stories/edit/' . $story['id'], '/'))) ?>" class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <?= $csrf->field() ?>
        <div class="mb-3">
            <label class="form-label" for="title">Tytuł</label>
            <input type="text" id="title" name="title" maxlength="200" class="form-control <?= $err('title') ?>" value="<?= e($story['title']) ?>" required>
            <?php if (isset($errors['title'])): ?><div class="invalid-feedback"><?= e($errors['title'][0]) ?></div><?php endif; ?>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="category_id">Kategoria</label>
                <select id="category_id" name="category_id" class="form-select <?= $err('category_id') ?>" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat['id']) ?>" <?= (int)$story['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="status">Status</label>
                <select id="status" name="status" class="form-select">
                    <?php foreach ($statuses as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= $story['status'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="content">Treść</label>
            <textarea id="content" name="content" rows="10" maxlength="5000" class="form-control <?= $err('content') ?>" required><?= e($story['content']) ?></textarea>
            <?php if (isset($errors['content'])): ?><div class="invalid-feedback"><?= e($errors['content'][0]) ?></div><?php endif; ?>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-warning fw-semibold"><i class="bi bi-save"></i> Zapisz</button>
            <a href="<?= e(url(ltrim($admin_prefix . '/stories', '/'))) ?>" class="btn btn-outline-secondary">Anuluj</a>
        </div>
    </div>
</form>
