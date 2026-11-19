/* Enquiry form: live checks, progress, character count, clear errors and a sending state. Server checks still apply. */
(function () {
  var f = document.getElementById('enq'); if (!f) return;
  var fields = [].slice.call(f.querySelectorAll('.enq-f[data-req]'));
  var consent = f.querySelector('input[name=consent]'), cErr = document.getElementById('enqConsentErr');
  var bar = document.getElementById('enqBar'), prog = document.getElementById('enqProg'), count = document.getElementById('enqCount'), body = document.getElementById('enq-body');
  var send = document.getElementById('enqSend'), touched = new WeakSet();
  var total = fields.length + 1;

  function valid(box) {
    var el = box.querySelector('input,textarea'), v = el.value.trim();
    if (!v) return false;
    if (box.getAttribute('data-type') === 'email') return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v);
    return v.length >= 2;
  }
  function paint(box, show) {
    var ok = valid(box), has = box.querySelector('input,textarea').value.trim() !== '';
    box.classList.toggle('ok', ok);
    box.classList.toggle('bad', show && !ok);
    box.classList.toggle('has', has);
  }
  function progress() {
    var done = fields.filter(valid).length + (consent.checked ? 1 : 0);
    bar.style.width = (done / total * 100) + '%';
    prog.textContent = done === total ? 'All set. Ready to send.' : done + ' of ' + total + ' required fields done';
    f.classList.toggle('ready', done === total);
  }
  function grow() { body.style.height = 'auto'; body.style.height = Math.min(body.scrollHeight + 2, 420) + 'px'; }
  function counter() { count.textContent = body.value.length + ' / 4000'; }

  fields.forEach(function (box) {
    var el = box.querySelector('input,textarea');
    el.addEventListener('input', function () { paint(box, touched.has(box)); progress(); if (el === body) { counter(); grow(); } });
    el.addEventListener('blur', function () { touched.add(box); paint(box, true); });
    paint(box, false);
  });
  consent.addEventListener('change', function () { cErr.classList.toggle('show', !consent.checked && f.classList.contains('tried')); progress(); });
  counter(); grow(); progress();

  f.addEventListener('submit', function (e) {
    f.classList.add('tried');
    var bad = fields.filter(function (b) { touched.add(b); paint(b, true); return !valid(b); });
    var noConsent = !consent.checked; cErr.classList.toggle('show', noConsent);
    if (bad.length || noConsent) {
      e.preventDefault();
      f.classList.remove('shake'); void f.offsetWidth; f.classList.add('shake');
      (bad.length ? bad[0].querySelector('input,textarea') : consent).focus();
      return;
    }
    send.disabled = true; send.classList.add('busy'); send.querySelector('.enq-send-t').textContent = 'Sending…';
  });
})();
