<?php
/**
 * Szybki composer na tablicy — rozwijany formularz (AJAX + fallback POST).
 *
 * @var array<int, array<string, mixed>> $categories
 * @var \App\Core\Csrf $csrf
 */
$categories = $categories ?? [];
$defaultCategoryId = (int)($categories[0]['id'] ?? 0);
?>
<div class="wall-composer" data-wall-composer>
    <button type="button"
            class="wall-composer__trigger"
            data-wall-composer-trigger
            aria-expanded="false"
            aria-controls="wall-composer-panel"
            aria-label="Dodaj nową historię na tablicę">
        <?php $wallMe = $auth->user(); ?>
        <?= $view->renderPartial('layout/partials/user-avatar', [
            'path'  => ($wallMe !== null ? ($wallMe['avatar_path'] ?? null) : null),
            'size'  => 'md',
            'class' => 'wall-composer__avatar',
            'alt'   => '',
        ]) ?>
        <span class="wall-composer__placeholder">Co Ci się dziś wydarzyło? Opowiedz historię…</span>
        <span class="wall-composer__cta btn btn-accent btn-sm">+ Dodaj</span>
    </button>

    <form id="wall-composer-panel"
          class="wall-composer__panel"
          method="post"
          action="<?= e(url('/dodaj')) ?>"
          data-wall-composer-form
          hidden>
        <?= $csrf->field() ?>
        <input type="hidden" name="quick" value="1">
        <div class="wall-composer__hp visually-hidden">
            <label for="wall-composer-hp">Nie wypełniaj tego pola</label>
            <input type="text" id="wall-composer-hp" name="website" tabindex="-1" autocomplete="off">
        </div>

        <label class="visually-hidden" for="wall-composer-content">Treść historii</label>
        <textarea id="wall-composer-content"
                  name="content"
                  class="wall-composer__textarea"
                  rows="4"
                  maxlength="5000"
                  placeholder="Opowiedz, co Ci się przydarzyło…"
                  required></textarea>

        <fieldset class="wall-composer__categories">
            <legend class="wall-composer__categories-label">Kategoria</legend>
            <div class="wall-composer__category-list" role="radiogroup" aria-label="Kategoria historii">
                <?php foreach ($categories as $index => $cat): ?>
                    <?php $catId = (int)$cat['id']; ?>
                    <label class="wall-composer__category">
                        <input type="radio"
                               name="category_id"
                               value="<?= e($catId) ?>"
                               <?= ($index === 0 || $catId === $defaultCategoryId) ? 'checked' : '' ?>
                               required>
                        <span><?= e($cat['name']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <div class="wall-composer__feedback" data-wall-composer-feedback aria-live="polite"></div>

        <div class="wall-composer__actions">
            <button type="submit" class="btn btn-accent btn-sm" data-wall-composer-submit>Opublikuj</button>
            <button type="button" class="btn btn-ghost btn-sm" data-wall-composer-cancel>Anuluj</button>
            <a href="<?= e(url('/dodaj')) ?>" class="wall-composer__full-link">Pełny formularz</a>
        </div>
    </form>
</div>
