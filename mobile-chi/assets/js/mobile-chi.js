/**
 * Mobile Chi Child scripts.
 * Adds a light-weight sticky-header behaviour similar to the WoodMart demo
 * (whb-sticked class toggling) without depending on theme internals.
 * Settings are provided via the `MobileChi` global (wp_localize_script).
 */
(function () {
    'use strict';

    var cfg = window.MobileChi || {};

    function initStickyHeader() {
        var header = document.querySelector('.whb-main-header');
        if (!header) {
            return;
        }

        var threshold = header.offsetTop + 200;
        var placeholder = null;
        var ticking = false;

        function onScroll() {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(function () {
                var stick = window.pageYOffset > threshold;
                if (stick && !header.classList.contains('mobile_chi-sticked')) {
                    if (!placeholder) {
                        placeholder = document.createElement('div');
                        placeholder.className = 'mobile_chi-sticky-placeholder';
                        placeholder.style.height = header.offsetHeight + 'px';
                        header.parentNode.insertBefore(placeholder, header.nextSibling);
                    }
                    header.classList.add('mobile_chi-sticked');
                } else if (!stick && header.classList.contains('mobile_chi-sticked')) {
                    header.classList.remove('mobile_chi-sticked');
                    if (placeholder && placeholder.parentNode) {
                        placeholder.parentNode.removeChild(placeholder);
                    }
                    placeholder = null;
                }
                ticking = false;
            });
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    function initBackToTop() {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'mobile_chi-back-to-top';
        btn.setAttribute('aria-label', cfg.rtl ? 'بالا رفتن' : 'Back to top');
        btn.innerHTML = '\u2191';
        btn.style.display = 'none';
        document.body.appendChild(btn);

        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        window.addEventListener('scroll', function () {
            btn.style.display = window.pageYOffset > 600 ? 'flex' : 'none';
        }, { passive: true });
    }

    function boot() {
        initStickyHeader();
        initBackToTop();
    }

    if (document.readyState !== 'loading') {
        boot();
    } else {
        document.addEventListener('DOMContentLoaded', boot);
    }
})();
