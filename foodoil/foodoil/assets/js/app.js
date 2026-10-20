/* FoodOil - shared interactions: toast, header, search suggestions, add-to-cart, forms */
(function (w, d) {
  'use strict';
  var cfg = w.FOODOIL || { base: '' };
  var esc = (w.FoodCart && w.FoodCart.esc) || function (s) { return String(s); };

  /* ---------- toast ---------- */
  w.showToast = function (msg, type) {
    var wrap = d.getElementById('toast-wrap');
    if (!wrap) { return; }
    var t = d.createElement('div');
    t.className = 'toast' + (type === 'error' ? ' toast-error' : '');
    t.innerHTML = '<i class="fa-solid ' + (type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check') + '" aria-hidden="true"></i><span></span>';
    t.lastChild.textContent = msg;
    wrap.appendChild(t);
    setTimeout(function () { t.classList.add('hide'); setTimeout(function () { t.remove(); }, 300); }, 2600);
  };

  /* ---------- header: mobile menu, search, account menu ---------- */
  var header = d.getElementById('site-header');
  var menuBtn = d.getElementById('menu-toggle');
  if (menuBtn && header) {
    menuBtn.addEventListener('click', function () {
      var open = header.classList.toggle('nav-open');
      menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      menuBtn.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    });
  }
  var sBtn = d.getElementById('search-toggle'), sPanel = d.getElementById('search-panel');
  var sInput = d.getElementById('header-search'), sList = d.getElementById('search-suggest');
  function toggleSearch(show) {
    if (!sPanel) { return; }
    sPanel.hidden = !show;
    sBtn.setAttribute('aria-expanded', show ? 'true' : 'false');
    if (show && sInput) { sInput.focus(); }
  }
  if (sBtn && sPanel) {
    sBtn.addEventListener('click', function () { toggleSearch(sPanel.hidden); });
    var timer = null;
    sInput.addEventListener('input', function () {
      clearTimeout(timer);
      var q = sInput.value.trim();
      if (q.length < 2) { sList.innerHTML = ''; return; }
      timer = setTimeout(function () {
        fetch(cfg.base + '/api/products.php?q=' + encodeURIComponent(q), { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (!data.products || !data.products.length) { sList.innerHTML = '<li class="muted small pad">No oils found for "' + esc(q) + '"</li>'; return; }
            sList.innerHTML = data.products.map(function (p) {
              return '<li><a href="' + esc(p.url) + '"><img src="' + esc(p.image) + '" alt=""><span>' + esc(p.name) + ' <small class="muted">' + esc(p.volume) + '</small></span><b>' + w.FoodCart.money(p.price) + '</b></a></li>';
            }).join('');
          }).catch(function () { sList.innerHTML = ''; });
      }, 250);
    });
  }
  d.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') { return; }
    if (sPanel && !sPanel.hidden) { toggleSearch(false); sBtn.focus(); }
    if (header && header.classList.contains('nav-open')) { header.classList.remove('nav-open'); menuBtn.setAttribute('aria-expanded', 'false'); }
    var um = d.querySelector('.user-menu[open]'); if (um) { um.removeAttribute('open'); }
  });
  d.addEventListener('click', function (e) {
    var um = d.querySelector('.user-menu[open]');
    if (um && !um.contains(e.target)) { um.removeAttribute('open'); }
  });

  /* ---------- add to cart / buy now ---------- */
  function qtyFor(btn) {
    var sel = btn.getAttribute('data-qty-input');
    if (!sel) { return 1; }
    var input = d.querySelector(sel);
    var v = input ? parseInt(input.value, 10) : 1;
    return isNaN(v) || v < 1 ? 1 : v;
  }
  d.addEventListener('click', function (e) {
    var btn = e.target.closest ? e.target.closest('[data-add-to-cart],[data-buy-now]') : null;
    if (!btn || !w.FoodCart) { return; }
    var res = w.FoodCart.add(btn.getAttribute('data-id'), qtyFor(btn), btn.getAttribute('data-stock'));
    var name = btn.getAttribute('data-name') || 'Item';
    if (btn.hasAttribute('data-buy-now')) {
      if (!res.ok && res.qty === undefined) { return; }
      w.location.href = cfg.base + '/checkout.php';
      return;
    }
    if (!res.ok) {
      w.showToast('Maximum quantity (' + res.max + ') already in your cart', 'error');
      return;
    }
    w.showToast(res.capped ? name + ' added (limited to ' + res.max + ')' : name + ' added to cart');
    var old = btn.textContent;
    btn.classList.add('added'); btn.textContent = 'Added \u2713';
    setTimeout(function () { btn.classList.remove('added'); btn.textContent = old; }, 1200);
  });

  /* ---------- quantity box (product page) ---------- */
  d.querySelectorAll('[data-qty-box]').forEach(function (box) {
    var input = box.querySelector('input'), max = parseInt(box.getAttribute('data-max'), 10) || 10;
    function set(v) { v = parseInt(v, 10); if (isNaN(v) || v < 1) { v = 1; } if (v > max) { v = max; } input.value = v; }
    box.querySelector('[data-qty-minus]').addEventListener('click', function () { set(parseInt(input.value, 10) - 1); });
    box.querySelector('[data-qty-plus]').addEventListener('click', function () { set(parseInt(input.value, 10) + 1); });
    input.addEventListener('change', function () { set(input.value); });
  });

  /* ---------- misc form helpers ---------- */
  d.addEventListener('submit', function (e) {
    var f = e.target, msg = f.getAttribute && f.getAttribute('data-confirm');
    if (msg && !w.confirm(msg)) { e.preventDefault(); }
  });
  d.querySelectorAll('select[data-autosubmit]').forEach(function (s) {
    s.addEventListener('change', function () { s.form.submit(); });
  });
  d.querySelectorAll('[data-toggle-password]').forEach(function (b) {
    b.addEventListener('click', function () {
      var input = d.querySelector(b.getAttribute('data-toggle-password'));
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      b.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      b.firstElementChild.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
    });
  });
  d.querySelectorAll('input[inputmode="numeric"]').forEach(function (i) {
    i.addEventListener('input', function () { i.value = i.value.replace(/\D/g, ''); });
  });
})(window, document);
