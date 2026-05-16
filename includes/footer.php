        </main>
    </div>
</div>

<script>
(function () {
    var sidebar  = document.getElementById('app-sidebar');
    var overlay  = document.getElementById('ns-sidebar-overlay');
    var openBtn  = document.getElementById('ns-sidebar-toggle');
    var closeBtn = document.getElementById('ns-sidebar-close');
    var icon     = document.getElementById('ns-sidebar-toggle-icon');
    if (!sidebar || !overlay) return;

    function setOpen(state) {
        sidebar.classList.toggle('sidebar-open', state);
        overlay.classList.toggle('ns-sidebar-overlay-open', state);
        overlay.setAttribute('aria-hidden', state ? 'false' : 'true');
        document.body.style.overflow = state ? 'hidden' : '';
        if (openBtn) {
            openBtn.setAttribute('aria-expanded', state ? 'true' : 'false');
        }
        if (icon) {
            icon.style.transform = state ? 'rotate(90deg)' : '';
        }
    }

    function close() {
        setOpen(false);
    }

    function toggle() {
        setOpen(!sidebar.classList.contains('sidebar-open'));
    }

    if (openBtn) {
        openBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            toggle();
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            close();
        });
    }

    overlay.addEventListener('click', close);

    window.addEventListener('resize', function () {
        if (window.matchMedia('(min-width: 1024px)').matches) {
            close();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            close();
        }
    });

    sidebar.querySelectorAll('a[href]').forEach(function (a) {
        a.addEventListener('click', function () {
            if (window.matchMedia('(max-width: 1023px)').matches) {
                close();
            }
        });
    });
})();
</script>
<script>
(function () {
    /* ── Flash auto-dismiss ─────────────────────────── */
    var flash = document.getElementById('flash-msg');
    if (flash) {
        setTimeout(function () {
            flash.style.transition = 'opacity 0.5s ease';
            flash.style.opacity    = '0';
            setTimeout(function () { flash.remove(); }, 520);
        }, 4000);
    }

    /* ── Count-up animation ─────────────────────────── */
    function countUp(el) {
        var target   = parseFloat(el.dataset.count);
        var decimals = el.dataset.dec ? parseInt(el.dataset.dec, 10) : 0;
        var duration = 1200;
        var startTs  = null;
        function step(ts) {
            if (!startTs) startTs = ts;
            var t    = Math.min((ts - startTs) / duration, 1);
            var ease = 1 - Math.pow(1 - t, 3);
            el.textContent = (target * ease).toFixed(decimals);
            if (t < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    /* ── Progress bar animation ─────────────────────── */
    function animateBar(el) {
        var w = el.dataset.progress || '0%';
        el.style.width = '0%';
        requestAnimationFrame(function () {
            requestAnimationFrame(function () { el.style.width = w; });
        });
    }

    /* ── Intersection Observer ──────────────────────── */
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                var el = entry.target;
                if (el.dataset.count    !== undefined) countUp(el);
                if (el.dataset.progress !== undefined) animateBar(el);
                if (el.dataset.animate  !== undefined) el.classList.add('ns-visible');
                io.unobserve(el);
            });
        }, { threshold: 0.15 });

        document.querySelectorAll('[data-count],[data-progress],[data-animate]').forEach(function (el) {
            io.observe(el);
        });
    } else {
        /* Fallback: just show values immediately */
        document.querySelectorAll('[data-count]').forEach(function (el) {
            el.textContent = parseFloat(el.dataset.count).toFixed(el.dataset.dec ? parseInt(el.dataset.dec, 10) : 0);
        });
        document.querySelectorAll('[data-progress]').forEach(function (el) {
            el.style.width = el.dataset.progress;
        });
        document.querySelectorAll('[data-animate]').forEach(function (el) {
            el.classList.add('ns-visible');
        });
    }
})();
</script>
</body>
</html>
