/* FoodOil - checkout: order summary, validation and order submission */
(function (w, d) {
  'use strict';
  var cfg = w.FOODOIL, C = w.FoodCart, esc = C.esc, money = C.money;
  var form = d.getElementById('checkout-form');
  if (!form) { return; }
  var summary = d.getElementById('summary-body');
  var placeBtn = d.getElementById('place-order');
  var alertBox = d.getElementById('checkout-alert');
  var emptyBox = d.getElementById('checkout-empty');

  function showAlert(msg, items) {
    var list = items && items.length ? '<ul>' + items.map(function (i) { return '<li>' + esc(i) + '</li>'; }).join('') + '</ul>' : '';
    alertBox.innerHTML = '<div class="alert alert-error"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><span>' + esc(msg) + list + '</span></div>';
    alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
  function clearErrors() {
    alertBox.innerHTML = '';
    form.querySelectorAll('.field-error').forEach(function (el) { el.textContent = ''; el.classList.remove('show'); });
    form.querySelectorAll('[aria-invalid]').forEach(function (el) { el.removeAttribute('aria-invalid'); });
  }
  function fieldError(name, msg) {
    var el = form.querySelector('[data-error-for="' + name + '"]'), input = form.elements[name];
    if (el) { el.textContent = msg; el.classList.add('show'); }
    if (input && input.setAttribute) { input.setAttribute('aria-invalid', 'true'); }
  }

  /* ---------- summary ---------- */
  function renderSummary(data) {
    if (!data.lines.length) { form.hidden = true; emptyBox.hidden = false; return; }
    var note = '';
    if (data.removed.length || data.adjusted.length) {
      note = '<div class="alert alert-info"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><span>Your cart was updated: ' + esc(data.removed.concat(data.adjusted).join('. ')) + '.</span></div>';
    }
    summary.innerHTML = note + '<ul class="summary-lines">' + data.lines.map(function (l) {
      return '<li><img src="' + esc(l.image) + '" alt=""><div><b>' + esc(l.name) + ' (' + esc(l.volume) + ')</b><br><small class="muted">' + money(l.price) + ' &times; ' + l.qty + '</small></div><span>' + money(l.subtotal) + '</span></li>';
    }).join('') + '</ul>' +
    '<dl class="totals"><div><dt>Subtotal</dt><dd>' + money(data.subtotal) + '</dd></div>' +
    '<div><dt>Delivery Charge</dt><dd>' + (data.delivery > 0 ? money(data.delivery) : 'Free') + '</dd></div>' +
    '<div class="grand"><dt>Total</dt><dd>' + money(data.total) + '</dd></div></dl>';
    placeBtn.disabled = false;
  }
  function loadSummary() {
    if (!C.items().length) { form.hidden = true; emptyBox.hidden = false; return Promise.resolve(); }
    return C.fetchCart().then(renderSummary).catch(function (err) {
      summary.innerHTML = '<div class="alert alert-error"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><span>' + esc(err.message) + '</span></div>' +
        '<button type="button" class="btn btn-outline btn-sm" id="summary-retry">Try again</button>';
    });
  }
  summary.addEventListener('click', function (e) { if (e.target.id === 'summary-retry') { loadSummary(); } });

  /* ---------- payment options ---------- */
  var note = d.getElementById('pay-note');
  form.querySelectorAll('input[name="payment"]').forEach(function (r) {
    r.addEventListener('change', function () {
      form.querySelectorAll('.pay-option').forEach(function (o) { o.classList.toggle('selected', o.querySelector('input').checked); });
      note.hidden = form.elements.payment.value === 'COD';
    });
  });

  /* ---------- validation (mirrors the server rules) ---------- */
  function validate() {
    var v = {}, errs = {};
    ['name', 'phone', 'address', 'city', 'state', 'pincode'].forEach(function (k) { v[k] = (form.elements[k].value || '').trim(); });
    if (v.name.length < 2) { errs.name = 'Enter your full name.'; }
    if (!/^[6-9]\d{9}$/.test(v.phone)) { errs.phone = 'Enter a valid 10-digit mobile number.'; }
    if (v.address.length < 10) { errs.address = 'Enter your full delivery address (at least 10 characters).'; }
    if (v.city.length < 2) { errs.city = 'Enter your city.'; }
    if (!v.state) { errs.state = 'Select your state.'; }
    if (!/^[1-9]\d{5}$/.test(v.pincode)) { errs.pincode = 'Enter a valid 6-digit PIN code.'; }
    return { values: v, errors: errs };
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    clearErrors();
    var res = validate(), keys = Object.keys(res.errors);
    if (keys.length) {
      keys.forEach(function (k) { fieldError(k, res.errors[k]); });
      form.elements[keys[0]].focus();
      return;
    }
    var payload = res.values;
    payload.payment = form.elements.payment.value;
    payload.items = C.items();
    placeBtn.disabled = true; placeBtn.innerHTML = '<span class="spinner spinner-white" aria-hidden="true"></span> Placing order...';

    fetch(cfg.base + '/api/orders.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': cfg.csrf },
      credentials: 'same-origin',
      body: JSON.stringify(payload)
    }).then(function (r) { return r.json().then(function (j) { return { status: r.status, body: j }; }); })
      .then(function (r) {
        var b = r.body;
        if (b.ok) { C.clear(); w.location.href = b.redirect; return; }
        if (r.status === 401) { w.location.href = cfg.base + '/login.php?next=checkout.php'; return; }
        if (b.errors) { Object.keys(b.errors).forEach(function (k) { fieldError(k, b.errors[k]); }); }
        showAlert(b.message || 'Could not place the order.', b.details);
        if (b.cartChanged) { loadSummary(); }
        resetBtn();
      }).catch(function () {
        showAlert('Network problem. Please check your connection and try again.');
        resetBtn();
      });
  });
  function resetBtn() { placeBtn.disabled = false; placeBtn.textContent = 'Place Order'; }

  loadSummary();
})(window, document);
