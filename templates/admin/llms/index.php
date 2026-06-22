<?php
/**
 * @var array<int, array<string, mixed>> $entries
 * @var array{summary:string, body:string, stories_limit:string} $meta
 * @var string $previewUrl
 * @var array<string, array<int,string>>|null $errors
 * @var string $admin_prefix
 * @var \App\Core\Csrf $csrf
 */
$errors = $errors ?? [];
$adminUrl = static fn (string $p): string => url(ltrim($admin_prefix . '/' . ltrim($p, '/'), '/'));
$err = static fn (string $f): string => isset($errors[$f]) ? 'is-invalid' : '';

$grouped = [];
foreach ($entries as $entry) {
    $section = (int)($entry['is_optional'] ?? 0) === 1 ? 'Optional' : (string)$entry['section'];
    $grouped[$section][] = $entry;
}
?>
<div class="admin-page-head">
    <h1 class="h3">llms.txt</h1>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-sm btn-outline-secondary" href="<?= e($previewUrl) ?>" target="_blank" rel="noopener">
            <i class="bi bi-box-arrow-up-right"></i> Podgląd /llms.txt
        </a>
        <a class="btn btn-sm btn-warning" href="<?= e($adminUrl('llms/add')) ?>">
            <i class="bi bi-plus-lg"></i> Dodaj wpis
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <form method="post" action="<?= e($adminUrl('llms/meta')) ?>" class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Nagłówek pliku</div>
            <div class="card-body p-4">
                <?= $csrf->field() ?>
                <div class="mb-3">
                    <label class="form-label" for="llms_summary">Podsumowanie (blockquote)</label>
                    <textarea id="llms_summary" name="llms_summary" rows="3" maxlength="500"
                              class="form-control <?= $err('llms_summary') ?>" required><?= e($meta['summary']) ?></textarea>
                    <?php if (isset($errors['llms_summary'])): ?>
                        <div class="invalid-feedback d-block"><?= e($errors['llms_summary'][0]) ?></div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="llms_body">Wstęp (akapity przed sekcjami)</label>
                    <textarea id="llms_body" name="llms_body" rows="5" maxlength="3000"
                              class="form-control <?= $err('llms_body') ?>"><?= e($meta['body']) ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="llms_stories_limit">Limit historii w synchronizacji</label>
                    <input type="number" id="llms_stories_limit" name="llms_stories_limit" min="1" max="2000"
                           class="form-control <?= $err('llms_stories_limit') ?>" value="<?= e($meta['stories_limit']) ?>" required>
                    <div class="form-text">Ile opublikowanych historii dodać przy synchronizacji (max 2000).</div>
                </div>
                <button type="submit" class="btn btn-warning btn-sm fw-semibold">
                    <i class="bi bi-save"></i> Zapisz nagłówek
                </button>
            </div>
        </form>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Synchronizacja z serwisem</div>
            <div class="card-body p-4">
                <p class="text-muted small">
                    Uzupełnia wpisy systemowe: strony serwisu, kategorie, rankingi i opublikowane historie.
                    Wpisy ręczne pozostają bez zmian.
                </p>
                <form method="post" action="<?= e($adminUrl('llms/sync')) ?>">
                    <?= $csrf->field() ?>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="overwrite_descriptions"
                               name="overwrite_descriptions">
                        <label class="form-check-label" for="overwrite_descriptions">
                            Nadpisz opisy wpisów systemowych
                        </label>
                    </div>
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-repeat"></i> Synchronizuj teraz
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php foreach ($grouped as $sectionName => $sectionEntries): ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
            <span><?= e($sectionName) ?></span>
            <span class="badge text-bg-secondary"><?= e(count($sectionEntries)) ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tytuł</th>
                        <th>URL</th>
                        <th>Opis</th>
                        <th>Kolejność</th>
                        <th>Status</th>
                        <th class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($sectionEntries as $entry): ?>
                    <tr class="<?= (int)($entry['is_active'] ?? 0) === 0 ? 'table-secondary' : '' ?>">
                        <td class="fw-semibold">
                            <?= e($entry['title']) ?>
                            <?php if ((int)($entry['is_system'] ?? 0) === 1): ?>
                                <span class="badge text-bg-light border ms-1">system</span>
                            <?php endif; ?>
                        </td>
                        <td><code class="small"><?= e($entry['url']) ?></code></td>
                        <td><small><?= e(mb_strimwidth((string)($entry['description'] ?? ''), 0, 80, '…')) ?></small></td>
                        <td><?= e($entry['sort_order']) ?></td>
                        <td>
                            <?php if ((int)($entry['is_active'] ?? 0) === 1): ?>
                                <span class="badge text-bg-success">aktywny</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">wyłączony</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a class="btn btn-outline-secondary" href="<?= e($adminUrl('llms/edit/' . $entry['id'])) ?>" title="Edytuj">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="post" action="<?= e($adminUrl('llms/delete/' . $entry['id'])) ?>"
                                      onsubmit="return confirm('Usunąć wpis?');">
                                    <?= $csrf->field() ?>
                                    <button type="submit" class="btn btn-outline-danger" title="Usuń">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>

<?php if (empty($entries)): ?>
    <div class="alert alert-info">Brak wpisów. Użyj synchronizacji, aby wczytać zawartość serwisu.</div>
<?php endif; ?>
