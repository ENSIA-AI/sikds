document.addEventListener('DOMContentLoaded', function () {
    var nav = document.getElementById('sikds-sidebar');
    var links = nav ? nav.querySelectorAll('.sikds-nav-link') : [];
    var toggleBtn = document.getElementById('sikds-sidebar-toggle');
    var closeBtn  = document.getElementById('sikds-sidebar-close');
    var overlay = document.getElementById('sikds-sidebar-overlay');
    var mobileBreakpoint = window.matchMedia('(min-width: 1024px)');

    function closeSidebar() {
        if (!nav) return;
        nav.classList.remove('is-open');
        if (overlay) overlay.classList.remove('is-open');
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('sikds-sidebar-open');
    }

    function openSidebar() {
        if (!nav) return;
        nav.classList.add('is-open');
        if (overlay) overlay.classList.add('is-open');
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
        document.body.classList.add('sikds-sidebar-open');
    }

    function handleViewportChange() {
        if (mobileBreakpoint.matches) {
            closeSidebar();
        }
    }

    if (toggleBtn && nav) {
        toggleBtn.addEventListener('click', function () {
            if (nav.classList.contains('is-open')) {
                closeSidebar();
                return;
            }
            openSidebar();
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeSidebar);
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeSidebar();
    });

    mobileBreakpoint.addEventListener('change', handleViewportChange);
    handleViewportChange();

    links.forEach(function (link) {
        link.addEventListener('click', function (e) {
            if (this.getAttribute('href') === '#') e.preventDefault();

            if (!mobileBreakpoint.matches) {
                closeSidebar();
            }

            links.forEach(function (l) {
                l.classList.replace('sikds-nav-link--active', 'sikds-nav-link--idle');
                var icon = l.querySelector('.sikds-nav-icon');
                if (icon) {
                    icon.src = l.dataset.iconIdle;
                    icon.classList.add('sikds-nav-icon--idle');
                }
            });

            this.classList.replace('sikds-nav-link--idle', 'sikds-nav-link--active');
            var activeIcon = this.querySelector('.sikds-nav-icon');
            if (activeIcon) {
                activeIcon.src = this.dataset.iconActive;
                activeIcon.classList.remove('sikds-nav-icon--idle');
            }
        });
    });
});
