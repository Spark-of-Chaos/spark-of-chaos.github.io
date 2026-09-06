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
      this.y = 250;
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
})();
