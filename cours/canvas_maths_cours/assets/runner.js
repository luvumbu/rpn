/* ============================================================
   Moteur de démos live du cours de Maths (Canvas)
   - Chaque .demo contient un <canvas> et un <textarea> de code.
   - Le code est exécuté avec en portée : canvas, ctx, loop, on, loadImage, helpers
   - loop(fn)  : boucle d'animation (annulée automatiquement au ré-lancement)
   - on(t,e,h) : addEventListener nettoyé automatiquement
   - helpers   : petites ressources prêtes à l'emploi (générées par code)
   ============================================================ */
(function () {
  'use strict';

  /* ---------- Ressources générées (100% hors-ligne) ---------- */
  function makeJeton() {
    const c = document.createElement('canvas');
    c.width = 48; c.height = 48;
    const x = c.getContext('2d');
    x.fillStyle = '#f1c40f';
    x.beginPath(); x.arc(24, 24, 20, 0, Math.PI * 2); x.fill();
    x.strokeStyle = '#c9a227'; x.lineWidth = 3; x.stroke();
    return c;
  }

  const helpers = {
    get jeton() { if (!this._j) this._j = makeJeton(); return this._j; }
  };

  function loadImage(src) {
    return new Promise(function (res, rej) {
      const im = new Image();
      im.onload = function () { res(im); };
      im.onerror = rej;
      im.src = src;
    });
  }

  /* ---------- Agrandissement plein écran ---------- */
  let overlay = null, placeholder = null, current = null;

  function ensureOverlay() {
    if (overlay) return overlay;
    overlay = document.createElement('div');
    overlay.className = 'fs-overlay';
    const inner = document.createElement('div');
    inner.className = 'fs-inner';
    const close = document.createElement('button');
    close.className = 'fs-close';
    close.type = 'button';
    close.textContent = '✕ Fermer';
    close.addEventListener('click', closeFs);
    const host = document.createElement('div');
    host.className = 'fs-host';
    inner.appendChild(close);
    inner.appendChild(host);
    overlay.appendChild(inner);
    overlay.addEventListener('click', function (e) { if (e.target === overlay) closeFs(); });
    document.body.appendChild(overlay);
    return overlay;
  }

  function openFs(demo) {
    ensureOverlay();
    const host = overlay.querySelector('.fs-host');
    placeholder = document.createComment('demo-placeholder');
    demo.parentNode.insertBefore(placeholder, demo);
    host.appendChild(demo);
    demo.classList.add('fs-active');
    current = demo;
    document.body.classList.add('fs-lock');
    overlay.classList.add('open');
  }

  function closeFs() {
    if (!current) return;
    placeholder.parentNode.insertBefore(current, placeholder);
    placeholder.parentNode.removeChild(placeholder);
    current.classList.remove('fs-active');
    current = null;
    overlay.classList.remove('open');
    document.body.classList.remove('fs-lock');
  }

  /* ---------- Mise en place d'une démo ---------- */
  const registry = [];

  function setupDemo(demo) {
    const ta = demo.querySelector('textarea');
    const runBtn = demo.querySelector('.run');
    const resetBtn = demo.querySelector('.reset');
    const original = ta.value;

    let rafId = null;
    let listeners = [];
    let running = false;

    function clearLoop() {
      if (rafId !== null) { cancelAnimationFrame(rafId); rafId = null; }
      listeners.forEach(function (l) { l.t.removeEventListener(l.e, l.h); });
      listeners = [];
    }

    function showError(msg) {
      demo.classList.add('has-error');
      let err = demo.querySelector('.err');
      if (!err) { err = document.createElement('div'); err.className = 'err'; demo.appendChild(err); }
      err.textContent = '⚠ ' + msg;
    }
    function clearError() {
      demo.classList.remove('has-error');
      const err = demo.querySelector('.err');
      if (err) err.textContent = '';
    }

    function run() {
      clearLoop();

      // canvas neuf : supprime les anciens écouteurs éventuels
      const old = demo.querySelector('canvas');
      const canvas = old.cloneNode(false);
      old.replaceWith(canvas);
      const ctx = canvas.getContext('2d');
      ctx.clearRect(0, 0, canvas.width, canvas.height);

      function loop(fn) {
        function frame(t) {
          let cont = true;
          try { cont = fn(t); } catch (e) { showError(e.message); clearLoop(); return; }
          if (cont !== false) rafId = requestAnimationFrame(frame);
        }
        rafId = requestAnimationFrame(frame);
      }
      function on(target, type, handler) {
        target.addEventListener(type, handler);
        listeners.push({ t: target, e: type, h: handler });
      }

      try {
        const f = new Function('canvas', 'ctx', 'loop', 'on', 'loadImage', 'helpers', ta.value);
        f(canvas, ctx, loop, on, loadImage, helpers);
        clearError();
      } catch (e) {
        showError(e.message);
      }
    }

    function start() { if (!running) { running = true; run(); } }
    function stop() { running = false; clearLoop(); }

    if (runBtn) runBtn.addEventListener('click', function () { running = true; run(); });
    if (resetBtn) resetBtn.addEventListener('click', function () { ta.value = original; running = true; run(); autoSize(ta); });

    // Bouton "Agrandir" injecté dans la barre
    const bar = demo.querySelector('.bar');
    if (bar) {
      const fsBtn = document.createElement('button');
      fsBtn.type = 'button';
      fsBtn.className = 'fs';
      fsBtn.textContent = '⛶ Agrandir';
      fsBtn.addEventListener('click', function () { openFs(demo); });
      const hint = bar.querySelector('.hint');
      if (hint) bar.insertBefore(fsBtn, hint);
      else bar.appendChild(fsBtn);
    }

    autoSize(ta);
    ta.addEventListener('input', function () { autoSize(ta); });
    // Ctrl+Entrée pour exécuter
    ta.addEventListener('keydown', function (e) {
      if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') { e.preventDefault(); running = true; run(); }
    });

    registry.push({ el: demo, start: start, stop: stop });
  }

  function autoSize(ta) {
    ta.style.height = 'auto';
    ta.style.height = Math.max(120, ta.scrollHeight + 4) + 'px';
  }

  /* ---------- Activation paresseuse (perf) ---------- */
  function init() {
    document.querySelectorAll('.demo').forEach(setupDemo);

    // Échap ferme la fenêtre agrandie
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && current) closeFs();
    });

    if ('IntersectionObserver' in window) {
      const io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          const d = registry.find(function (r) { return r.el === entry.target; });
          if (!d) return;
          if (entry.isIntersecting) d.start(); else d.stop();
        });
      }, { threshold: 0.12 });
      registry.forEach(function (r) { io.observe(r.el); });
    } else {
      registry.forEach(function (r) { r.start(); });
    }

    // Surlignage du lien actif dans la sidebar
    const links = Array.from(document.querySelectorAll('.sidebar nav a[href^="#"]'));
    const map = {};
    links.forEach(function (a) {
      const id = a.getAttribute('href').slice(1);
      const sec = document.getElementById(id);
      if (sec) map[id] = a;
    });
    const spy = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) {
          links.forEach(function (a) { a.classList.remove('active'); });
          const a = map[e.target.id];
          if (a) a.classList.add('active');
        }
      });
    }, { rootMargin: '-10% 0px -75% 0px' });
    Object.keys(map).forEach(function (id) { spy.observe(document.getElementById(id)); });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
