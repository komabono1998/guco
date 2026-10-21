// Live countdowns: <div class="countdown" data-start="ISO date"> with <b data-u="d|h|m|s"> children.
(function () {
  var boxes = document.querySelectorAll('.countdown[data-start]');
  if (!boxes.length) return;
  function pad(n) { return n < 10 ? '0' + n : String(n); }
  function tick() {
    var now = Date.now();
    boxes.forEach(function (box) {
      var left = Math.floor((new Date(box.dataset.start).getTime() - now) / 1000);
      if (left <= 0) {
        box.classList.add('done');
        box.textContent = 'Happening now';
        return;
      }
      var parts = { d: Math.floor(left / 86400), h: Math.floor(left % 86400 / 3600), m: Math.floor(left % 3600 / 60), s: left % 60 };
      Object.keys(parts).forEach(function (k) {
        var el = box.querySelector('[data-u="' + k + '"]');
        if (el) el.textContent = k === 'd' ? parts[k] : pad(parts[k]);
      });
    });
  }
  tick();
  setInterval(tick, 1000);
})();
