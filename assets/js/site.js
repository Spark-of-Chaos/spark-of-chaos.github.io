/* Spark of Chaos — site.js
   Progressive enhancement only. Every behaviour is independently guarded so a
   failure in one cannot break another. No dependencies. */
(function () {
  'use strict';

  // Flag JS availability first, so CSS can hide things that need JS to reveal.
  document.documentElement.setAttribute('data-js', '');

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  function guard(name, fn) {
    try { fn(); } catch (err) { console.error('[site.js] ' + name + ' failed', err); }
  }

  /* --- Mobile navigation ------------------------------------------------- */
  guard('nav', function () {
    var toggle = document.querySelector('.nav__toggle');
    var panel = document.querySelector('.nav__panel');
    if (!toggle || !panel) return;

    function setOpen(open) {
      toggle.setAttribute('aria-expanded', String(open));
      panel.toggleAttribute('data-open', open);
    }

    toggle.addEventListener('click', function () {
      setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
        setOpen(false);
        toggle.focus();
      }
    });

    document.addEventListener('click', function (e) {
      if (toggle.getAttribute('aria-expanded') !== 'true') return;
      if (panel.contains(e.target) || toggle.contains(e.target)) return;
      setOpen(false);
    });

    panel.addEventListener('click', function (e) {
      if (e.target.closest('a')) setOpen(false);
    });
  });

  /* --- Footer fire canvas ------------------------------------------------ */
  guard('fire', function () {
    var canvas = document.getElementById('fire');
    if (!canvas || !canvas.getContext) return;
    if (reduceMotion.matches) return; // CSS shows the static glyph instead

    canvas.hidden = false;
    var ctx = canvas.getContext('2d');
    var W = (canvas.width = 160);
    var H = (canvas.height = 320);
    var COUNT = 150;
    var embers = [];
    var tick = 0;
    var raf = null;
    var onScreen = false;

    function rand(min, max) { return Math.floor(Math.random() * (max - min + 1) + min); }

    function Ember() { this.reset(); }
    Ember.prototype.reset = function () {
      this.startRadius = rand(5, 25);
      this.radius = this.startRadius;
      this.x = W / 2 + rand(-3, 3);
      this.y = 173; // emitter sits high in the box; embers burn up from here
      this.vx = 0;
      this.vy = 0;
      this.hue = rand(tick - 1, tick + 1); // cycles the spectrum over time
      this.sat = rand(50, 100);
      this.light = rand(20, 70);
      this.startAlpha = rand(1, 10) / 100;
      this.alpha = this.startAlpha;
      this.decay = 0.1;
      this.startLife = 7;
      this.life = this.startLife;
      this.lineWidth = rand(1, 3);
    };
    Ember.prototype.update = function () {
      this.vx += rand(-100, 100) / 1500;
      this.vy -= this.life / 50;
      this.x += this.vx;
      this.y += this.vy;
      var ratio = this.life / this.startLife;
      this.alpha = this.startAlpha * ratio;
      this.radius = this.startRadius * ratio;
      this.life -= this.decay;
      if (this.life <= this.decay ||
          this.x < -this.radius || this.x > W + this.radius ||
          this.y < -this.radius || this.y > H + this.radius) this.reset();
    };
    Ember.prototype.draw = function () {
      ctx.beginPath();
      ctx.arc(this.x, this.y, Math.max(this.radius, 0), 0, Math.PI * 2, false);
      var colour = 'hsla(' + this.hue + ', ' + this.sat + '%, ' + this.light + '%, ' + this.alpha + ')';
      ctx.fillStyle = colour;
      ctx.strokeStyle = colour;
      ctx.lineWidth = this.lineWidth;
      ctx.fill();
      ctx.stroke();
    };

    function frame() {
      ctx.globalCompositeOperation = 'destination-out';
      ctx.fillStyle = 'hsla(0, 0%, 0%, .3)';
      ctx.fillRect(0, 0, W, H);
      ctx.globalCompositeOperation = 'lighter';
      if (embers.length < COUNT) embers.push(new Ember());
      for (var i = embers.length; i--; ) { embers[i].update(); embers[i].draw(); }
      tick++;
      raf = window.requestAnimationFrame(frame);
    }

    function play() { if (!raf && onScreen && !document.hidden) frame(); }
    function pause() { if (raf) { window.cancelAnimationFrame(raf); raf = null; } }

    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (entries) {
        onScreen = entries[0].isIntersecting;
        onScreen ? play() : pause();
      }).observe(canvas);
    } else {
      onScreen = true;
      play();
    }
    document.addEventListener('visibilitychange', function () {
      document.hidden ? pause() : play();
    });
  });

  /* --- Lightbox ---------------------------------------------------------- */
  guard('lightbox', function () {
    var links = Array.prototype.slice.call(document.querySelectorAll('[data-lightbox]'));
    if (!links.length) return;

    var overlay = null;
    var group = [];
    var index = 0;
    var opener = null;

    function render() {
      var link = group[index];
      overlay.querySelector('img').src = link.getAttribute('href');
      overlay.querySelector('img').alt = (link.querySelector('img') || {}).alt || '';
      var multi = group.length > 1;
      overlay.querySelectorAll('.lightbox__nav').forEach(function (b) { b.hidden = !multi; });
    }

    function close() {
      if (!overlay) return;
      overlay.remove();
      overlay = null;
      document.removeEventListener('keydown', onKey);
      if (opener) opener.focus();
    }

    function step(delta) {
      index = (index + delta + group.length) % group.length;
      render();
    }

    function onKey(e) {
      if (e.key === 'Escape') return close();
      if (e.key === 'ArrowRight') return step(1);
      if (e.key === 'ArrowLeft') return step(-1);
      if (e.key !== 'Tab') return;
      // Focus trap: cycle within the overlay's buttons.
      var focusable = overlay.querySelectorAll('button:not([hidden])');
      var first = focusable[0];
      var last = focusable[focusable.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }

    function open(link) {
      opener = link;
      var name = link.getAttribute('data-lightbox');
      group = links.filter(function (l) { return l.getAttribute('data-lightbox') === name; });
      index = group.indexOf(link);

      overlay = document.createElement('div');
      overlay.className = 'lightbox';
      overlay.setAttribute('role', 'dialog');
      overlay.setAttribute('aria-modal', 'true');
      overlay.setAttribute('aria-label', 'Image viewer');
      overlay.innerHTML =
        '<button class="lightbox__close" type="button" aria-label="Close">&times;</button>' +
        '<button class="lightbox__nav lightbox__nav--prev" type="button" aria-label="Previous image">&#8249;</button>' +
        '<img src="" alt="">' +
        '<button class="lightbox__nav lightbox__nav--next" type="button" aria-label="Next image">&#8250;</button>';

      overlay.querySelector('.lightbox__close').addEventListener('click', close);
      overlay.querySelector('.lightbox__nav--prev').addEventListener('click', function () { step(-1); });
      overlay.querySelector('.lightbox__nav--next').addEventListener('click', function () { step(1); });
      overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });

      document.body.appendChild(overlay);
      render();
      document.addEventListener('keydown', onKey);
      overlay.querySelector('.lightbox__close').focus();
    }

    links.forEach(function (link) {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        open(link);
      });
    });
  });

  /* --- Reduced-motion video guard ---------------------------------------- */
  guard('video', function () {
    var clips = document.querySelectorAll('video[autoplay]');
    if (!clips.length) return;
    function apply() {
      clips.forEach(function (v) {
        if (reduceMotion.matches) { v.pause(); v.removeAttribute('autoplay'); v.controls = true; }
      });
    }
    apply();
    if (reduceMotion.addEventListener) reduceMotion.addEventListener('change', apply);
  });
})();
