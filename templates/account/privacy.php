<?php
/**
 * @var array<string, mixed> $user
 * @var array<int, string> $options
 * @var \App\Services\ProfilePrivacyService $labels
 * @var string $accountNav
 * @var \App\Core\Csrf $csrf
 */
$currentProfile = (string)($user['profile_visibility'] ?? 'public');
$currentStories = (string)($user['stories_visibility'] ?? 'public');
$currentFriends = (string)($user['friends_list_visibility'] ?? 'friends');
?>
<?= $view->renderPartial('account/_page-open', ['accountNav' => $accountNav]) ?>

        <header class="page-header">
            <h1 class="page-title">Prywatność</h1>
            <p class="page-subtitle">Kontroluj, kto widzi Twój profil, historie i listę znajomych.</p>
        </header>

        <form method="post" action="<?= e(url('/konto/prywatnosc')) ?>" class="account-form card">
            <div class="card-body">
                <?= $csrf->field() ?>

                <div class="mb-3">
                    <label class="form-label" for="profile_visibility">Widoczność profilu</label>
                    <select id="profile_visibility" name="profile_visibility" class="form-select">
                        <?php foreach ($options as $opt): ?>
                            <option value="<?= e($opt) ?>" <?= $currentProfile === $opt ? 'selected' : '' ?>>
                                <?= e($labels->visibilityLabel($opt)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-text">Kto może zobaczyć Twoją tablicę, bio i statystyki.</p>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="stories_visibility">Widoczność historii</label>
                    <select id="stories_visibility" name="stories_visibility" class="form-select">
                        <?php foreach ($options as $opt): ?>
                            <option value="<?= e($opt) ?>" <?= $currentStories === $opt ? 'selected' : '' ?>>
                                <?= e($labels->visibilityLabel($opt)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-text">Kto widzi wpisy na tablicy (opublikowane historie).</p>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="friends_list_visibility">Lista znajomych</label>
                    <select id="friends_list_visibility" name="friends_list_visibility" class="form-select">
                        <?php foreach ($options as $opt): ?>
                            <option value="<?= e($opt) ?>" <?= $currentFriends === $opt ? 'selected' : '' ?>>
                                <?= e($labels->visibilityLabel($opt)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-text">Kto widzi listę Twoich znajomych na profilu.</p>
                </div>

                <button type="submit" class="btn btn-accent">Zapisz ustawienia</button>
            </div>
        </form>

<?= $view->renderPartial('account/_page-close', get_defined_vars()) ?>
