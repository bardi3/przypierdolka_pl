<?php
/**
 * Edytor zdjęcia profilowego (cropper + upload AJAX).
 *
 * @var array<string, mixed> $user
 * @var bool $avatarSupported
 * @var \App\Core\Csrf $csrf
 */
$avatarPath = !empty($user['avatar_path']) ? (string)$user['avatar_path'] : null;
$avatarSupported = $avatarSupported ?? true;
$avatarMaxUpload = (int)\App\Core\Config::get('app.avatars.max_upload', 8 * 1024 * 1024);
$avatarMaxUploadMb = (int)round($avatarMaxUpload / (1024 * 1024));
?>
<section class="card account-avatar-card mb-4" id="account-avatar-editor"
         data-avatar-upload-url="<?= e(url('/ajax/account/avatar')) ?>"
         data-avatar-remove-url="<?= e(url('/ajax/account/avatar/usun')) ?>"
         data-avatar-max-bytes="<?= e($avatarMaxUpload) ?>"
         data-csrf-token="<?= e($csrf->token()) ?>"
         data-csrf-name="<?= e($csrf->tokenName()) ?>">
    <div class="card-body">
        <h2 class="account-avatar-card__title">Zdjęcie profilowe</h2>
        <p class="account-avatar-card__lead text-muted small">
            Widoczne w nawigacji, na tablicy i w profilu. Zapisujemy jako lekki WebP (256×256 px).
            Maks. <?= e($avatarMaxUploadMb) ?> MB na plik wejściowy.
        </p>

        <?php if (!$avatarSupported): ?>
            <?= $view->renderPartial('layout/partials/alert', [
                'type'    => 'warning',
                'message' => 'Serwer nie obsługuje przetwarzania obrazów (wymagane rozszerzenie GD z WebP).',
            ]) ?>
        <?php else: ?>
            <div class="account-avatar-editor">
                <div class="account-avatar-editor__preview" data-avatar-preview>
                    <?= $view->renderPartial('layout/partials/user-avatar', [
                        'path'  => $avatarPath,
                        'size'  => 'xl',
                        'alt'   => 'Twoje zdjęcie profilowe',
                        'class' => 'account-avatar-editor__avatar',
                    ]) ?>
                </div>

                <div class="account-avatar-editor__actions">
                    <label class="btn btn-outline btn-sm">
                        Wybierz zdjęcie
                        <input type="file"
                               class="visually-hidden"
                               accept="image/jpeg,image/png,image/webp"
                               data-avatar-file-input>
                    </label>
                    <?php if ($avatarPath !== null): ?>
                        <button type="button" class="btn btn-ghost btn-sm" data-avatar-remove>
                            Usuń zdjęcie
                        </button>
                    <?php endif; ?>
                </div>

                <p class="account-avatar-editor__hint text-muted small" data-avatar-status aria-live="polite"></p>
            </div>

            <div class="avatar-crop-modal" data-avatar-crop-modal hidden>
                <div class="avatar-crop-modal__backdrop" data-avatar-crop-close></div>
                <div class="avatar-crop-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="avatar-crop-title">
                    <header class="avatar-crop-modal__head">
                        <h3 id="avatar-crop-title" class="avatar-crop-modal__title">Kadruj zdjęcie</h3>
                        <button type="button" class="avatar-crop-modal__close" data-avatar-crop-close aria-label="Zamknij">&times;</button>
                    </header>
                    <div class="avatar-crop-modal__body">
                        <div class="avatar-crop-modal__stage">
                            <img src="" alt="" class="avatar-crop-modal__image" data-avatar-crop-image>
                        </div>
                        <label class="avatar-crop-modal__zoom-label" for="avatar-crop-zoom">
                            Powiększenie
                            <input type="range"
                                   id="avatar-crop-zoom"
                                   class="avatar-crop-modal__zoom"
                                   min="0"
                                   max="100"
                                   step="1"
                                   value="0"
                                   data-avatar-crop-zoom>
                        </label>
                        <p class="avatar-crop-modal__hint text-muted small">
                            Przeciągnij zdjęcie, aby je ustawić. Suwak zmienia powiększenie.
                        </p>
                    </div>
                    <footer class="avatar-crop-modal__foot">
                        <button type="button" class="btn btn-ghost btn-sm" data-avatar-crop-close>Anuluj</button>
                        <button type="button" class="btn btn-ghost btn-sm" data-avatar-crop-reset>Resetuj kadr</button>
                        <button type="button" class="btn btn-accent btn-sm" data-avatar-crop-save>Zapisz awatar</button>
                    </footer>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js" defer></script>
<script src="<?= e(asset('js/avatar-uploader.js')) ?>" defer></script>
