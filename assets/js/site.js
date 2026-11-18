// Shared interactions for the public site: scroll progress, reveal on scroll, number count-up, gallery lightbox.
(function () {
  'use strict';
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var root = document.documentElement;

  /* slogan ribbon: pause and play */
  var sgBtn = document.querySelector('.sg-pause'), sgBox = document.querySelector('.sg');
  if (sgBtn && sgBox) {
    sgBtn.addEventListener('click', function () {
      var p = sgBox.classList.toggle('paused');
      sgBtn.setAttribute('aria-pressed', p ? 'true' : 'false');
      sgBtn.setAttribute('aria-label', p ? 'Play the scrolling slogan' : 'Pause the scrolling slogan');
    });
  }

  /* phone menu: fold sub-pages under their parent */
  [].slice.call(document.querySelectorAll('.mainnav .navitem.has-sub')).forEach(function (item) {
    var a = item.querySelector(':scope > a'); if (!a) return;
    var b = document.createElement('button');
    b.type = 'button'; b.className = 'sub-toggle'; b.setAttribute('aria-expanded', 'false'); b.setAttribute('aria-label', 'Show pages under ' + a.textContent.trim());
    b.innerHTML = '&#9662;';
    b.addEventListener('click', function () { var o = item.classList.toggle('open-sub'); b.setAttribute('aria-expanded', o ? 'true' : 'false'); });
    a.insertAdjacentElement('afterend', b);
  });

  /* scroll progress bar and header shadow */
  var bar = document.createElement('div');
  bar.className = 'sp-bar';
  bar.setAttribute('aria-hidden', 'true');
  document.body.appendChild(bar);
  var header = document.querySelector('.site-header');
  var ticking = false;
  function onScroll() {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(function () {
      var max = root.scrollHeight - root.clientHeight;
      bar.style.transform = 'scaleX(' + (max > 0 ? Math.min(1, root.scrollTop / max) : 0) + ')';
      if (header) header.classList.toggle('scrolled', root.scrollTop > 10);
      ticking = false;
    });
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* reveal on scroll: only elements without their own animation; nested items inherit their parent's reveal */
  var skip = '.mb, .po, .pw, .fd, .ob, .cv, .mvv, .hero2, .anthem, .page-head, .lb, .site-header, .topbar, .slogan-strip, .newsletter-band, .site-footer, .ft, .countdown, .gl';
  var sel = 'main > section, main > .grid, main > .split, main > .cta, main > .filters, main > .table-wrap, main > .calendar, main > .notice, main > .lead, main > h2, main > details, main > article, ' +
    'main .grid > *, main .split > *, main .album > *, main .photo-strip > *, main .steps > li, main .table-wrap, main .cta, main .gov-section, main .event-hero, main .filters, main .calendar';
  var els = [].slice.call(document.querySelectorAll(sel)).filter(function (el) {
    return !el.closest(skip) && el.tagName !== 'SCRIPT';
  });
  var chosen = [];
  els.forEach(function (el) {
    for (var p = el.parentElement; p; p = p.parentElement) { if (chosen.indexOf(p) > -1) return; }
    chosen.push(el);
  });
  chosen.forEach(function (el) {
    var sibs = el.parentElement ? [].slice.call(el.parentElement.children) : [];
    var i = Math.max(0, sibs.indexOf(el));
    if (el.parentElement && /grid|album|steps|photo-strip|split/.test(el.parentElement.className)) el.style.setProperty('--rd', Math.min(i, 6) * 90 + 'ms');
    el.classList.add('rv');
  });
  if ('IntersectionObserver' in window && !reduce) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
      });
    }, { threshold: 0.06, rootMargin: '0px 0px -4% 0px' });
    chosen.forEach(function (el) { io.observe(el); });
  } else {
    chosen.forEach(function (el) { el.classList.add('in'); });
  }
  // Safety net: never leave content hidden if the observer is slow or unavailable.
  setTimeout(function () {
    chosen.forEach(function (el) { if (!el.classList.contains('in') && el.getBoundingClientRect().top < window.innerHeight * 1.2) el.classList.add('in'); });
  }, 2500);

  /* count-up for plain numbers in stat strips */
  if (!reduce && 'IntersectionObserver' in window) {
    var nums = [].slice.call(document.querySelectorAll('.hero-stats b, .band-grid b, .gov-count b, .gl-item b, .ov-fact b')).filter(function (el) {
      var t = el.textContent.trim();
      var n = parseInt(t.replace(/,/g, ''), 10);
      return /^\d[\d,]*$/.test(t) && !(n >= 1900 && n <= 2100);
    });
    var co = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (!e.isIntersecting) return;
        co.unobserve(e.target);
        var el = e.target, final = el.textContent.trim(), target = parseInt(final.replace(/,/g, ''), 10), start = null;
        function step(t) {
          if (start === null) start = t;
          var p = Math.min(1, (t - start) / 1100);
          el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString('en');
          if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
        setTimeout(function () { el.textContent = final; }, 1600);
      });
    }, { threshold: 0.5 });
    nums.forEach(function (el) { co.observe(el); });
  }

  /* gallery lightbox */
  var links = [].slice.call(document.querySelectorAll('.album a[href]'));
  if (links.length) {
    var box = document.createElement('div');
    box.className = 'lb';
    box.setAttribute('role', 'dialog');
    box.setAttribute('aria-modal', 'true');
    box.setAttribute('aria-label', 'Photo viewer');
    box.innerHTML = '<button class="lb-x" aria-label="Close">&times;</button><button class="lb-p" aria-label="Previous photo">&lsaquo;</button><figure><img alt=""><figcaption></figcaption></figure><button class="lb-n" aria-label="Next photo">&rsaquo;</button><span class="lb-c"></span>';
    document.body.appendChild(box);
    var img = box.querySelector('img'), cap = box.querySelector('figcaption'), count = box.querySelector('.lb-c'), cur = 0, last = null;
    function show(i) {
      cur = (i + links.length) % links.length;
      var a = links[cur], im = a.querySelector('img');
      img.src = a.getAttribute('href');
      img.alt = im ? im.alt : '';
      cap.textContent = im ? im.alt : '';
      count.textContent = (cur + 1) + ' / ' + links.length;
    }
    function open(i) { last = document.activeElement; show(i); box.classList.add('open'); document.body.style.overflow = 'hidden'; box.querySelector('.lb-x').focus(); }
    function close() { box.classList.remove('open'); document.body.style.overflow = ''; if (last) last.focus(); }
    links.forEach(function (a, i) {
      a.addEventListener('click', function (e) { e.preventDefault(); open(i); });
    });
    box.querySelector('.lb-x').addEventListener('click', close);
    box.querySelector('.lb-p').addEventListener('click', function () { show(cur - 1); });
    box.querySelector('.lb-n').addEventListener('click', function () { show(cur + 1); });
    box.addEventListener('click', function (e) { if (e.target === box) close(); });
    document.addEventListener('keydown', function (e) {
      if (!box.classList.contains('open')) return;
      if (e.key === 'Escape') close();
      else if (e.key === 'ArrowLeft') show(cur - 1);
      else if (e.key === 'ArrowRight') show(cur + 1);
    });
  }
})();
