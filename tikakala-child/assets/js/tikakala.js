/**
 * Tikakala Child scripts.
 * Adds a light-weight sticky-header behaviour similar to the WoodMart demo
 * (whb-sticked class toggling) without depending on theme internals.
 */
(function () {
    'use strict';

    function initStickyHeader() {
        var header = document.querySelector('.whb-main-header');
        if (!header) {
            return;
        }

        var threshold = header.offsetTop + 200;
        var ticking = false;

        function onScroll() {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(function () {
                if (window.pageYOffset > threshold) {
                    header.classList.add('tikakala-sticked');
                } else {
                    header.classList.remove('tikakala-sticked');
                }
                ticking = false;
            });
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    if (document.readyState !== 'loading') {
        initStickyHeader();
    } else {
        document.addEventListener('DOMContentLoaded', initStickyHeader);
    }
})();
