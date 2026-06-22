<?php
/**
 * @var array<int, array<string, mixed>> $categories
 * @var array<int, array<string, mixed>> $trendingStories
 * @var array<string, mixed> $old
 * @var array<string, array<int,string>> $errors
 * @var \App\Core\Auth $auth
 * @var \App\Core\Csrf $csrf
 * @var string $formAction
 */
$old = $old ?? [];
$errors = $errors ?? [];
$trendingStories = $trendingStories ?? [];
$formAction = $formAction ?? '/dodaj';
$sidebarActive = 'add';
$err = static fn (string $f): string => isset($errors[$f]) ? 'is-invalid' : '';
?>
<div class="page-layout story-create">
    <div class="page-main">
        <header class="page-header">
            <h1 class="page-title">Dodaj historię</h1>
            <p class="page-subtitle">Opowiedz coś śmiesznego — społeczność oceni gwiazdkami.</p>
        </header>

        <?php if (!$auth->check()): ?>
            <?= $view->renderPartial('layout/partials/alert', [
                'type' => 'info',
                'html' => 'Dodajesz jako gość — historia trafi do moderacji (limit: 2 historie/godzinę). '
                    . '<a href="' . e(url('/logowanie')) . '">Zaloguj się</a>, aby publikować od razu.',
            ]) ?>
        <?php endif; ?>

        <form method="post" action="<?= e(url($formAction)) ?>" class="card">
            <div class="card-body">
                <?= $csrf->field() ?>
                <div style="position:absolute;left:-9999px" aria-hidden="true">
                    <label>Nie wypełniaj <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="title">Tytuł</label>
                    <input type="text" id="title" name="title" maxlength="200"
                           class="form-control <?= $err('title') ?>" value="<?= old($old, 'title') ?>" required>
                    <?php if (isset($errors['title'])): ?><div class="invalid-feedback d-block"><?= e($errors['title'][0]) ?></div><?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="category_id">Kategoria</label>
                    <select id="category_id" name="category_id" class="form-select <?= $err('category_id') ?>" required>
                        <option value="">— wybierz —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat['id']) ?>" <?= (int)($old['category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['category_id'])): ?><div class="invalid-feedback d-block"><?= e($errors['category_id'][0]) ?></div><?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="content">Treść</label>
                    <textarea id="content" name="content" rows="8" maxlength="5000"
                              class="form-control <?= $err('content') ?>" required><?= old($old, 'content') ?></textarea>
                    <?php if (isset($errors['content'])): ?><div class="invalid-feedback d-block"><?= e($errors['content'][0]) ?></div><?php endif; ?>
                    <div class="form-text">Od 10 do 5000 znaków.</div>
                </div>

                <?php if (!$auth->check()): ?>
                    <div class="mb-3">
                        <label class="form-label" for="author_name_guest">Podpis (opcjonalnie)</label>
                        <input type="text" id="author_name_guest" name="author_name_guest" maxlength="60"
                               class="form-control" value="<?= old($old, 'author_name_guest') ?>" placeholder="Anonim">
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-accent">Wyślij historię</button>
            </div>
        </form>
    </div>

    <?= $view->renderPartial('layout/partials/wall-sidebar', get_defined_vars()) ?>
</div>
