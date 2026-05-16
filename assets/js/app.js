/**
 * NextScho — Global App Utilities
 * Dark mode · Toasts · Modals · Dropdowns · Command Palette
 */
(function (App) {
    'use strict';

    /* ── Dark mode ─────────────────────────────────────────────── */
    function initDarkMode() {
        var saved = localStorage.getItem('ns-dark');
        if (saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
        updateDarkToggleIcons();
    }

    function toggleDark() {
        var isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('ns-dark', isDark ? 'dark' : 'light');
        updateDarkToggleIcons();
    }

    function updateDarkToggleIcons() {
        var isDark = document.documentElement.classList.contains('dark');
        document.querySelectorAll('[data-dark-icon-sun]').forEach(function (el) {
            el.classList.toggle('hidden', !isDark);
        });
        document.querySelectorAll('[data-dark-icon-moon]').forEach(function (el) {
            el.classList.toggle('hidden', isDark);
        });
    }

    App.toggleDark = toggleDark;

    /* ── Toast notification system ─────────────────────────────── */
    var toastContainer = null;

    function getToastContainer() {
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'ns-toast-container';
            toastContainer.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:9999;display:flex;flex-direction:column;gap:0.5rem;pointer-events:none;';
            document.body.appendChild(toastContainer);
        }
        return toastContainer;
    }

    function showToast(message, type, duration) {
        type = type || 'success';
        duration = duration === undefined ? 3000 : duration;

        var colors = {
            success: { icon: '#10b981', border: '#10b981', bg: 'white', text: '#1e293b' },
            error:   { icon: '#ef4444', border: '#ef4444', bg: 'white', text: '#1e293b' },
            warning: { icon: '#f59e0b', border: '#f59e0b', bg: 'white', text: '#1e293b' },
            info:    { icon: '#4f46e5', border: '#4f46e5', bg: 'white', text: '#1e293b' },
        };
        var c = colors[type] || colors.success;

        var icons = {
            success: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
            error:   '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>',
            warning: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>',
            info:    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        };

        var toast = document.createElement('div');
        toast.className = 'ns-toast animate-slide-in';
        toast.style.cssText = [
            'background:' + c.bg,
            'border:1px solid #e2e8f0',
            'border-left:4px solid ' + c.border,
            'border-radius:0.75rem',
            'padding:0.75rem 1rem',
            'display:flex',
            'align-items:center',
            'gap:0.75rem',
            'box-shadow:0 10px 25px -5px rgba(0,0,0,.1),0 4px 6px -2px rgba(0,0,0,.05)',
            'min-width:280px',
            'max-width:420px',
            'pointer-events:all',
            'cursor:pointer',
        ].join(';');

        toast.innerHTML = [
            '<svg style="width:20px;height:20px;flex-shrink:0;color:' + c.icon + ';" fill="none" stroke="currentColor" viewBox="0 0 24 24">',
            icons[type] || icons.success,
            '</svg>',
            '<span style="font-size:0.875rem;color:' + c.text + ';flex:1;">' + message + '</span>',
            '<button style="color:#94a3b8;background:none;border:none;cursor:pointer;padding:0;line-height:1;" onclick="this.parentElement.remove()">',
            '<svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">',
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>',
            '</svg></button>',
        ].join('');

        if (document.documentElement.classList.contains('dark')) {
            toast.style.background = '#1e293b';
            toast.style.borderColor = '#334155';
            toast.style.borderLeft = '4px solid ' + c.border;
            toast.querySelector('span').style.color = '#f1f5f9';
        }

        var container = getToastContainer();
        container.appendChild(toast);

        toast.addEventListener('click', function () { dismissToast(toast); });

        if (duration > 0) {
            setTimeout(function () { dismissToast(toast); }, duration);
        }

        return toast;
    }

    function dismissToast(toast) {
        if (!toast || toast.dataset.dismissing) return;
        toast.dataset.dismissing = '1';
        toast.classList.remove('animate-slide-in');
        toast.classList.add('animate-slide-out');
        setTimeout(function () { toast.remove(); }, 250);
    }

    App.toast = showToast;

    /* ── Modal system ───────────────────────────────────────────── */
    function showModal(opts) {
        var title     = opts.title || '';
        var body      = opts.body || '';
        var confirmText  = opts.confirm || 'Confirm';
        var cancelText   = opts.cancel  || 'Cancel';
        var confirmClass = opts.danger  ? 'bg-rose-600 hover:bg-rose-700 text-white' : 'bg-indigo-600 hover:bg-indigo-700 text-white';
        var onConfirm = opts.onConfirm || function () {};
        var onCancel  = opts.onCancel  || function () {};

        var backdrop = document.createElement('div');
        backdrop.className = 'fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm';
        backdrop.setAttribute('data-modal', '');

        var dialog = document.createElement('div');
        dialog.className = 'w-full max-w-md p-6 rounded-2xl bg-white dark:bg-slate-800 shadow-xl mx-4';
        dialog.style.animation = 'ns-scaleIn 0.2s ease both';

        dialog.innerHTML = [
            '<h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">' + title + '</h3>',
            '<p class="text-sm text-slate-600 dark:text-slate-400 mb-6">' + body + '</p>',
            '<div class="flex justify-end gap-2">',
            '<button class="ns-modal-cancel px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-600 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition">' + cancelText + '</button>',
            '<button class="ns-modal-confirm px-4 py-2 rounded-lg text-sm font-medium transition ' + confirmClass + '">' + confirmText + '</button>',
            '</div>',
        ].join('');

        backdrop.appendChild(dialog);
        document.body.appendChild(backdrop);

        function close() { backdrop.remove(); }

        backdrop.querySelector('.ns-modal-confirm').addEventListener('click', function () {
            close(); onConfirm();
        });
        backdrop.querySelector('.ns-modal-cancel').addEventListener('click', function () {
            close(); onCancel();
        });
        backdrop.addEventListener('click', function (e) {
            if (e.target === backdrop) { close(); onCancel(); }
        });
        document.addEventListener('keydown', function esc(e) {
            if (e.key === 'Escape') { close(); onCancel(); document.removeEventListener('keydown', esc); }
        });

        return backdrop;
    }

    App.modal = showModal;

    /* ── Click-outside dropdown handler ─────────────────────────── */
    function initDropdowns() {
        document.addEventListener('click', function (e) {
            document.querySelectorAll('[data-dropdown-open]').forEach(function (panel) {
                var trigger = document.querySelector('[data-dropdown-trigger="' + panel.id + '"]');
                if (trigger && !trigger.contains(e.target) && !panel.contains(e.target)) {
                    closeDropdown(panel);
                }
            });
        });
    }

    function openDropdown(panel) {
        panel.classList.remove('hidden');
        panel.dataset.dropdownOpen = '1';
        panel.style.opacity = '0';
        panel.style.transform = 'translateY(-8px) scale(0.96)';
        panel.style.transition = 'none';
        requestAnimationFrame(function () {
            panel.style.transition = 'opacity 0.18s ease, transform 0.18s ease';
            panel.style.opacity    = '1';
            panel.style.transform  = 'translateY(0) scale(1)';
        });
    }

    function closeDropdown(panel) {
        delete panel.dataset.dropdownOpen;
        panel.style.opacity   = '0';
        panel.style.transform = 'translateY(-6px) scale(0.97)';
        setTimeout(function () {
            if (!panel.dataset.dropdownOpen) panel.classList.add('hidden');
        }, 180);
    }

    function toggleDropdown(panelId) {
        var panel = document.getElementById(panelId);
        if (!panel) return;
        if (panel.dataset.dropdownOpen) {
            closeDropdown(panel);
        } else {
            openDropdown(panel);
        }
    }

    App.toggleDropdown = toggleDropdown;

    /* ── Delete confirmation (replaces inline confirm()) ─────────── */
    function confirmDelete(message, formOrCallback) {
        App.modal({
            title: 'Konfirmasi Hapus',
            body: message || 'Data akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.',
            confirm: 'Hapus',
            cancel: 'Batal',
            danger: true,
            onConfirm: function () {
                if (typeof formOrCallback === 'function') {
                    formOrCallback();
                } else if (formOrCallback && formOrCallback.submit) {
                    formOrCallback.submit();
                }
            },
        });
        return false;
    }

    App.confirmDelete = confirmDelete;

    /* ── Cmd+K placeholder ───────────────────────────────────────── */
    function initCommandPalette() {
        document.addEventListener('keydown', function (e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                var searchBtn = document.getElementById('ns-search-btn');
                if (searchBtn) searchBtn.click();
            }
        });
    }

    /* ── Intersection observer (reveal + counter trigger) ────────── */
    function initAnimations() {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                var el = entry.target;
                if (el.dataset.count !== undefined) animateCounter(el);
                if (el.dataset.progress !== undefined) animateProgress(el);
                if (el.dataset.animate !== undefined) el.classList.add('ns-visible');
                io.unobserve(el);
            });
        }, { threshold: 0.15 });

        document.querySelectorAll('[data-count],[data-progress],[data-animate]')
            .forEach(function (el) { io.observe(el); });
    }

    function animateCounter(el) {
        var raw     = el.dataset.count || '0';
        var target  = parseFloat(raw);
        var isFloat = raw.includes('.');
        var decimals = isFloat ? (raw.split('.')[1] || '').length : 0;
        var duration = 900;
        var start    = performance.now();
        function tick(now) {
            var p     = Math.min((now - start) / duration, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            var val   = target * eased;
            el.textContent = isFloat ? val.toFixed(decimals) : Math.round(val).toString();
            if (p < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    }

    function animateProgress(bar) {
        var pct = parseFloat(bar.dataset.progress) || 0;
        bar.style.width = '0%';
        requestAnimationFrame(function () {
            bar.style.transition = 'width 1.1s cubic-bezier(0.16,1,0.3,1)';
            bar.style.width = Math.min(pct, 100) + '%';
        });
    }

    /* ── Ripple effect ───────────────────────────────────────────── */
    function initRipple() {
        document.querySelectorAll('[data-ripple]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                el.querySelectorAll('.ns-ripple').forEach(function (r) { r.remove(); });
                var rect = el.getBoundingClientRect();
                var size = Math.max(rect.width, rect.height) * 2.2;
                var x = e.clientX - rect.left - size / 2;
                var y = e.clientY - rect.top  - size / 2;
                var span = document.createElement('span');
                span.className = 'ns-ripple';
                span.style.cssText = 'width:' + size + 'px;height:' + size + 'px;left:' + x + 'px;top:' + y + 'px;';
                el.style.position = 'relative';
                el.style.overflow = 'hidden';
                el.appendChild(span);
                span.addEventListener('animationend', function () { span.remove(); });
            });
        });
    }

    /* ── Live table search ───────────────────────────────────────── */
    function initTableSearch() {
        document.querySelectorAll('[data-search-table]').forEach(function (input) {
            var tableId = input.dataset.searchTable;
            var table   = document.getElementById(tableId);
            if (!table) return;
            var tbody   = table.querySelector('tbody');

            input.addEventListener('input', function () {
                var q = input.value.toLowerCase().trim();
                var count = 0;

                tbody.querySelectorAll('tr:not(.ns-empty-row)').forEach(function (row) {
                    var match = !q || row.textContent.toLowerCase().includes(q);
                    row.classList.toggle('ns-hidden', !match);
                    if (match) { count++; row.classList.add('ns-match'); }
                    else        { row.classList.remove('ns-match'); }
                });

                var emptyRow = tbody.querySelector('.ns-empty-row');
                if (count === 0 && q) {
                    if (!emptyRow) {
                        emptyRow = document.createElement('tr');
                        emptyRow.className = 'ns-empty-row ns-empty-state';
                        emptyRow.innerHTML = '<td colspan="99" class="px-6 py-10 text-center text-sm text-slate-400">Tidak ada hasil untuk "<strong class="text-slate-600 ns-q"></strong>"</td>';
                        tbody.appendChild(emptyRow);
                    }
                    emptyRow.querySelector('.ns-q').textContent = input.value;
                } else if (emptyRow) {
                    emptyRow.remove();
                }
            });
        });
    }

    /* ── Score bar animation ─────────────────────────────────────── */
    function initScoreBars() {
        document.querySelectorAll('.ns-score-fill').forEach(function (bar) {
            var target = bar.dataset.score || '0';
            bar.style.width = '0%';
            setTimeout(function () { bar.style.width = Math.min(parseFloat(target), 100) + '%'; }, 200);
        });
    }

    /* ── Init all ────────────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        initDarkMode();
        initDropdowns();
        initCommandPalette();
        initAnimations();
        initRipple();
        initTableSearch();
        initScoreBars();
    });

}(window.App = window.App || {}));
