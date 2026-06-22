<?php
/**
 * Formularz edycji inline na stronie historii.
 *
 * @var array<string, mixed> $story
 * @var array<int, array<string, mixed>> $categories
 * @var bool $canModerate
 * @var \App\Core\Csrf $csrf
 */
use App\Models\Story;

$statuses = [
    Story::STATUS_PUBLISHED => 'Opublikowana',
    Story::STATUS_PENDING   => 'Oczekuje',
    Story::STATUS_REJECTED  => 'Odrzucona',
];
?>
<form class="story-inline-edit"
      data-story-edit-form
      method="post"
      action="<?= e(url('/ajax/story/' . (int)$story['id'] . '/edit')) ?>"
      hidden>
    <?= $csrf->field() ?>

    <div class="story-inline-edit__field">
        <label class="story-inline-edit__label" for="story-edit-title">Tytuł</label>
        <input type="text"
               id="story-edit-title"
               name="title"
               class="story-inline-edit__input"
               maxlength="200"
               value="<?= e($story['title']) ?>"
               required>
    </div>

    <div class="story-inline-edit__row">
        <div class="story-inline-edit__field story-inline-edit__field--grow">
            <label class="story-inline-edit__label" for="story-edit-category">Kategoria</label>
            <select id="story-edit-category" name="category_id" class="story-inline-edit__select" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat['id']) ?>"
                        <?= (int)$story['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($canModerate ?? false): ?>
            <div class="story-inline-edit__field story-inline-edit__field--status">
                <label class="story-inline-edit__label" for="story-edit-status">Status</label>
                <select id="story-edit-status" name="status" class="story-inline-edit__select" required>
                    <?php foreach ($statuses as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= $story['status'] === $val ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
    </div>

    <div class="story-inline-edit__field">
        <label class="story-inline-edit__label" for="story-edit-content">Treść</label>
        <textarea id="story-edit-content"
                  name="content"
                  class="story-inline-edit__textarea"
                  rows="10"
                  maxlength="5000"
                  required><?= e($story['content']) ?></textarea>
    </div>

    <div class="story-inline-edit__feedback" data-story-edit-feedback aria-live="polite"></div>

    <div class="story-inline-edit__actions">
        <button type="submit" class="btn btn-accent btn-sm" data-story-edit-save>Zapisz</button>
        <button type="button" class="btn btn-ghost btn-sm" data-story-edit-cancel>Anuluj</button>
    </div>
</form>
