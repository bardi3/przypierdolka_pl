/* AJAX — odświeżenie cache historii (belka mod, panel admina) */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initStoryCacheRefresh();
    });

    function initStoryCacheRefresh() {
        document.querySelectorAll('[data-story-cache-refresh]').forEach(function (btn) {
            if (btn.dataset.cacheRefreshBound === '1') {
                return;
            }
            btn.dataset.cacheRefreshBound = '1';

            btn.addEventListener('click', function () {
                if (btn.classList.contains('is-loading')) {
                    return;
                }

                setRefreshState(btn, 'loading');

                var body = new URLSearchParams();
                body.set(btn.dataset.csrfName || '_csrf', btn.dataset.csrf || '');

                fetch(btn.dataset.refreshUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: body.toString()
                })
                    .then(function (res) {
                        return res.text().then(function (text) {
                            var json;
                            try {
                                json = JSON.parse(text);
                            } catch (err) {
                                throw new Error('Nieprawidłowa odpowiedź serwera.');
                            }
                            return { ok: res.ok, json: json };
                        });
                    })
                    .then(function (data) {
                        if (data.ok && data.json.success) {
                            setRefreshState(btn, 'ok');
                            if (data.json.image_url) {
                                updateStoryShareImage(data.json.image_url);
                            }
                            window.setTimeout(function () {
                                setRefreshState(btn, 'idle');
                            }, 2200);
                            return;
                        }

                        setRefreshState(btn, 'error');
                        window.setTimeout(function () {
                            setRefreshState(btn, 'idle');
                        }, 2600);
                    })
                    .catch(function () {
                        setRefreshState(btn, 'error');
                        window.setTimeout(function () {
                            setRefreshState(btn, 'idle');
                        }, 2600);
                    });
            });
        });
    }

    function setRefreshState(btn, state) {
        btn.classList.remove('is-loading', 'is-ok', 'is-error');
        if (state === 'loading') {
            btn.classList.add('is-loading');
        } else if (state === 'ok') {
            btn.classList.add('is-ok');
        } else if (state === 'error') {
            btn.classList.add('is-error');
        }

        btn.querySelectorAll('.story-cache-refresh__state').forEach(function (el) {
            el.hidden = true;
        });

        var active = btn.querySelector('.story-cache-refresh__state--' + state);
        if (active) {
            active.hidden = false;
        }
    }

    function updateStoryShareImage(imageUrl) {
        var img = document.querySelector('[data-story-share-image]');
        if (!img || !imageUrl) {
            return;
        }

        img.src = imageUrl;
        if (img.hasAttribute('srcset')) {
            img.removeAttribute('srcset');
        }
    }
})();
