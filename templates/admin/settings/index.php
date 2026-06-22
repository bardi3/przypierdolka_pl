<?php
/**
 * @var array<string, string> $settings
 * @var array<int, string> $keys
 * @var array<string, array<int, string>>|null $errors
 * @var string $admin_prefix
 * @var \App\Core\Csrf $csrf
 */
$labels = [
    'site_title'                 => 'Tytuł serwisu (SEO)',
    'site_description'           => 'Opis serwisu (meta description)',
    'meta_keywords'              => 'Słowa kluczowe',
    'stories_require_moderation' => 'Wymagaj moderacji (1/0)',
    'social_facebook'            => 'Facebook URL',
    'social_instagram'           => 'Instagram URL',
];
?>
<h1 class="h3 mb-4">Ustawienia</h1>

<form method="post" action="<?= e(url(ltrim($admin_prefix . '/settings', '/'))) ?>" class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <?= $csrf->field() ?>
        <?php foreach ($keys as $key): ?>
            <div class="mb-3">
                <label class="form-label" for="<?= e($key) ?>"><?= e($labels[$key] ?? $key) ?></label>
                <?php if ($key === 'site_description'): ?>
                    <textarea id="<?= e($key) ?>" name="<?= e($key) ?>" rows="2" class="form-control"><?= e($settings[$key] ?? '') ?></textarea>
                <?php else: ?>
                    <input type="text" id="<?= e($key) ?>" name="<?= e($key) ?>" class="form-control" value="<?= e($settings[$key] ?? '') ?>">
                <?php endif; ?>
                <?php if (!empty($errors[$key][0])): ?>
                    <div class="form-text text-danger"><?= e($errors[$key][0]) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-warning fw-semibold"><i class="bi bi-save"></i> Zapisz ustawienia</button>
    </div>
</form>
