<?php
/**
 * @var array<string, mixed>|null $entry
 * @var array<string, array<int,string>> $errors
 * @var string $admin_prefix
 * @var \App\Core\Csrf $csrf
 */
$errors = $errors ?? [];
$isNew = $entry === null || !isset($entry['id']);
$err = static fn (string $f): string => isset($errors[$f]) ? 'is-invalid' : '';
$action = $isNew
    ? url(ltrim($admin_prefix . '/llms/add', '/'))
    : url(ltrim($admin_prefix . '/llms/edit/' . $entry['id'], '/'));

$sections = ['Serwis', 'Rankingi', 'Kategorie', 'Historie', 'Optional', 'Inne'];
$currentSection = (string)($entry['section'] ?? 'Serwis');
?>
<h1 class="h3 mb-4"><?= $isNew ? 'Nowy wpis llms.txt' : 'Edycja wpisu llms.txt' ?></h1>

<form method="post" action="<?= e($action) ?>" class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <?= $csrf->field() ?>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="section">Sekcja (nagłówek H2)</label>
                <input type="text" id="section" name="section" list="llms-sections" maxlength="80"
                       class="form-control <?= $err('section') ?>" value="<?= e($currentSection) ?>" required>
                <datalist id="llms-sections">
                    <?php foreach ($sections as $section): ?>
                        <option value="<?= e($section) ?>">
                    <?php endforeach; ?>
                </datalist>
                <div class="form-text">Wpis z opcją „sekcja opcjonalna” trafi do ## Optional.</div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="sort_order">Kolejność w sekcji</label>
                <input type="number" id="sort_order" name="sort_order" min="0" max="9999"
                       class="form-control" value="<?= e((string)($entry['sort_order'] ?? 0)) ?>">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="title">Tytuł linku</label>
            <input type="text" id="title" name="title" maxlength="200"
                   class="form-control <?= $err('title') ?>" value="<?= e($entry['title'] ?? '') ?>" required>
            <?php if (isset($errors['title'])): ?><div class="invalid-feedback d-block"><?= e($errors['title'][0]) ?></div><?php endif; ?>
        </div>

        <div class="mb-3">
            <label class="form-label" for="url">URL</label>
            <input type="text" id="url" name="url" maxlength="500"
                   class="form-control <?= $err('url') ?>" value="<?= e($entry['url'] ?? '') ?>"
                   placeholder="/historia/przyklad lub https://…" required>
            <?php if (isset($errors['url'])): ?><div class="invalid-feedback d-block"><?= e($errors['url'][0]) ?></div><?php endif; ?>
        </div>

        <div class="mb-3">
            <label class="form-label" for="description">Opis (po dwukropku w liście)</label>
            <textarea id="description" name="description" rows="3" maxlength="500"
                      class="form-control <?= $err('description') ?>"><?= e($entry['description'] ?? '') ?></textarea>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_optional" name="is_optional" value="1"
                           <?= (int)($entry['is_optional'] ?? 0) === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_optional">Sekcja opcjonalna (## Optional)</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                           <?= ($isNew || (int)($entry['is_active'] ?? 0) === 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Aktywny w pliku</label>
                </div>
            </div>
        </div>

        <?php if (!$isNew && (int)($entry['is_system'] ?? 0) === 1): ?>
            <p class="text-muted small">Wpis systemowy — synchronizacja może zaktualizować tytuł i URL. Opis zostaje, chyba że zaznaczysz nadpisanie przy sync.</p>
        <?php endif; ?>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-warning fw-semibold"><i class="bi bi-save"></i> Zapisz</button>
            <a href="<?= e(url(ltrim($admin_prefix . '/llms', '/'))) ?>" class="btn btn-outline-secondary">Anuluj</a>
        </div>
    </div>
</form>
