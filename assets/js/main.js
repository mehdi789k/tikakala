/* ══════════════════════════════════════════════════════════
   قالب موبایل تیکا کالا (Woodmart Plus) — تعاملات
   سبد خرید / علاقه‌مندی، پنل‌های کشویی، اسلایدر، سوایپ
   ══════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  /* تبدیل عدد به فارسی و فرمت قیمت */
  var faDigits = '۰۱۲۳۴۵۶۷۸۹';
  function toFa(str) { return String(str).replace(/\d/g, function (d) { return faDigits[d]; }); }
  function money(n) { return toFa(n.toLocaleString('en-US')) + ' تومان'; }

  /* وضعیت برنامه */
  var state = { cart: [], wish: [] };
  try {
    var saved = localStorage.getItem('wd-tikakala');
    if (saved) state = JSON.parse(saved);
    if (!state.cart) state.cart = [];
    if (!state.wish) state.wish = [];
  } catch (e) { /* ignore */ }
  function save() {
    try { localStorage.setItem('wd-tikakala', JSON.stringify(state)); } catch (e) {}
  }

  /* اعلان */
  var toastEl = $('#toast'), toastTimer;
  function toast(msg) {
    toastEl.textContent = msg;
    toastEl.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toastEl.classList.remove('show'); }, 2200);
  }

  /* بج‌ها */
  function updateBadges() {
    var cartCount = state.cart.reduce(function (a, i) { return a + i.qty; }, 0);
    $$('.wd-badge-cart').forEach(function (b) { b.textContent = toFa(cartCount); b.dataset.count = cartCount; });
    var headerWish = $('.wd-header-actions .wd-badge:not(.wd-badge-cart)');
    if (headerWish) { headerWish.textContent = toFa(state.wish.length); headerWish.dataset.count = state.wish.length; }
    $$('.wd-bottom-nav .wd-badge-pin:not(.wd-badge-cart)').forEach(function (b) {
      b.textContent = toFa(state.wish.length); b.dataset.count = state.wish.length;
    });
    var cnt = $('.wd-drawer-count');
    if (cnt) cnt.textContent = '(' + toFa(cartCount) + ' مورد)';
  }

  /* رندر سبد */
  var cartBody = $('#cart-body');
  function renderCart() {
    if (!state.cart.length) {
      cartBody.innerHTML = '<p class="wd-cart-empty">سبد خرید شما خالی است!</p>';
      $('.wd-cart-sum').textContent = money(0);
      updateBadges();
      return;
    }
    var html = '';
    state.cart.forEach(function (item, idx) {
      html += '<div class="wd-cart-item">' +
        '<img src="' + item.img + '" alt="" loading="lazy">' +
        '<div class="wd-cart-item-info"><h4>' + item.name + '</h4>' +
        '<span class="wd-cart-item-price">' + money(item.price) + '</span><br>' +
        '<span class="wd-cart-qty"><button data-inc="' + idx + '" aria-label="افزایش">+</button>' +
        '<span>' + toFa(item.qty) + '</span>' +
        '<button data-dec="' + idx + '" aria-label="کاهش">&#8722;</button></span></div>' +
        '<button class="wd-cart-remove" data-remove="' + idx + '" aria-label="حذف">&#215;</button></div>';
    });
    cartBody.innerHTML = html;
    var sum = state.cart.reduce(function (a, i) { return a + i.price * i.qty; }, 0);
    $('.wd-cart-sum').textContent = money(sum);
    updateBadges();
  }

  cartBody.addEventListener('click', function (e) {
    var t = e.target;
    if (t.dataset.inc !== undefined) { state.cart[t.dataset.inc].qty++; }
    else if (t.dataset.dec !== undefined) {
      var it = state.cart[t.dataset.dec];
      it.qty--; if (it.qty <= 0) state.cart.splice(t.dataset.dec, 1);
    }
    else if (t.dataset.remove !== undefined) { state.cart.splice(t.dataset.remove, 1); }
    else return;
    save(); renderCart();
  });

  /* افزودن به سبد */
  $$('.wd-add-cart').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (btn.disabled) return;
      var name = btn.dataset.name, price = parseInt(btn.dataset.price, 10);
      var img = btn.closest('.wd-product').querySelector('img');
      var found = null;
      state.cart.forEach(function (i) { if (i.name === name) found = i; });
      if (found) found.qty++;
      else state.cart.push({ name: name, price: price, qty: 1, img: img ? img.src : '' });
      save(); renderCart();
      toast('«' + name + '» به سبد خرید اضافه شد ✓');
      $$('.wd-badge-cart').forEach(function (b) {
        b.style.transform = 'scale(1.5)';
        setTimeout(function () { b.style.transform = ''; }, 250);
      });
    });
  });

  /* علاقه‌مندی */
  $$('.wd-hbtn-wish').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      var title = btn.closest('.wd-product').querySelector('.wd-prod-title').textContent.trim();
      var i = state.wish.indexOf(title);
      if (i > -1) { state.wish.splice(i, 1); btn.style.color = ''; toast('از علاقه‌مندی‌ها حذف شد'); }
      else { state.wish.push(title); btn.style.color = '#e74c3c'; toast('به علاقه‌مندی‌ها اضافه شد ♥'); }
      save(); updateBadges();
    });
  });

  /* پنل‌های کشویی */
  var overlay = $('#drawer-overlay');
  var drawers = { cat: $('#cat-panel'), cart: $('#cart-panel'), login: $('#login-panel') };
  var openName = null;

  function closeDrawer() {
    Object.keys(drawers).forEach(function (k) {
      drawers[k].classList.remove('open');
      drawers[k].setAttribute('aria-hidden', 'true');
    });
    overlay.classList.remove('show');
    document.body.style.overflow = '';
    $('#btn-categories').setAttribute('aria-expanded', 'false');
    openName = null;
  }
  function openDrawer(name) {
    closeDrawer();
    drawers[name].classList.add('open');
    drawers[name].setAttribute('aria-hidden', 'false');
    overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
    openName = name;
    if (name === 'cat') $('#btn-categories').setAttribute('aria-expanded', 'true');
  }

  $('#btn-categories').addEventListener('click', function () {
    if (openName === 'cat') { closeDrawer(); } else { openDrawer('cat'); }
  });
  $('#btn-cart').addEventListener('click', function () { openDrawer('cart'); });
  $('#btn-account').addEventListener('click', function () { openDrawer('login'); });
  $('#bottom-cart').addEventListener('click', function (e) { e.preventDefault(); openDrawer('cart'); });
  $('#bottom-account').addEventListener('click', function (e) { e.preventDefault(); openDrawer('login'); });
  $('#bottom-cat').addEventListener('click', function (e) { e.preventDefault(); openDrawer('cat'); });
  overlay.addEventListener('click', closeDrawer);
  $$('.wd-drawer-close').forEach(function (b) { b.addEventListener('click', closeDrawer); });

  /* زیرمنوی آکاردئونی دسته‌بندی */
  $$('.wd-cat-list .has-sub > a').forEach(function (a) {
    a.addEventListener('click', function (e) {
      e.preventDefault();
      a.parentElement.classList.toggle('expanded');
    });
  });

  /* مودال جستجو */
  var searchModal = $('#search-modal');
  function closeSearch() {
    searchModal.classList.remove('open');
    searchModal.setAttribute('aria-hidden', 'true');
    if (!openName) document.body.style.overflow = '';
  }
  $('#btn-search-open').addEventListener('click', function () {
    searchModal.classList.add('open');
    searchModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    setTimeout(function () { searchModal.querySelector('input').focus(); }, 320);
  });
  $('#btn-search-close').addEventListener('click', closeSearch);
  $$('form').forEach(function (f) {
    if (f.querySelector('[type=search]')) f.addEventListener('submit', function (e) {
      e.preventDefault();
      toast('جستجو در نسخه دمو غیرفعال است');
    });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { closeDrawer(); closeSearch(); }
  });

  /* اسلایدر بنر */
  var slider = $('#hero-slider');
  var slides = $$('.wd-slide', slider);
  var dotsWrap = $('#slide-dots');
  var cur = 0, timer;

  slides.forEach(function (_, i) {
    var d = document.createElement('button');
    d.setAttribute('aria-label', 'اسلاید ' + toFa(i + 1));
    if (i === 0) d.classList.add('on');
    d.addEventListener('click', function () { go(i); restart(); });
    dotsWrap.appendChild(d);
  });

  function go(n) {
    slides[cur].classList.remove('is-active');
    dotsWrap.children[cur].classList.remove('on');
    cur = (n + slides.length) % slides.length;
    slides[cur].classList.add('is-active');
    dotsWrap.children[cur].classList.add('on');
  }
  function restart() { clearInterval(timer); timer = setInterval(function () { go(cur + 1); }, 4500); }
  $('#slide-next').addEventListener('click', function () { go(cur + 1); restart(); });
  $('#slide-prev').addEventListener('click', function () { go(cur - 1); restart(); });
  restart();

  /* سوایپ انگشت روی اسلایدر */
  var sx = null;
  slider.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; }, { passive: true });
  slider.addEventListener('touchend', function (e) {
    if (sx === null) return;
    var dx = e.changedTouches[0].clientX - sx;
    if (Math.abs(dx) > 45) { go(dx < 0 ? cur + 1 : cur - 1); restart(); }
    sx = null;
  }, { passive: true });

  /* بازگشت به بالا */
  var backtop = $('#backtop');
  window.addEventListener('scroll', function () {
    backtop.classList.toggle('show', window.scrollY > 500);
  }, { passive: true });
  backtop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });

  /* شروع */
  renderCart();
  $$('.wd-hbtn-wish').forEach(function (btn) {
    var title = btn.closest('.wd-product').querySelector('.wd-prod-title').textContent.trim();
    if (state.wish.indexOf(title) > -1) btn.style.color = '#e74c3c';
  });
})();
