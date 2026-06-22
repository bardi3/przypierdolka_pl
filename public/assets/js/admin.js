/* Panel admina — menu mobilne + etykiety tabel */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initAdminSidebar();
        initResponsiveTables();
        initFlashDismiss();
    });

    function initAdminSidebar() {
        var toggle = document.getElementById('adminSidebarToggle');
        var sidebar = document.getElementById('adminSidebar');
        var overlay = document.getElementById('adminOverlay');
        if (!toggle || !sidebar) {
            return;
        }

        function openSidebar() {
            sidebar.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
            document.body.classList.add('admin-sidebar-open');
            if (overlay) {
                overlay.hidden = false;
                overlay.setAttribute('aria-hidden', 'false');
            }
        }

        function closeSidebar() {
            sidebar.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('admin-sidebar-open');
            if (overlay) {
                overlay.hidden = true;
                overlay.setAttribute('aria-hidden', 'true');
            }
        }

        function isMobile() {
            return window.matchMedia('(max-width: 767.98px)').matches;
        }

        toggle.addEventListener('click', function () {
            if (sidebar.classList.contains('is-open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

        if (overlay) {
            overlay.addEventListener('click', closeSidebar);
        }

        sidebar.querySelectorAll('.nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (isMobile()) {
                    closeSidebar();
                }
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeSidebar();
            }
        });

        window.addEventListener('resize', function () {
            if (!isMobile()) {
                closeSidebar();
            }
        });
    }

    function initResponsiveTables() {
        document.querySelectorAll('.admin-main .table-responsive > table').forEach(function (table) {
            var headers = [];
            table.querySelectorAll('thead th').forEach(function (th) {
                headers.push(th.textContent.replace(/\s+/g, ' ').trim());
            });
            if (headers.length === 0) {
                return;
            }

            table.querySelectorAll('tbody tr').forEach(function (row) {
                var cells = row.querySelectorAll('td');
                cells.forEach(function (cell, index) {
                    if (cell.hasAttribute('colspan') || cell.hasAttribute('data-label')) {
                        return;
                    }
                    var label = headers[index] || '';
                    if (label !== '') {
                        cell.setAttribute('data-label', label);
                    }
                });
            });
        });
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
})();
