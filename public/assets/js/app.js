/* przypierdolka.pl — vanilla JS, zero zależności */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initNavigation();
        initFlashDismiss();
        initLazyLoading();
        initRatingWidgets();
        initShareButtons();
        initStoryNavigation();
        initFeedInfiniteScroll();
        initSiteSearch();
        initWallComposer();
        initStoryInlineEdit();
        initProfileAvatarMenu();
    });

    function ajaxStoryUrl() {
        return document.body.dataset.ajaxStoryUrl || '/ajax/story';
    }

    function ajaxSearchUrl() {
        return document.body.dataset.ajaxSearchUrl || '/ajax/search';
    }

    function ajaxFeedUrl() {
        return document.body.dataset.ajaxFeedUrl || '/ajax/feed';
    }

    function ajaxRateUrl() {
        return document.body.dataset.ajaxRateUrl || '/ajax/rate';
    }

    function initNavigation() {
        var toggle = document.querySelector('.nav-toggle');
        var nav = document.getElementById('mainNav');
        if (toggle && nav) {
            function setNavOpen(open) {
                nav.classList.toggle('is-open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                document.body.classList.toggle('nav-open', open);
            }

            toggle.addEventListener('click', function () {
                setNavOpen(!nav.classList.contains('is-open'));
            });

            nav.querySelectorAll('a, button[type="submit"]').forEach(function (el) {
                el.addEventListener('click', function (e) {
                    if (el.closest('.site-subnav')) {
                        return;
                    }
                    setNavOpen(false);
                });
            });

            document.addEventListener('click', function (e) {
                if (!nav.classList.contains('is-open')) {
                    return;
                }
                if (!nav.contains(e.target) && !toggle.contains(e.target)) {
                    setNavOpen(false);
                }
            });

            window.addEventListener('resize', function () {
                if (window.matchMedia('(min-width: 961px)').matches) {
                    setNavOpen(false);
                }
            });
        }

        document.querySelectorAll('.nav-dropdown').forEach(function (wrap) {
            var btn = wrap.querySelector('.nav-dropdown__toggle');
            var menu = wrap.querySelector('.nav-dropdown__menu');
            if (!btn || !menu) {
                return;
            }
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var open = menu.hasAttribute('hidden');
                closeAllDropdowns();
                if (open) {
                    menu.removeAttribute('hidden');
                    btn.setAttribute('aria-expanded', 'true');
                }
            });
        });

        document.addEventListener('click', closeAllDropdowns);
    }

    function closeAllDropdowns() {
        document.querySelectorAll('.nav-dropdown__menu').forEach(function (menu) {
            menu.setAttribute('hidden', 'hidden');
        });
        document.querySelectorAll('.nav-dropdown__toggle').forEach(function (btn) {
            btn.setAttribute('aria-expanded', 'false');
        });
        document.querySelectorAll('.profile-avatar-menu__menu').forEach(function (menu) {
            menu.setAttribute('hidden', 'hidden');
        });
        document.querySelectorAll('.profile-avatar-menu__trigger').forEach(function (btn) {
            btn.setAttribute('aria-expanded', 'false');
        });
    }

    function initProfileAvatarMenu() {
        document.querySelectorAll('[data-profile-avatar-menu]').forEach(function (wrap) {
            var btn = wrap.querySelector('.profile-avatar-menu__trigger');
            var menu = wrap.querySelector('.profile-avatar-menu__menu');
            if (!btn || !menu) {
                return;
            }

            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var open = menu.hasAttribute('hidden');
                closeAllDropdowns();
                if (open) {
                    menu.removeAttribute('hidden');
                    btn.setAttribute('aria-expanded', 'true');
                }
            });
        });

        var lightbox = document.querySelector('[data-avatar-lightbox]');
        var lightboxImg = lightbox ? lightbox.querySelector('[data-avatar-lightbox-img]') : null;

        function closeLightbox() {
            if (!lightbox) {
                return;
            }
            lightbox.setAttribute('hidden', 'hidden');
            if (lightboxImg) {
                lightboxImg.removeAttribute('src');
            }
        }

        document.querySelectorAll('[data-profile-avatar-view]').forEach(function (viewBtn) {
            viewBtn.addEventListener('click', function () {
                if (!lightbox || !lightboxImg) {
                    return;
                }
                var url = viewBtn.getAttribute('data-avatar-url');
                if (!url) {
                    return;
                }
                closeAllDropdowns();
                lightboxImg.src = url;
                lightbox.removeAttribute('hidden');
            });
        });

        if (lightbox) {
            lightbox.querySelectorAll('[data-avatar-lightbox-close]').forEach(function (el) {
                el.addEventListener('click', closeLightbox);
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && lightbox && !lightbox.hasAttribute('hidden')) {
                    closeLightbox();
                }
            });
        }
    }

    function initFlashDismiss() {
        document.querySelectorAll('.pp-alert__dismiss').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var alert = btn.closest('.pp-alert');
                if (alert) {
                    alert.remove();
                }
            });
        });
    }

    function initLazyLoading() {
        var lazyImages = document.querySelectorAll('img.lazy[data-src]');
        if ('IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (entries, obs) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        var img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        obs.unobserve(img);
                    }
                });
            });
            lazyImages.forEach(function (img) { io.observe(img); });
        } else {
            lazyImages.forEach(function (img) {
                img.src = img.dataset.src;
                img.classList.remove('lazy');
            });
        }
    }

    function initRatingWidgets() {
        document.querySelectorAll('.rate-widget').forEach(function (widget) {
            if (widget.dataset.ratingBound === '1') {
                return;
            }
            widget.dataset.ratingBound = '1';

            var stars = Array.prototype.slice.call(widget.querySelectorAll('.rate-star'));
            var preset = parseInt(widget.dataset.userRating || '0', 10);

            if (preset > 0) {
                markSelected(widget, preset);
                widget.classList.add('is-rated');
            }

            if (widget.classList.contains('disabled')) {
                return;
            }

            stars.forEach(function (star) {
                star.addEventListener('mouseenter', function () {
                    if (widget.classList.contains('is-loading') || widget.classList.contains('disabled')) {
                        return;
                    }
                    previewHover(widget, parseInt(star.dataset.value, 10));
                });

                star.addEventListener('mouseleave', function () {
                    clearHover(widget);
                    if (widget.classList.contains('is-rated')) {
                        var current = parseInt(widget.dataset.userRating || '0', 10);
                        if (current > 0) {
                            markSelected(widget, current);
                        }
                    }
                });

                star.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (widget.classList.contains('is-loading') || widget.classList.contains('disabled')) {
                        return;
                    }
                    var value = parseInt(star.dataset.value, 10);
                    markSelected(widget, value);
                    sendRating(widget, value);
                });
            });
        });
    }

    function previewHover(widget, value) {
        widget.querySelectorAll('.rate-star').forEach(function (star) {
            var v = parseInt(star.dataset.value, 10);
            star.classList.toggle('hover', v <= value);
        });
    }

    function clearHover(widget) {
        widget.querySelectorAll('.rate-star').forEach(function (star) {
            star.classList.remove('hover');
        });
    }

    function setFeedback(storyId, text, type) {
        var fb = document.querySelector('[data-rating-feedback="' + storyId + '"]');
        if (!fb) {
            return;
        }
        fb.textContent = text;
        fb.classList.remove('is-success', 'is-error');
        if (type) {
            fb.classList.add('is-' + type);
        }
    }

    function sendRating(widget, value) {
        var storyId = widget.dataset.storyId;
        var csrfToken = widget.dataset.csrf;
        var tokenName = widget.dataset.csrfName || '_csrf';

        var body = new URLSearchParams();
        body.append('story_id', storyId);
        body.append('rating', String(value));
        body.append(tokenName, csrfToken);

        widget.classList.add('is-loading');
        setFeedback(storyId, 'Zapisuję ocenę…', null);

        fetch(ajaxRateUrl(), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded'
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
                    return { status: res.status, json: json };
                });
            })
            .then(function (data) {
                widget.classList.remove('is-loading');

                if (data.json.success) {
                    var rated = data.json.user_rating || value;
                    widget.dataset.userRating = String(rated);
                    markSelected(widget, rated);
                    widget.classList.add('is-rated', 'disabled');
                    widget.querySelectorAll('.rate-star').forEach(function (star) {
                        star.disabled = true;
                    });
                    updateAverage(storyId, data.json.rating_avg, data.json.ratings_count);
                    setFeedback(storyId, 'Dzięki za ocenę!', 'success');
                } else {
                    setFeedback(storyId, data.json.error || 'Nie udało się ocenić.', 'error');
                }
            })
            .catch(function () {
                widget.classList.remove('is-loading');
                clearHover(widget);
                setFeedback(storyId, 'Błąd połączenia. Spróbuj ponownie.', 'error');
            });
    }

    function markSelected(widget, value) {
        widget.querySelectorAll('.rate-star').forEach(function (star) {
            var v = parseInt(star.dataset.value, 10);
            star.classList.toggle('selected', v <= value);
        });
    }

    function updateAverage(storyId, avg, count) {
        var avgEl = document.querySelector('[data-rating-avg="' + storyId + '"]');
        var cntEl = document.querySelector('[data-rating-count="' + storyId + '"]');
        if (avgEl) {
            avgEl.textContent = Number(avg).toFixed(2);
        }
        if (cntEl) {
            cntEl.textContent = count;
        }
    }

    function initShareButtons() {
        document.querySelectorAll('[data-share-copy]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var url = btn.dataset.shareCopy;
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(function () {
                        var original = btn.textContent;
                        btn.textContent = 'Skopiowano!';
                        setTimeout(function () { btn.textContent = original; }, 1500);
                    });
                }
            });
        });
    }

    function initStoryNavigation() {
        var shell = document.querySelector('[data-story-nav]');
        if (!shell) {
            return;
        }

        var prevUrl = shell.dataset.navPrev || '';
        var nextUrl = shell.dataset.navNext || '';

        function isEditableTarget(el) {
            if (!el || !el.closest) {
                return false;
            }
            var tag = el.tagName;
            return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || el.isContentEditable;
        }

        document.addEventListener('keydown', function (e) {
            if (e.defaultPrevented || e.altKey || e.ctrlKey || e.metaKey || e.shiftKey) {
                return;
            }
            if (isEditableTarget(e.target)) {
                return;
            }

            if (e.key === 'ArrowLeft' && prevUrl) {
                e.preventDefault();
                window.location.href = prevUrl;
            } else if (e.key === 'ArrowRight' && nextUrl) {
                e.preventDefault();
                window.location.href = nextUrl;
            }
        });
    }

    function initFeedInfiniteScroll() {
        var shell = document.querySelector('[data-feed-infinite]');
        if (!shell) {
            return;
        }

        var list = shell.querySelector('[data-feed-list]');
        var status = shell.querySelector('[data-feed-status]');
        if (!list) {
            return;
        }

        var feedType = shell.dataset.feedType || 'newest';
        var feedSlug = shell.dataset.feedSlug || '';
        var page = parseInt(shell.dataset.feedPage || '1', 10);
        var pages = parseInt(shell.dataset.feedPages || '1', 10);
        var loading = false;

        if (page >= pages) {
            if (status) {
                status.remove();
            }
            return;
        }

        if (!status) {
            status = document.createElement('div');
            status.className = 'feed-infinite-status';
            status.setAttribute('data-feed-status', '');
            status.setAttribute('aria-live', 'polite');
            status.innerHTML = '<span class="feed-infinite-status__spinner" aria-hidden="true"></span>'
                + '<span class="feed-infinite-status__text">Ładowanie kolejnych historii…</span>';
            shell.appendChild(status);
        }

        function setStatusText(text) {
            var textEl = status.querySelector('.feed-infinite-status__text');
            if (textEl) {
                textEl.textContent = text;
            }
        }

        function loadMore() {
            if (loading || page >= pages) {
                return;
            }

            loading = true;
            status.classList.add('is-loading');
            setStatusText('Ładowanie kolejnych historii…');

            var url = ajaxFeedUrl()
                + '?feed=' + encodeURIComponent(feedType)
                + '&page=' + encodeURIComponent(String(page + 1));
            if (feedSlug) {
                url += '&slug=' + encodeURIComponent(feedSlug);
            }

            fetch(url, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(function (res) {
                    return res.text().then(function (text) {
                        var json;
                        try {
                            json = JSON.parse(text);
                        } catch (err) {
                            throw new Error('Nieprawidłowa odpowiedź serwera.');
                        }
                        return { status: res.status, json: json };
                    });
                })
                .then(function (data) {
                    loading = false;
                    status.classList.remove('is-loading');

                    if (!data.json.success || !data.json.html) {
                        setStatusText(data.json.error || 'Nie udało się załadować historii.');
                        return;
                    }

                    list.insertAdjacentHTML('beforeend', data.json.html);
                    page = data.json.page;
                    pages = data.json.pages;
                    shell.dataset.feedPage = String(page);
                    shell.dataset.feedPages = String(pages);
                    initRatingWidgets();

                    if (!data.json.has_more) {
                        status.remove();
                        if (observer) {
                            observer.disconnect();
                        }
                        return;
                    }

                    setStatusText('Przewiń dalej, aby zobaczyć więcej…');
                })
                .catch(function () {
                    loading = false;
                    status.classList.remove('is-loading');
                    setStatusText('Błąd połączenia. Spróbuj ponownie.');
                });
        }

        var observer = null;
        if ('IntersectionObserver' in window) {
            observer = new IntersectionObserver(function (entries) {
                if (entries[0].isIntersecting) {
                    loadMore();
                }
            }, { rootMargin: '320px 0px' });
            observer.observe(status);
        } else {
            status.classList.add('feed-infinite-status--manual');
            setStatusText('Załaduj więcej historii');
            status.addEventListener('click', loadMore);
            status.setAttribute('role', 'button');
            status.setAttribute('tabindex', '0');
            status.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    loadMore();
                }
            });
        }
    }

    function initSiteSearch() {
        var root = document.querySelector('[data-site-search]');
        if (!root) {
            return;
        }

        var input = root.querySelector('[data-site-search-input]');
        var dropdown = root.querySelector('[data-site-search-dropdown]');
        var form = root.querySelector('.site-search__form');
        var trap = root.querySelector('.site-search__trap');
        if (!input || !dropdown || !form) {
            return;
        }

        var timer = null;
        var lastQuery = '';
        var abortCtrl = null;

        function closeDropdown() {
            dropdown.setAttribute('hidden', 'hidden');
            dropdown.innerHTML = '';
        }

        function openDropdown() {
            dropdown.removeAttribute('hidden');
        }

        function fetchResults(query) {
            if (abortCtrl) {
                abortCtrl.abort();
            }
            abortCtrl = new AbortController();

            var url = ajaxSearchUrl()
                + '?q=' + encodeURIComponent(query)
                + '&website=' + encodeURIComponent(trap ? trap.value : '');

            fetch(url, {
                credentials: 'same-origin',
                signal: abortCtrl.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(function (res) {
                    return res.json();
                })
                .then(function (data) {
                    if (!data.success) {
                        dropdown.innerHTML = '<p class="site-search__empty">' + (data.error || 'Błąd wyszukiwania.') + '</p>';
                        openDropdown();
                        return;
                    }
                    if (data.hint) {
                        dropdown.innerHTML = '<p class="site-search__empty">' + data.hint + '</p>';
                        openDropdown();
                        return;
                    }
                    dropdown.innerHTML = data.html || '';
                    if (data.total > 0) {
                        openDropdown();
                    } else {
                        openDropdown();
                    }
                })
                .catch(function (err) {
                    if (err.name === 'AbortError') {
                        return;
                    }
                    dropdown.innerHTML = '<p class="site-search__empty">Błąd połączenia.</p>';
                    openDropdown();
                });
        }

        input.addEventListener('input', function () {
            var query = input.value.trim();
            if (timer) {
                clearTimeout(timer);
            }
            if (query.length < 2) {
                closeDropdown();
                lastQuery = query;
                return;
            }
            if (query === lastQuery) {
                return;
            }
            timer = setTimeout(function () {
                lastQuery = query;
                fetchResults(query);
            }, 280);
        });

        input.addEventListener('focus', function () {
            var query = input.value.trim();
            if (query.length >= 2 && dropdown.innerHTML !== '') {
                openDropdown();
            }
        });

        document.addEventListener('click', function (e) {
            if (!root.contains(e.target)) {
                closeDropdown();
            }
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeDropdown();
            }
        });
    }

    function initWallComposer() {
        var root = document.querySelector('[data-wall-composer]');
        if (!root) {
            return;
        }

        var trigger = root.querySelector('[data-wall-composer-trigger]');
        var form = root.querySelector('[data-wall-composer-form]');
        var textarea = root.querySelector('.wall-composer__textarea');
        var feedback = root.querySelector('[data-wall-composer-feedback]');
        var cancelBtn = root.querySelector('[data-wall-composer-cancel]');
        var submitBtn = root.querySelector('[data-wall-composer-submit]');

        if (!trigger || !form || !textarea) {
            return;
        }

        function setFeedback(message, type) {
            if (!feedback) {
                return;
            }
            feedback.textContent = message || '';
            feedback.classList.remove('is-error', 'is-success');
            if (type) {
                feedback.classList.add('is-' + type);
            }
        }

        function clearFieldErrors() {
            textarea.classList.remove('is-invalid');
            root.querySelectorAll('.wall-composer__category').forEach(function (label) {
                label.classList.remove('is-invalid');
            });
        }

        function showFieldErrors(errors) {
            if (!errors || typeof errors !== 'object') {
                return;
            }
            if (errors.content && errors.content[0]) {
                textarea.classList.add('is-invalid');
            }
            if (errors.category_id && errors.category_id[0]) {
                root.querySelectorAll('.wall-composer__category').forEach(function (label) {
                    label.classList.add('is-invalid');
                });
            }
        }

        function expand() {
            root.classList.add('is-expanded');
            form.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            window.setTimeout(function () {
                textarea.focus();
            }, 0);
        }

        function collapse() {
            root.classList.remove('is-expanded', 'is-submitting');
            form.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
            setFeedback('');
            clearFieldErrors();
        }

        function resetForm() {
            form.reset();
            clearFieldErrors();
            var firstCat = form.querySelector('input[name="category_id"]');
            if (firstCat) {
                firstCat.checked = true;
            }
        }

        trigger.addEventListener('click', expand);

        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                resetForm();
                collapse();
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && root.classList.contains('is-expanded')) {
                resetForm();
                collapse();
            }
        });

        form.addEventListener('submit', function (e) {
            if (form.dataset.fallbackSubmit === '1') {
                return;
            }

            e.preventDefault();
            setFeedback('');
            clearFieldErrors();
            root.classList.add('is-submitting');

            if (submitBtn) {
                submitBtn.disabled = true;
            }

            var body = new FormData(form);

            fetch(ajaxStoryUrl(), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: body
            })
                .then(function (res) {
                    return res.text().then(function (text) {
                        var json;
                        try {
                            json = JSON.parse(text);
                        } catch (err) {
                            throw new Error('invalid_json');
                        }
                        return { ok: res.ok, status: res.status, json: json };
                    });
                })
                .then(function (data) {
                    root.classList.remove('is-submitting');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }

                    if (data.ok && data.json.success) {
                        setFeedback(data.json.message || 'Gotowe!', 'success');
                        resetForm();

                        var feedList = document.querySelector('[data-feed-list]');
                        if (data.json.html && feedList) {
                            feedList.insertAdjacentHTML('afterbegin', data.json.html);
                            initRatingWidgets();
                            window.setTimeout(collapse, 1200);
                            return;
                        }

                        if (data.json.redirect) {
                            window.setTimeout(function () {
                                window.location.href = data.json.redirect;
                            }, data.json.status === 'pending' ? 1400 : 600);
                            return;
                        }

                        window.setTimeout(collapse, 1200);
                        return;
                    }

                    showFieldErrors(data.json.errors);
                    setFeedback(data.json.error || 'Nie udało się dodać historii.', 'error');
                })
                .catch(function () {
                    root.classList.remove('is-submitting');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
                    setFeedback('Wysyłam przez zwykły formularz…', 'success');
                    form.dataset.fallbackSubmit = '1';
                    form.submit();
                });
        });
    }

    function initStoryInlineEdit() {
        var root = document.querySelector('[data-story-inline-edit]');
        if (!root) {
            return;
        }

        var view = root.querySelector('[data-story-view]');
        var form = root.querySelector('[data-story-edit-form]');
        var feedback = root.querySelector('[data-story-edit-feedback]');
        var titleEl = root.querySelector('[data-story-title]');
        var contentEl = root.querySelector('[data-story-content]');
        var saveBtn = root.querySelector('[data-story-edit-save]');
        var cancelBtn = root.querySelector('[data-story-edit-cancel]');
        var toggles = document.querySelectorAll('[data-story-inline-edit-toggle]');

        if (!view || !form) {
            return;
        }

        var original = {
            title: form.querySelector('[name="title"]') ? form.querySelector('[name="title"]').value : '',
            content: form.querySelector('[name="content"]') ? form.querySelector('[name="content"]').value : '',
            categoryId: form.querySelector('[name="category_id"]') ? form.querySelector('[name="category_id"]').value : '',
            status: form.querySelector('[name="status"]') ? form.querySelector('[name="status"]').value : ''
        };

        function setFeedback(message, type) {
            if (!feedback) {
                return;
            }
            feedback.textContent = message || '';
            feedback.classList.remove('is-error', 'is-success');
            if (type) {
                feedback.classList.add('is-' + type);
            }
        }

        function clearFieldErrors() {
            form.querySelectorAll('.is-invalid').forEach(function (el) {
                el.classList.remove('is-invalid');
            });
        }

        function showFieldErrors(errors) {
            if (!errors) {
                return;
            }
            Object.keys(errors).forEach(function (field) {
                var input = form.querySelector('[name="' + field + '"]');
                if (input) {
                    input.classList.add('is-invalid');
                }
            });
        }

        function snapshotOriginal() {
            original.title = form.querySelector('[name="title"]').value;
            original.content = form.querySelector('[name="content"]').value;
            original.categoryId = form.querySelector('[name="category_id"]').value;
            if (form.querySelector('[name="status"]')) {
                original.status = form.querySelector('[name="status"]').value;
            }
        }

        function restoreOriginal() {
            form.querySelector('[name="title"]').value = original.title;
            form.querySelector('[name="content"]').value = original.content;
            form.querySelector('[name="category_id"]').value = original.categoryId;
            if (form.querySelector('[name="status"]')) {
                form.querySelector('[name="status"]').value = original.status;
            }
        }

        function setEditing(active) {
            root.classList.toggle('is-editing', active);
            view.hidden = active;
            form.hidden = !active;
            toggles.forEach(function (btn) {
                btn.setAttribute('aria-pressed', active ? 'true' : 'false');
                btn.classList.toggle('is-active', active);
            });
            if (active) {
                setFeedback('');
                clearFieldErrors();
                var focusTarget = form.querySelector('[name="title"]') || form.querySelector('[name="content"]');
                if (focusTarget) {
                    window.setTimeout(function () {
                        focusTarget.focus();
                    }, 0);
                }
            }
        }

        toggles.forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (root.classList.contains('is-editing')) {
                    restoreOriginal();
                    setEditing(false);
                    return;
                }
                snapshotOriginal();
                setEditing(true);
                root.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                restoreOriginal();
                setEditing(false);
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && root.classList.contains('is-editing')) {
                restoreOriginal();
                setEditing(false);
            }
        });

        form.addEventListener('submit', function (e) {
            if (form.dataset.fallbackSubmit === '1') {
                return;
            }

            e.preventDefault();
            setFeedback('');
            clearFieldErrors();
            root.classList.add('is-saving');
            if (saveBtn) {
                saveBtn.disabled = true;
            }

            var url = root.dataset.storyEditUrl || form.getAttribute('action');
            var body = new FormData(form);

            fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: body
            })
                .then(function (res) {
                    return res.text().then(function (text) {
                        var json;
                        try {
                            json = JSON.parse(text);
                        } catch (err) {
                            throw new Error('invalid_json');
                        }
                        return { ok: res.ok, json: json };
                    });
                })
                .then(function (data) {
                    root.classList.remove('is-saving');
                    if (saveBtn) {
                        saveBtn.disabled = false;
                    }

                    if (data.ok && data.json.success) {
                        if (titleEl) {
                            titleEl.textContent = data.json.title || titleEl.textContent;
                        }
                        if (contentEl) {
                            contentEl.textContent = data.json.content || contentEl.textContent;
                        }
                        if (data.json.image_url) {
                            var img = document.querySelector('[data-story-share-image]');
                            if (img) {
                                img.src = data.json.image_url;
                                img.removeAttribute('srcset');
                            }
                        }
                        snapshotOriginal();
                        setFeedback(data.json.message || 'Zapisano.', 'success');
                        setEditing(false);

                        if (data.json.redirect) {
                            window.setTimeout(function () {
                                window.location.href = data.json.redirect;
                            }, 500);
                        }
                        return;
                    }

                    showFieldErrors(data.json.errors);
                    setFeedback(data.json.error || 'Nie udało się zapisać.', 'error');
                })
                .catch(function () {
                    root.classList.remove('is-saving');
                    if (saveBtn) {
                        saveBtn.disabled = false;
                    }
                    setFeedback('Błąd połączenia. Spróbuj ponownie.', 'error');
                });
        });
    }
})();
