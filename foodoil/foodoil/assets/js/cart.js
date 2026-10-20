/* FoodOil - cart (localStorage) + cart page rendering */
(function (w, d) {
  'use strict';
  var KEY = 'foodoil_cart_v1';
  var cfg = w.FOODOIL || { base: '', csrf: '', maxQty: 10, freeAbove: 999, deliveryFee: 40 };
  var memory = [];

  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function money(n) {
    n = Number(n) || 0;
    var f = n % 1 === 0 ? 0 : 2;
    return '\u20B9' + n.toLocaleString('en-IN', { minimumFractionDigits: f, maximumFractionDigits: f });
  }
  function load() {
    try {
      var raw = w.localStorage.getItem(KEY);
      var arr = raw ? JSON.parse(raw) : memory;
      if (!Array.isArray(arr)) { return []; }
      return arr.map(function (x) { return { id: parseInt(x.id, 10), qty: parseInt(x.qty, 10) }; })
                .filter(function (x) { return x.id > 0 && x.qty > 0; });
    } catch (e) { return memory.slice(); }
  }
  function save(items, silent) {
    memory = items;
    try { w.localStorage.setItem(KEY, JSON.stringify(items)); } catch (e) { /* storage blocked: keep in memory */ }
    updateBadge();
    if (!silent) { d.dispatchEvent(new CustomEvent('cart:changed')); }
  }
  function count() { return load().reduce(function (n, x) { return n + x.qty; }, 0); }
  function updateBadge() {
    var b = d.getElementById('cart-count');
    if (!b) { return; }
    var n = count();
    b.textContent = n > 99 ? '99+' : n;
    b.hidden = n === 0;
  }
  function limit(stock) {
    var s = parseInt(stock, 10);
    return Math.max(1, Math.min(isNaN(s) ? cfg.maxQty : s, cfg.maxQty));
  }
  /* add(id, qty, stock) -> {ok, qty, max, capped} */
  function add(id, qty, stock) {
    id = parseInt(id, 10); qty = Math.max(1, parseInt(qty, 10) || 1);
    var max = limit(stock), items = load(), line = null;
    items.forEach(function (x) { if (x.id === id) { line = x; } });
    var current = line ? line.qty : 0;
    if (current >= max) { return { ok: false, max: max, qty: current }; }
    var next = Math.min(current + qty, max);
    if (line) { line.qty = next; } else { items.push({ id: id, qty: next }); }
    save(items);
    return { ok: true, qty: next, max: max, capped: current + qty > max };
  }
  function setQty(id, qty) {
    var items = load().map(function (x) { return x.id === id ? { id: id, qty: qty } : x; })
                      .filter(function (x) { return x.qty > 0; });
    save(items);
  }
  function remove(id) { save(load().filter(function (x) { return x.id !== id; })); }
  function clear() { save([]); }

  /* Ask the server for trusted prices / stock; sync local cart with the answer. */
  function fetchCart() {
    var items = load();
    if (!items.length) {
      return Promise.resolve({ ok: true, lines: [], removed: [], adjusted: [], subtotal: 0, delivery: 0, total: 0, count: 0 });
    }
    return fetch(cfg.base + '/api/cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': cfg.csrf },
      body: JSON.stringify({ items: items }),
      credentials: 'same-origin'
    }).then(function (r) { return r.json(); }).then(function (data) {
      if (!data.ok) { throw new Error(data.message || 'Could not load your cart.'); }
      var synced = data.lines.map(function (l) { return { id: l.id, qty: l.qty }; });
      if (JSON.stringify(synced) !== JSON.stringify(items)) { save(synced, true); }
      return data;
    });
  }

  w.FoodCart = { add: add, setQty: setQty, remove: remove, clear: clear, items: load, count: count,
                 updateBadge: updateBadge, fetchCart: fetchCart, money: money, esc: esc, limit: limit };
  updateBadge();
  w.addEventListener('storage', function (e) { if (e.key === KEY) { updateBadge(); } });

  /* ---------- cart page ---------- */
  var root = d.getElementById('cart-root');
  if (!root) { return; }
  var label = d.getElementById('cart-items-label');

  function emptyHtml() {
    return '<div class="empty-state"><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>' +
      '<h3>Your cart is empty</h3><p>Looks like you have not added any oils yet.</p>' +
      '<a class="btn btn-primary" href="' + cfg.base + '/products.php">Browse Products</a></div>';
  }
  function render(data) {
    var notes = '';
    if (data.removed.length || data.adjusted.length) {
      notes = '<div class="alert alert-info"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><span>Your cart was updated: ' +
        esc(data.removed.concat(data.adjusted).join('. ')) + '.</span></div>';
    }
    if (!data.lines.length) { if (label) { label.textContent = ''; } root.innerHTML = notes + emptyHtml(); return; }
    if (label) { label.textContent = data.lines.length + (data.lines.length === 1 ? ' item' : ' items'); }
    var rows = data.lines.map(function (l) {
      var atMax = l.qty >= l.max;
      return '<div class="cart-row" data-id="' + l.id + '">' +
        '<div class="cart-prod"><img src="' + esc(l.image) + '" alt=""><div><b>' + esc(l.name) + '</b><small class="muted">' + esc(l.volume) + '</small></div></div>' +
        '<div class="cart-cell" data-label="Price">' + money(l.price) + '</div>' +
        '<div class="cart-cell" data-label="Quantity"><div class="qty-box">' +
          '<button type="button" data-minus aria-label="Decrease quantity of ' + esc(l.name) + '">&minus;</button>' +
          '<input type="number" value="' + l.qty + '" min="1" max="' + l.max + '" data-qty aria-label="Quantity of ' + esc(l.name) + '">' +
          '<button type="button" data-plus aria-label="Increase quantity of ' + esc(l.name) + '"' + (atMax ? ' disabled' : '') + '>+</button></div>' +
          (atMax ? '<small class="muted max-note">Max ' + l.max + '</small>' : '') + '</div>' +
        '<div class="cart-cell cart-line-total" data-label="Total">' + money(l.subtotal) + '</div>' +
        '<button type="button" class="cart-remove" data-remove aria-label="Remove ' + esc(l.name) + ' from cart"><i class="fa-regular fa-trash-can" aria-hidden="true"></i></button>' +
        '</div>';
    }).join('');
    var hint = '';
    if (data.delivery > 0) {
      hint = '<p class="free-hint"><i class="fa-solid fa-truck-fast" aria-hidden="true"></i> Add ' + money(cfg.freeAbove - data.subtotal) + ' more for free delivery</p>';
    }
    root.innerHTML = notes +
      '<div class="cart-table"><div class="cart-head"><span>Product</span><span>Price</span><span>Quantity</span><span>Total</span><span class="sr-only">Remove</span></div>' + rows + '</div>' +
      '<div class="cart-foot"><a class="back-link" href="' + cfg.base + '/products.php"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Continue Shopping</a>' +
      '<div class="cart-summary"><dl class="totals">' +
        '<div><dt>Subtotal</dt><dd>' + money(data.subtotal) + '</dd></div>' +
        '<div><dt>Delivery fee</dt><dd>' + (data.delivery > 0 ? money(data.delivery) : 'Free') + '</dd></div>' +
        '<div class="grand"><dt>Grand Total</dt><dd>' + money(data.total) + '</dd></div></dl>' + hint +
        '<a class="btn btn-primary btn-block btn-lg" href="' + cfg.base + '/checkout.php">Proceed to Checkout</a></div></div>';
  }
  function refresh() {
    return fetchCart().then(render).catch(function (err) {
      root.innerHTML = '<div class="alert alert-error"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><span>' + esc(err.message || 'Something went wrong.') +
        '</span></div><button type="button" class="btn btn-primary" id="cart-retry">Try again</button>';
    });
  }
  root.addEventListener('click', function (e) {
    var t = e.target;
    if (t.id === 'cart-retry') { refresh(); return; }
    var btn = t.closest ? t.closest('button') : null;
    var row = t.closest ? t.closest('.cart-row') : null;
    if (!btn || !row) { return; }
    var id = parseInt(row.getAttribute('data-id'), 10);
    var input = row.querySelector('[data-qty]');
    var qty = parseInt(input.value, 10) || 1;
    if (btn.hasAttribute('data-minus')) {
      if (qty <= 1) { return; }
      setQty(id, qty - 1); refresh();
    } else if (btn.hasAttribute('data-plus')) {
      var max = parseInt(input.getAttribute('max'), 10) || cfg.maxQty;
      if (qty >= max) { return; }
      setQty(id, qty + 1); refresh();
    } else if (btn.hasAttribute('data-remove')) {
      var name = row.querySelector('b').textContent;
      if (w.confirm('Remove ' + name + ' from your cart?')) { remove(id); refresh(); if (w.showToast) { w.showToast(name + ' removed from cart'); } }
    }
  });
  root.addEventListener('change', function (e) {
    var input = e.target;
    if (!input.hasAttribute || !input.hasAttribute('data-qty')) { return; }
    var row = input.closest('.cart-row');
    var id = parseInt(row.getAttribute('data-id'), 10);
    var max = parseInt(input.getAttribute('max'), 10) || cfg.maxQty;
    var qty = parseInt(input.value, 10);
    if (isNaN(qty) || qty < 1) { qty = 1; }
    if (qty > max) { qty = max; if (w.showToast) { w.showToast('Only ' + max + ' available for this item', 'error'); } }
    setQty(id, qty); refresh();
  });
  d.addEventListener('cart:changed', function () { /* other scripts changed cart; page re-renders itself via refresh() calls */ });
  refresh();
})(window, document);
