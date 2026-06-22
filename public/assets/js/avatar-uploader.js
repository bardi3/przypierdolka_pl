/**
 * Edytor awatara — wybór pliku, kadrowanie (Cropper.js), upload WebP.
 */
(function () {
    'use strict';

    function qs(sel, root) {
        return (root || document).querySelector(sel);
    }

    function setStatus(root, message, type) {
        var el = qs('[data-avatar-status]', root);
        if (!el) return;
        el.textContent = message || '';
        el.classList.toggle('is-error', type === 'error');
        el.classList.toggle('is-success', type === 'success');
    }

    function parseJsonResponse(res) {
        return res.text().then(function (text) {
            var trimmed = (text || '').trim();
            if (trimmed === '') {
                throw new Error('Pusta odpowiedź serwera.');
            }
            try {
                return JSON.parse(trimmed);
            } catch (e) {
                var start = trimmed.indexOf('{');
                var end = trimmed.lastIndexOf('}');
                if (start !== -1 && end > start) {
                    return JSON.parse(trimmed.slice(start, end + 1));
                }
                throw new Error('Nieprawidłowa odpowiedź serwera.');
            }
        });
    }

    function uploadAvatarFetch(url, body) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: body
        }).then(function (res) {
            return parseJsonResponse(res).then(function (data) {
                return { res: res, data: data };
            });
        });
    }

    function initAvatarEditor(root) {
        if (!root || root.dataset.avatarInit === '1') return;
        root.dataset.avatarInit = '1';

        var uploadUrl = root.dataset.avatarUploadUrl;
        var removeUrl = root.dataset.avatarRemoveUrl;
        var csrfToken = root.dataset.csrfToken;
        var csrfName = root.dataset.csrfName;
        var fileInput = qs('[data-avatar-file-input]', root);
        var preview = qs('[data-avatar-preview]', root);
        var removeBtn = qs('[data-avatar-remove]', root);
        var modal = qs('[data-avatar-crop-modal]', root);
        var cropImage = qs('[data-avatar-crop-image]', root);
        var zoomInput = qs('[data-avatar-crop-zoom]', root);
        var saveBtn = qs('[data-avatar-crop-save]', root);
        var resetBtn = qs('[data-avatar-crop-reset]', root);
        var maxBytes = parseInt(root.dataset.avatarMaxBytes, 10) || (8 * 1024 * 1024);
        var maxMb = Math.round(maxBytes / (1024 * 1024));
        var cropper = null;
        var objectUrl = null;
        var minZoomRatio = 0;
        var maxZoomRatio = 1;
        var isZoomSyncing = false;

        function syncZoomBounds() {
            if (!cropper) return;
            var imageData = cropper.getImageData();
            minZoomRatio = imageData.width / imageData.naturalWidth;
            maxZoomRatio = minZoomRatio * 3;
        }

        function ratioToSlider(ratio) {
            if (maxZoomRatio <= minZoomRatio) return 0;
            var pct = ((ratio - minZoomRatio) / (maxZoomRatio - minZoomRatio)) * 100;
            return Math.max(0, Math.min(100, Math.round(pct)));
        }

        function sliderToRatio(val) {
            var pct = (parseInt(val, 10) || 0) / 100;
            return minZoomRatio + pct * (maxZoomRatio - minZoomRatio);
        }

        function updateZoomSlider(ratio) {
            if (!zoomInput || isZoomSyncing) return;
            zoomInput.value = String(ratioToSlider(ratio));
        }

        function applyZoomFromSlider() {
            if (!cropper || !zoomInput) return;
            isZoomSyncing = true;
            cropper.zoomTo(sliderToRatio(zoomInput.value));
            isZoomSyncing = false;
        }

        function resetCrop() {
            if (!cropper) return;
            isZoomSyncing = true;
            cropper.reset();
            syncZoomBounds();
            if (zoomInput) {
                zoomInput.value = '0';
            }
            isZoomSyncing = false;
        }

        function closeModal() {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }
            if (cropImage) {
                cropImage.removeAttribute('src');
                cropImage.onload = null;
            }
            if (modal) {
                modal.hidden = true;
            }
            if (fileInput) {
                fileInput.value = '';
            }
            if (zoomInput) {
                zoomInput.value = '0';
            }
            isZoomSyncing = false;
        }

        function initCropper() {
            if (!cropImage || typeof Cropper === 'undefined') {
                setStatus(root, 'Edytor kadrowania nie jest dostępny.', 'error');
                return;
            }

            if (cropper) {
                cropper.destroy();
                cropper = null;
            }

            cropper = new Cropper(cropImage, {
                aspectRatio: 1,
                viewMode: 2,
                dragMode: 'move',
                autoCropArea: 1,
                responsive: true,
                background: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: false,
                cropBoxResizable: false,
                toggleDragModeOnDblclick: false,
                ready: function () {
                    syncZoomBounds();
                    if (zoomInput) {
                        zoomInput.value = '0';
                    }
                },
                zoom: function (event) {
                    if (isZoomSyncing) return;
                    updateZoomSlider(event.detail.ratio);
                }
            });
        }

        function openModal(file) {
            if (!modal || !cropImage || typeof Cropper === 'undefined') {
                setStatus(root, 'Edytor kadrowania nie jest dostępny.', 'error');
                return;
            }

            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }
            if (cropImage) {
                cropImage.removeAttribute('src');
                cropImage.onload = null;
            }
            if (zoomInput) {
                zoomInput.value = '0';
            }
            isZoomSyncing = false;

            modal.hidden = false;
            objectUrl = URL.createObjectURL(file);
            cropImage.onload = function () {
                cropImage.onload = null;
                initCropper();
            };
            cropImage.src = objectUrl;
        }

        root.querySelectorAll('[data-avatar-crop-close]').forEach(function (btn) {
            btn.addEventListener('click', closeModal);
        });

        if (zoomInput) {
            zoomInput.addEventListener('input', applyZoomFromSlider);
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', resetCrop);
        }

        if (fileInput) {
            fileInput.addEventListener('change', function () {
                var file = fileInput.files && fileInput.files[0];
                if (!file) return;

                if (file.size > maxBytes) {
                    setStatus(root, 'Plik jest zbyt duży (max ' + maxMb + ' MB).', 'error');
                    fileInput.value = '';
                    return;
                }

                if (!/^image\/(jpeg|png|webp)$/i.test(file.type)) {
                    setStatus(root, 'Dozwolone formaty: JPG, PNG, WebP.', 'error');
                    fileInput.value = '';
                    return;
                }

                setStatus(root, '');
                openModal(file);
            });
        }

        if (saveBtn) {
            saveBtn.addEventListener('click', function () {
                if (!cropper) return;

                saveBtn.disabled = true;
                setStatus(root, 'Zapisywanie…');

                var canvas = cropper.getCroppedCanvas({
                    width: 512,
                    height: 512,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high'
                });

                if (!canvas) {
                    saveBtn.disabled = false;
                    setStatus(root, 'Nie udało się przygotować obrazu.', 'error');
                    return;
                }

                canvas.toBlob(function (blob) {
                    if (!blob) {
                        saveBtn.disabled = false;
                        setStatus(root, 'Nie udało się przygotować obrazu.', 'error');
                        return;
                    }

                    var body = new FormData();
                    body.append('avatar', blob, 'avatar.jpg');
                    body.append(csrfName, csrfToken);

                    uploadAvatarFetch(uploadUrl, body)
                        .then(function (result) {
                            saveBtn.disabled = false;
                            if (!result.res.ok || !result.data.success) {
                                setStatus(root, result.data.error || 'Nie udało się zapisać awatara.', 'error');
                                return;
                            }

                            try {
                                closeModal();
                                if (preview && result.data.url) {
                                    preview.innerHTML = '<span class="pp-avatar pp-avatar--xl account-avatar-editor__avatar">'
                                        + '<img src="' + result.data.url + '" alt="Twoje zdjęcie profilowe" class="pp-avatar__img" width="256" height="256" loading="lazy">'
                                        + '</span>';
                                }

                                if (!removeBtn) {
                                    var actions = qs('.account-avatar-editor__actions', root);
                                    if (actions) {
                                        var btn = document.createElement('button');
                                        btn.type = 'button';
                                        btn.className = 'btn btn-ghost btn-sm';
                                        btn.setAttribute('data-avatar-remove', '');
                                        btn.textContent = 'Usuń zdjęcie';
                                        actions.appendChild(btn);
                                        btn.addEventListener('click', onRemove);
                                        removeBtn = btn;
                                    }
                                }

                                setStatus(root, result.data.message || 'Zapisano.', 'success');
                                updateNavAvatar(result.data.url);
                            } catch (err) {
                                setStatus(root, 'Zapisano. Odśwież stronę, jeśli podgląd się nie zaktualizował.', 'success');
                            }
                        })
                        .catch(function (err) {
                            saveBtn.disabled = false;
                            setStatus(root, (err && err.message) ? err.message : 'Błąd połączenia. Spróbuj ponownie.', 'error');
                        });
                }, 'image/jpeg', 0.92);
            });
        }

        function updateNavAvatar(url) {
            var navAvatar = document.querySelector('.nav-profile__avatar .pp-avatar__img, .nav-profile__avatar.pp-avatar');
            if (!navAvatar || !url) return;
            if (navAvatar.tagName === 'IMG') {
                navAvatar.src = url;
                return;
            }
            var img = document.createElement('img');
            img.src = url;
            img.alt = '';
            img.className = 'pp-avatar__img';
            img.width = 28;
            img.height = 28;
            img.loading = 'lazy';
            navAvatar.innerHTML = '';
            navAvatar.appendChild(img);
        }

        function onRemove() {
            if (!confirm('Usunąć zdjęcie profilowe?')) return;

            var body = new FormData();
            body.append(csrfName, csrfToken);

            uploadAvatarFetch(removeUrl, body)
                .then(function (result) {
                    if (!result.res.ok || !result.data.success) {
                        setStatus(root, result.data.error || 'Nie udało się usunąć awatara.', 'error');
                        return;
                    }

                    setStatus(root, result.data.message || 'Usunięto.', 'success');
                    window.location.reload();
                })
                .catch(function () {
                    setStatus(root, 'Błąd połączenia.', 'error');
                });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', onRemove);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-avatar-upload-url]').forEach(initAvatarEditor);
    });
})();
