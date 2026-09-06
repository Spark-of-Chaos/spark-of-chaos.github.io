# Spark of Chaos Static Site Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a hand-authored static website for Spark of Chaos — home, two game pages, an updates section, and the style guide that keeps it maintainable — deployed to GitHub Pages from the `www` branch.

**Architecture:** Plain HTML files at the repository root, one stylesheet, one script, no build step and no dependencies. The repository root *is* the deployed site. Design tokens live at the top of `assets/css/site.css`; per-game palettes are local CSS custom property overrides on a scope class, so one component set serves every game. JavaScript is strictly progressive enhancement.

**Tech Stack:** HTML5, CSS (custom properties, `clamp()`, `grid`), vanilla ES2020, Node ≥18 (local preview server only), GitHub Actions.

**Spec:** `docs/superpowers/specs/2026-09-06-spark-of-chaos-site-design.md` — read it before starting. Section references below (§n) point at it.

## Global Constraints

Every task's requirements implicitly include this section.

- **No build step.** What is committed is exactly what deploys.
- **No runtime dependencies, no CDN, no package manager.** There is no `package.json`. No network request may leave the origin.
- **No frameworks.** One stylesheet (`assets/css/site.css`), one script (`assets/js/site.js`).
- **Progressive enhancement.** Every page must render complete and readable with JavaScript disabled.
- **Never hardcode a colour** below the `:root` token block. Reference a custom property.
- **Filenames** are lowercase, hyphen-separated, no spaces. Update posts are `updates/YYYY-MM-DD-kebab-title.html`.
- **Every image needs `alt`** (decorative art gets `alt=""`), plus explicit `width` and `height`.
- **All video** is `preload="none"` with a `poster`.
- **All decorative motion** sits behind `@media (prefers-reduced-motion: reduce)`.
- **Branch:** work happens on `www` only. Never touch `gh-pages`, `main`, or `static-page`.
- **Commit after every task.** Conventional-commit prefixes (`feat:`, `docs:`, `chore:`).

**Verification convention.** There is no test framework — this is a static site and the user
declined a committed validation tool. Each task verifies with ad-hoc shell commands against
the local preview server. Start it once per session:

```bash
node tools/serve.js &          # serves repo root on http://localhost:8000
```

Stop it with `kill %1`. Visual confirmation is deliberately deferred to Task 12, because the
user has no browser available at time of writing.

---

## File Structure

| File | Responsibility |
|---|---|
| `index.html` | Home: hero, game strips, latest updates, studio, contact |
| `kabonk.html` | Kabonk! game page |
| `fernweh.html` | Fernweh game page |
| `404.html` | Not-found page; also the first consumer of shared chrome |
| `updates/index.html` | Post list |
| `updates/_template.html` | Post starting point, never linked, never in sitemap |
| `updates/2026-09-06-a-new-home.html` | Seed post proving the whole updates path works |
| `updates/feed.xml` | RSS 2.0, hand-maintained |
| `assets/css/site.css` | Tokens, base, components — in that order, one file |
| `assets/js/site.js` | `data-js` flag, nav, lightbox, fire canvas, video guard |
| `assets/fonts/*.woff2` | Exo 2 variable + Bruno Ace SC, self-hosted |
| `assets/img/**`, `assets/video/**` | Already committed in `044c8fe` |
| `tools/serve.js` | Zero-dependency local preview server |
| `.github/workflows/deploy.yml` | Pages deploy on push to `www` |
| `CNAME`, `.nojekyll`, `robots.txt`, `sitemap.xml` | Hosting and discovery |
| `STYLE.md` | Style guide + content recipes |
| `CLAUDE.md` | Operating instructions for future Claude sessions |

---

## Task 1: Repository skeleton, preview server, deploy workflow

**Files:**
- Create: `tools/serve.js`, `.github/workflows/deploy.yml`, `CNAME`, `.nojekyll`, `robots.txt`, `.gitignore`, `index.html` (temporary stub)

**Interfaces:**
- Consumes: nothing.
- Produces: `node tools/serve.js` serving the repository root on port 8000, with MIME types for `.html .css .js .svg .png .jpg .webm .woff2 .xml .ico`, directory-index resolution (`/` → `/index.html`, `/updates/` → `/updates/index.html`), and unknown paths falling through to `404.html` with status 404. Every later task verifies against this.

- [ ] **Step 1: Write the preview server**

Create `tools/serve.js`:

```js
#!/usr/bin/env node
// Zero-dependency static preview server. Mirrors GitHub Pages path resolution
// closely enough to catch link and MIME mistakes before pushing.
const http = require('http');
const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
const PORT = Number(process.env.PORT) || 8000;

const TYPES = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.xml': 'application/xml; charset=utf-8',
  '.txt': 'text/plain; charset=utf-8',
  '.svg': 'image/svg+xml',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.webp': 'image/webp',
  '.ico': 'image/x-icon',
  '.webm': 'video/webm',
  '.mp4': 'video/mp4',
  '.woff2': 'font/woff2',
};

function resolve(urlPath) {
  // Strip query/hash, decode, and refuse to escape ROOT.
  const clean = decodeURIComponent(urlPath.split('?')[0].split('#')[0]);
  const target = path.normalize(path.join(ROOT, clean));
  if (!target.startsWith(ROOT)) return null;
  if (fs.existsSync(target) && fs.statSync(target).isDirectory()) {
    const index = path.join(target, 'index.html');
    return fs.existsSync(index) ? index : null;
  }
  if (fs.existsSync(target) && fs.statSync(target).isFile()) return target;
  // Allow /about -> /about.html, matching Pages' extensionless behaviour.
  const withExt = target + '.html';
  if (fs.existsSync(withExt)) return withExt;
  return null;
}

const server = http.createServer((req, res) => {
  const file = resolve(req.url);
  if (!file) {
    const notFound = path.join(ROOT, '404.html');
    const body = fs.existsSync(notFound) ? fs.readFileSync(notFound) : 'Not found';
    res.writeHead(404, { 'Content-Type': 'text/html; charset=utf-8' });
    return res.end(body);
  }
  const type = TYPES[path.extname(file).toLowerCase()] || 'application/octet-stream';
  res.writeHead(200, { 'Content-Type': type, 'Cache-Control': 'no-store' });
  fs.createReadStream(file).pipe(res);
});

server.listen(PORT, () => {
  console.log(`Spark of Chaos - preview on http://localhost:${PORT}`);
});
```

- [ ] **Step 2: Create the hosting files**

```bash
printf 'sparkofchaos.com\n' > CNAME
: > .nojekyll
printf 'User-agent: *\nAllow: /\n\nSitemap: https://sparkofchaos.com/sitemap.xml\n' > robots.txt
printf '.DS_Store\nnode_modules/\n*.log\n' > .gitignore
printf '<!doctype html>\n<html lang="en"><head><meta charset="utf-8"><title>Spark of Chaos</title></head><body><p>Building.</p></body></html>\n' > index.html
```

- [ ] **Step 3: Write the deploy workflow**

Create `.github/workflows/deploy.yml`:

```yaml
name: Deploy site to GitHub Pages

on:
  push:
    branches: ["www"]
  workflow_dispatch:

permissions:
  contents: read
  pages: write
  id-token: write

concurrency:
  group: "pages"
  cancel-in-progress: false

jobs:
  deploy:
    environment:
      name: github-pages
      url: ${{ steps.deployment.outputs.page_url }}
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v4
      - name: Setup Pages
        uses: actions/configure-pages@v5
      - name: Upload artifact
        uses: actions/upload-pages-artifact@v3
        with:
          path: '.'
      - name: Deploy to GitHub Pages
        id: deployment
        uses: actions/deploy-pages@v4
```

- [ ] **Step 4: Verify the server resolves paths and MIME types correctly**

```bash
node tools/serve.js & sleep 1
echo "--- index (expect 200 text/html) ---"
curl -s -o /dev/null -w '%{http_code} %{content_type}\n' http://localhost:8000/
echo "--- existing asset (expect 200 image/png) ---"
curl -s -o /dev/null -w '%{http_code} %{content_type}\n' http://localhost:8000/assets/img/kabonk/logo.png
echo "--- video (expect 200 video/webm) ---"
curl -s -o /dev/null -w '%{http_code} %{content_type}\n' http://localhost:8000/assets/video/kabonk-trick-shots.webm
echo "--- missing page (expect 404) ---"
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8000/nope
echo "--- traversal is refused (expect 404) ---"
curl -s -o /dev/null -w '%{http_code}\n' --path-as-is http://localhost:8000/../../etc/passwd
kill %1
```

Expected: `200 text/html…`, `200 image/png`, `200 video/webm`, `404`, `404`.

- [ ] **Step 5: Verify the workflow file is valid YAML**

```bash
node -e "const s=require('fs').readFileSync('.github/workflows/deploy.yml','utf8');
if(!/branches: \[\"www\"\]/.test(s)) throw new Error('wrong trigger branch');
if(!/path: '\.'/.test(s)) throw new Error('wrong artifact path');
console.log('workflow ok');"
```

Expected: `workflow ok`.

- [ ] **Step 6: Commit**

```bash
git add tools/serve.js .github/workflows/deploy.yml CNAME .nojekyll robots.txt .gitignore index.html
git commit -m "chore: repository skeleton, preview server and Pages workflow"
```

---

## Task 2: Self-hosted fonts

**Files:**
- Create: `assets/fonts/exo-2-variable.woff2`, `assets/fonts/bruno-ace-sc-400.woff2`, `assets/fonts/README.md`

**Interfaces:**
- Consumes: nothing.
- Produces: two woff2 files at the paths above. Task 3's `@font-face` blocks reference exactly these filenames and the family names `'Exo 2'` and `'Bruno Ace SC'`.

Both are OFL-licensed. Downloaded once here and committed — at runtime nothing is fetched.

- [ ] **Step 1: Download both fonts**

These exact URLs are verified working and return latin-subset woff2:

```bash
mkdir -p assets/fonts
curl -sS --max-time 30 -o assets/fonts/exo-2-variable.woff2 \
  "https://fonts.bunny.net/exo-2/files/exo-2-latin-variable-wghtOnly-normal.woff2"
curl -sS --max-time 30 -o assets/fonts/bruno-ace-sc-400.woff2 \
  "https://fonts.bunny.net/bruno-ace-sc/files/bruno-ace-sc-latin-400-normal.woff2"
```

- [ ] **Step 2: Verify both are real woff2 files of sane size**

```bash
node -e "
const fs=require('fs');
for (const [f,min,max] of [['assets/fonts/exo-2-variable.woff2',30000,60000],
                           ['assets/fonts/bruno-ace-sc-400.woff2',10000,30000]]) {
  const b=fs.readFileSync(f);
  if (b.slice(0,4).toString()!=='wOF2') throw new Error(f+' is not woff2');
  if (b.length<min||b.length>max) throw new Error(f+' unexpected size '+b.length);
  console.log(f, b.length, 'bytes ok');
}"
```

Expected: both lines print `ok` (approximately 39772 and 15512 bytes).

- [ ] **Step 3: Record provenance**

Create `assets/fonts/README.md`:

```markdown
# Fonts

Self-hosted so the site makes no third-party requests. Both are SIL Open Font License 1.1.

| File | Family | Source |
|---|---|---|
| `exo-2-variable.woff2` | Exo 2, variable weight 300–900, latin subset | https://fonts.bunny.net/exo-2/files/exo-2-latin-variable-wghtOnly-normal.woff2 |
| `bruno-ace-sc-400.woff2` | Bruno Ace SC 400, latin subset | https://fonts.bunny.net/bruno-ace-sc/files/bruno-ace-sc-latin-400-normal.woff2 |

To refresh, re-download from the source URL and confirm the file still begins with `wOF2`.
Do not add a build step or a font CDN link.
```

- [ ] **Step 4: Commit**

```bash
git add assets/fonts
git commit -m "feat: self-host Exo 2 and Bruno Ace SC as woff2"
```

---

## Task 3: Design tokens and base stylesheet

**Files:**
- Create: `assets/css/site.css`

**Interfaces:**
- Consumes: `assets/fonts/*.woff2` from Task 2.
- Produces: the `:root` token set (§5.2), `@font-face` declarations, the fluid type scale, a reset, and base element styles. Every later task references these tokens by name and **must not introduce new raw colour values**. Palette scope classes `.kabonk` and `.fernweh` are defined here and consumed by Tasks 6, 7, 8.

- [ ] **Step 1: Write the stylesheet foundation**

Create `assets/css/site.css`:

```css
/* ==========================================================================
   Spark of Chaos — site.css
   Order: fonts → tokens → reset → base → components (appended by later tasks).
   Never hardcode a colour below the token block.
   ========================================================================== */

/* --- Fonts ---------------------------------------------------------------- */
@font-face {
  font-family: 'Exo 2';
  src: url('../fonts/exo-2-variable.woff2') format('woff2');
  font-weight: 300 900;
  font-style: normal;
  font-display: swap;
}
@font-face {
  font-family: 'Bruno Ace SC';
  src: url('../fonts/bruno-ace-sc-400.woff2') format('woff2');
  font-weight: 400;
  font-style: normal;
  font-display: swap;
}

/* --- Tokens --------------------------------------------------------------- */
:root {
  /* Canvas */
  --ink-950: #06070c;
  --ink-900: #0b0e16;
  --ink-800: #121724;
  --ink-700: #1b2233;
  --line: rgba(255, 255, 255, .09);

  /* Text */
  --text: #e9edf6;
  --text-dim: #98a2b8;

  /* Spark ramp — the original ember particle colours */
  --spark-1: #0f4170;
  --spark-2: #214846;
  --spark-3: #a9c464;
  --spark-4: #f8322b;
  --spark-5: #f28c33;
  --spark-6: #fac453;
  --spark-7: #f4f3be;
  --spark-gradient: linear-gradient(135deg,
    var(--spark-1), var(--spark-2), var(--spark-3), var(--spark-4),
    var(--spark-5), var(--spark-6), var(--spark-7));

  /* Accent */
  --accent: #f38f55;
  --accent-2: #f0ba4c;

  /* Per-game palette defaults — overridden by scope classes below */
  --game-accent: var(--accent);
  --game-accent-2: var(--accent-2);
  --game-bg: var(--ink-900);

  /* Space — 4px base */
  --space-1: .25rem;  --space-2: .5rem;   --space-3: .75rem;
  --space-4: 1rem;    --space-5: 1.5rem;  --space-6: 2rem;
  --space-8: 3rem;    --space-10: 4rem;   --space-12: 6rem;

  /* Radius */
  --radius-sm: 4px;
  --radius: 8px;
  --radius-lg: 16px;

  /* Elevation */
  --shadow: 0 2px 12px rgba(0, 0, 0, .4);
  --shadow-lg: 0 12px 40px rgba(0, 0, 0, .55);
  --glow: 0 0 32px color-mix(in srgb, var(--game-accent) 45%, transparent);

  /* Type */
  --font-display: 'Bruno Ace SC', ui-serif, Georgia, serif;
  --font-body: 'Exo 2', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;

  --step--1: clamp(.833rem, .79rem + .2vw, .938rem);
  --step-0:  clamp(1rem, .95rem + .25vw, 1.125rem);
  --step-1:  clamp(1.25rem, 1.15rem + .5vw, 1.5rem);
  --step-2:  clamp(1.6rem, 1.4rem + 1vw, 2.25rem);
  --step-3:  clamp(2rem, 1.6rem + 2vw, 3.25rem);
  --step-4:  clamp(2.75rem, 2rem + 3.75vw, 5.5rem);

  /* Layout */
  --measure: 68ch;
  --container: 72rem;
  --container-narrow: 52rem;

  /* Motion */
  --ease: cubic-bezier(.2, .6, .3, 1);
  --dur: 200ms;
}

/* Per-game palettes (§5.2) */
.kabonk  { --game-accent: #ff00ff; --game-accent-2: #a3e0fc; --game-bg: #14030f; }
.fernweh { --game-accent: #f0a45a; --game-accent-2: #7fb98a; --game-bg: #0b120e; }

/* --- Reset ---------------------------------------------------------------- */
*, *::before, *::after { box-sizing: border-box; }
* { margin: 0; }

html { -webkit-text-size-adjust: 100%; scroll-behavior: smooth; }

body {
  min-height: 100svh;
  background: var(--ink-950);
  color: var(--text);
  font-family: var(--font-body);
  font-size: var(--step-0);
  font-weight: 300;
  line-height: 1.65;
  -webkit-font-smoothing: antialiased;
  text-rendering: optimizeLegibility;
}

img, picture, video, canvas, svg { display: block; max-width: 100%; }
img, video { height: auto; }
input, button, textarea, select { font: inherit; color: inherit; }
p, h1, h2, h3, h4 { overflow-wrap: break-word; }

/* --- Base elements -------------------------------------------------------- */
h1, h2, h3 {
  font-family: var(--font-display);
  font-weight: 400;
  line-height: 1.15;
  letter-spacing: -.01em;
}
h1 { font-size: var(--step-3); }
h2 { font-size: var(--step-2); }
h3 { font-size: var(--step-1); }

a { color: var(--accent); text-decoration-thickness: 1px; text-underline-offset: .2em; }
a:hover { color: var(--accent-2); }

:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 3px;
  border-radius: var(--radius-sm);
}

hr { border: 0; height: 1px; background: var(--line); }

::selection { background: var(--accent); color: var(--ink-950); }

/* --- Layout helpers ------------------------------------------------------- */
.container { width: min(100% - var(--space-8), var(--container)); margin-inline: auto; }
.container--narrow { width: min(100% - var(--space-8), var(--container-narrow)); margin-inline: auto; }

.section { padding-block: var(--space-12); }
.section--alt { background: var(--ink-900); }

.skip-link {
  position: absolute;
  left: var(--space-4);
  top: var(--space-4);
  z-index: 100;
  padding: var(--space-2) var(--space-4);
  background: var(--accent);
  color: var(--ink-950);
  border-radius: var(--radius);
  transform: translateY(-200%);
}
.skip-link:focus { transform: translateY(0); }

.visually-hidden {
  position: absolute; width: 1px; height: 1px;
  padding: 0; margin: -1px; overflow: hidden;
  clip-path: inset(50%); white-space: nowrap;
}

/* --- Motion preference ---------------------------------------------------- */
@media (prefers-reduced-motion: reduce) {
  html { scroll-behavior: auto; }
  *, *::before, *::after {
    animation-duration: .01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: .01ms !important;
  }
}
```

- [ ] **Step 2: Verify the stylesheet parses and tokens resolve**

There is no CSS parser dependency available, so assert structurally:

```bash
node -e "
const css=require('fs').readFileSync('assets/css/site.css','utf8');
const open=(css.match(/{/g)||[]).length, close=(css.match(/}/g)||[]).length;
if(open!==close) throw new Error('unbalanced braces: '+open+' vs '+close);
for (const t of ['--ink-950','--text-dim','--spark-gradient','--accent','--font-display','--step-4','--game-accent'])
  if(!css.includes(t+':')) throw new Error('missing token '+t);
if(!/\.kabonk\s*{/.test(css)) throw new Error('missing .kabonk palette');
if(!/\.fernweh\s*{/.test(css)) throw new Error('missing .fernweh palette');
if(!css.includes('prefers-reduced-motion')) throw new Error('missing reduced-motion block');
console.log('site.css structure ok —', open, 'rules');"
```

Expected: `site.css structure ok — <n> rules`.

- [ ] **Step 3: Verify the fonts are reachable at the paths the CSS declares**

```bash
node tools/serve.js & sleep 1
curl -s -o /dev/null -w 'exo2  %{http_code} %{content_type}\n' http://localhost:8000/assets/fonts/exo-2-variable.woff2
curl -s -o /dev/null -w 'bruno %{http_code} %{content_type}\n' http://localhost:8000/assets/fonts/bruno-ace-sc-400.woff2
kill %1
```

Expected: both `200 font/woff2`.

- [ ] **Step 4: Commit**

```bash
git add assets/css/site.css
git commit -m "feat: design tokens, type scale and base stylesheet"
```

---

## Task 4: Shared chrome — nav, footer, script core, 404 page

**Files:**
- Create: `assets/js/site.js`, `404.html`
- Modify: `assets/css/site.css` (append nav and footer components)

**Interfaces:**
- Consumes: tokens from Task 3.
- Produces: the canonical `<head>`, skip link, `.nav`, `.site-footer` and closing-script markup that Tasks 6–9 copy verbatim into every page. Produces `site.js` exporting nothing but attaching four guarded behaviours; it sets `data-js` on `<html>` as its first action, and **all JS-dependent hiding must be scoped to `[data-js]`**. `404.html` is the first consumer and the fixture the verification steps hit.

- [ ] **Step 1: Write the script core with the nav and fire canvas**

Create `assets/js/site.js`:

```js
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
```

- [ ] **Step 2: Append nav and footer components to the stylesheet**

Append to `assets/css/site.css`:

```css
/* --- Navigation ----------------------------------------------------------- */
.nav {
  position: sticky;
  top: 0;
  z-index: 20;
  background: color-mix(in srgb, var(--ink-950) 78%, transparent);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--line);
}
.nav__inner {
  display: flex;
  align-items: center;
  gap: var(--space-4);
  padding-block: var(--space-3);
}
.nav__brand { display: flex; align-items: center; gap: var(--space-2); margin-right: auto; }
.nav__brand img { width: 32px; height: 32px; }
.nav__brand span {
  font-family: var(--font-display);
  font-size: var(--step-0);
  color: var(--text);
}
.nav__panel { display: flex; align-items: center; gap: var(--space-2); }
.nav__link {
  padding: var(--space-2) var(--space-3);
  border-radius: var(--radius);
  color: var(--text);
  text-decoration: none;
  transition: background var(--dur) var(--ease), color var(--dur) var(--ease);
}
.nav__link:hover, .nav__link[aria-current="page"] {
  background: color-mix(in srgb, var(--accent) 18%, transparent);
  color: var(--accent-2);
}
.nav__icon { display: flex; padding: var(--space-2); color: var(--text); }
.nav__icon:hover { color: var(--accent); }
.nav__icon svg { width: 22px; height: 22px; fill: currentColor; }

/* The toggle only exists for JS users; without JS the panel simply stays open. */
.nav__toggle { display: none; }

@media (max-width: 48rem) {
  [data-js] .nav__toggle {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-2) var(--space-3);
    background: transparent;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    cursor: pointer;
  }
  [data-js] .nav__toggle svg { width: 20px; height: 20px; fill: currentColor; }
  [data-js] .nav__panel {
    position: absolute;
    inset-inline: 0;
    top: 100%;
    display: none;
    flex-direction: column;
    align-items: stretch;
    gap: 0;
    padding: var(--space-3);
    background: var(--ink-900);
    border-bottom: 1px solid var(--line);
  }
  [data-js] .nav__panel[data-open] { display: flex; }
  .nav__panel { flex-wrap: wrap; }
}

/* --- Footer --------------------------------------------------------------- */
.site-footer {
  padding-block: var(--space-8);
  background: var(--ink-900);
  border-top: 1px solid var(--line);
  text-align: center;
  color: var(--text-dim);
  font-size: var(--step--1);
}
.site-footer__made { display: flex; align-items: center; justify-content: center; gap: var(--space-2); }
.site-footer__brand {
  background: var(--spark-gradient);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  font-weight: 700;
}
#fire { width: 32px; height: 64px; }
.fire-fallback { font-size: 1.25rem; line-height: 1; }
[data-js] .fire-fallback { display: none; }
@media (prefers-reduced-motion: reduce) {
  #fire { display: none; }
  [data-js] .fire-fallback { display: block; }
}
```

- [ ] **Step 3: Build `404.html` as the first page using the chrome**

This markup is the canonical page shell. Later tasks copy the `<head>`, skip link, `<nav>`, `<footer>` and script tag verbatim, changing only the title, description, canonical, OG tags and `<main>`.

Create `404.html`:

```html
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Page not found — Spark of Chaos</title>
  <meta name="description" content="That page does not exist. Find your way back to Spark of Chaos and our games.">
  <meta name="theme-color" content="#f38f55">
  <link rel="canonical" href="https://sparkofchaos.com/404.html">
  <link rel="icon" href="/assets/img/brand/logo.svg">
  <link rel="mask-icon" href="/assets/img/brand/logo.svg" color="#f38f55">
  <link rel="alternate" type="application/rss+xml" title="Spark of Chaos updates" href="/updates/feed.xml">
  <link rel="preload" as="font" type="font/woff2" href="/assets/fonts/exo-2-variable.woff2" crossorigin>
  <link rel="preload" as="font" type="font/woff2" href="/assets/fonts/bruno-ace-sc-400.woff2" crossorigin>
  <link rel="stylesheet" href="/assets/css/site.css">
  <script src="/assets/js/site.js" defer></script>
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>

  <nav class="nav" aria-label="Main">
    <div class="container nav__inner">
      <a class="nav__brand" href="/">
        <img src="/assets/img/brand/logo.svg" alt="Spark of Chaos" width="32" height="32">
        <span>Spark of Chaos</span>
      </a>
      <button class="nav__toggle" type="button" aria-expanded="false" aria-controls="nav-panel">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18v2H3zm0 5h18v2H3zm0 5h18v2H3z"/></svg>
        Menu
      </button>
      <div class="nav__panel" id="nav-panel">
        <a class="nav__link" href="/#games">Games</a>
        <a class="nav__link" href="/updates/">Updates</a>
        <a class="nav__link" href="/#studio">Studio</a>
        <a class="nav__icon" href="https://discord.gg/2RtsJUMprB" target="_blank" rel="noopener">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.317 4.37a19.79 19.79 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.865-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.058a.082.082 0 0 0 .031.056 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.1 13.1 0 0 1-1.872-.892.077.077 0 0 1-.008-.128c.126-.094.252-.192.372-.291a.074.074 0 0 1 .078-.011c3.928 1.793 8.18 1.793 12.061 0a.074.074 0 0 1 .079.01c.12.099.246.198.373.292a.077.077 0 0 1-.007.128c-.598.35-1.22.642-1.873.891a.077.077 0 0 0-.04.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.029 19.84 19.84 0 0 0 6.002-3.03.077.077 0 0 0 .032-.055c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.029zM8.02 15.331c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.211 0 2.176 1.095 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.211 0 2.176 1.095 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>
          <span class="visually-hidden">Discord</span>
        </a>
      </div>
    </div>
  </nav>

  <main id="main">
    <section class="section">
      <div class="container container--narrow">
        <h1>Lost the ball</h1>
        <p>That page does not exist — it may have moved, or the link may be a little off.</p>
        <p><a href="/">Back to the home page</a> or take a look at <a href="/#games">our games</a>.</p>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container">
      <p class="site-footer__made">
        Made with
        <canvas id="fire" hidden aria-hidden="true"></canvas>
        <span class="fire-fallback" aria-hidden="true">🔥</span>
        by <span class="site-footer__brand">Spark of Chaos</span>.
      </p>
    </div>
  </footer>
</body>
</html>
```

- [ ] **Step 4: Verify the chrome renders and the no-JS contract holds**

```bash
node tools/serve.js & sleep 1
node -e "
const http=require('http');
http.get('http://localhost:8000/404.html', r => {
  let b=''; r.on('data',c=>b+=c); r.on('end',()=>{
    const must=[['skip link','class=\"skip-link\"'],['nav','class=\"nav\"'],
      ['nav toggle','nav__toggle'],['aria-expanded','aria-expanded=\"false\"'],
      ['nav panel id','id=\"nav-panel\"'],['footer','site-footer'],
      ['fire canvas','id=\"fire\"'],['fire fallback','fire-fallback'],
      ['stylesheet','/assets/css/site.css'],['deferred script','site.js\" defer'],
      ['font preload','rel=\"preload\" as=\"font\"']];
    for (const [name,frag] of must) if(!b.includes(frag)) throw new Error('missing '+name);
    if(/\\ssrc=\"https?:/.test(b)||/\\shref=\"https?:\\/\\/(?!sparkofchaos)/.test(b.replace(/discord\.gg[^\"]*/g,'')))
      console.warn('note: check external refs manually');
    console.log('404.html chrome ok');
  });
});"
echo "--- JS-dependent hiding must be scoped to [data-js] ---"
grep -c 'data-js' assets/css/site.css
grep -q \"setAttribute('data-js'\" assets/js/site.js && echo 'site.js sets data-js first: ok'
kill %1
```

Expected: `404.html chrome ok`, a `data-js` count of at least 4, and the `ok` line.

- [ ] **Step 5: Verify the script is syntactically valid**

```bash
node --check assets/js/site.js && echo 'site.js syntax ok'
```

Expected: `site.js syntax ok`.

- [ ] **Step 6: Commit**

```bash
git add assets/js/site.js assets/css/site.css 404.html
git commit -m "feat: shared nav, footer, script core and 404 page"
```

---

## Task 5: Component library

**Files:**
- Modify: `assets/css/site.css` (append the component layer)

**Interfaces:**
- Consumes: tokens from Task 3.
- Produces: `.btn` `.btn--primary` `.btn--ghost` `.chip` `.card` `.card__media` `.card__body` `.team-card` `.prose` `.grid` `.grid--2` `.grid--3` `.grid--4` `.feature` `.shot` `.lightbox` `.social-row` `.game-strip` `.hero` `.hero__bloom`. Tasks 6–9 use these class names exactly. `.shot` and `.lightbox` are consumed by Task 7.

- [ ] **Step 1: Append the component layer**

Append to `assets/css/site.css`:

```css
/* --- Buttons -------------------------------------------------------------- */
.btn {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  padding: var(--space-3) var(--space-5);
  border: 1px solid transparent;
  border-radius: var(--radius);
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  transition: background var(--dur) var(--ease), color var(--dur) var(--ease),
              border-color var(--dur) var(--ease), transform var(--dur) var(--ease);
}
.btn:hover { transform: translateY(-1px); }
.btn svg { width: 20px; height: 20px; fill: currentColor; }

.btn--primary {
  background: var(--game-accent);
  color: var(--ink-950);
  box-shadow: var(--shadow);
}
.btn--primary:hover {
  background: var(--game-accent-2);
  color: var(--ink-950);
  box-shadow: var(--glow);
}

.btn--ghost {
  border-color: var(--line);
  background: transparent;
  color: var(--text);
}
.btn--ghost:hover {
  border-color: var(--game-accent);
  color: var(--game-accent);
}

.btn-row { display: flex; flex-wrap: wrap; gap: var(--space-3); }

/* --- Chip ----------------------------------------------------------------- */
.chip {
  display: inline-block;
  padding: var(--space-1) var(--space-3);
  border: 1px solid color-mix(in srgb, var(--game-accent) 45%, transparent);
  border-radius: 999px;
  background: color-mix(in srgb, var(--game-accent) 12%, transparent);
  color: var(--game-accent);
  font-size: var(--step--1);
  font-weight: 600;
  letter-spacing: .04em;
  text-transform: uppercase;
}

/* --- Grid ----------------------------------------------------------------- */
.grid { display: grid; gap: var(--space-6); }
.grid--2 { grid-template-columns: repeat(auto-fit, minmax(min(100%, 22rem), 1fr)); }
.grid--3 { grid-template-columns: repeat(auto-fit, minmax(min(100%, 18rem), 1fr)); }
.grid--4 { grid-template-columns: repeat(auto-fit, minmax(min(100%, 14rem), 1fr)); }

/* --- Card ----------------------------------------------------------------- */
.card {
  position: relative;
  display: flex;
  flex-direction: column;
  background: var(--ink-800);
  border: 1px solid var(--line);
  border-radius: var(--radius-lg);
  overflow: hidden;
  transition: border-color var(--dur) var(--ease), transform var(--dur) var(--ease);
}
.card:hover { border-color: color-mix(in srgb, var(--game-accent) 50%, transparent); transform: translateY(-2px); }
.card__media { width: 100%; aspect-ratio: 16 / 10; object-fit: cover; }
.card__body { padding: var(--space-5); display: flex; flex-direction: column; gap: var(--space-2); }
.card__meta { color: var(--text-dim); font-size: var(--step--1); }
.card h3 { font-size: var(--step-1); }
.card a { text-decoration: none; }
/* Stretch the title link across the whole card so the entire card is clickable. */
.card a::after { content: ''; position: absolute; inset: 0; }

/* --- Team card ------------------------------------------------------------ */
.team-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-6);
  background: var(--ink-800);
  border: 1px solid var(--line);
  border-radius: var(--radius-lg);
  text-align: center;
}
.team-card img {
  width: 128px; height: 128px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid var(--accent);
}
.team-card__links { display: flex; gap: var(--space-3); }
.team-card__links a { display: flex; color: var(--text-dim); }
.team-card__links a:hover { color: var(--accent); }
.team-card__links svg { width: 18px; height: 18px; fill: currentColor; }

/* --- Prose ---------------------------------------------------------------- */
.prose { max-width: var(--measure); }
.prose > * + * { margin-top: var(--space-4); }
.prose h2 { margin-top: var(--space-8); }
.prose h3 { margin-top: var(--space-6); }
.prose ul, .prose ol { padding-left: var(--space-5); }
.prose li + li { margin-top: var(--space-2); }
.prose strong { font-weight: 700; color: #fff; }
.prose blockquote {
  padding-left: var(--space-5);
  border-left: 3px solid var(--game-accent);
  color: var(--text-dim);
  font-size: var(--step-1);
}

/* --- Feature row ---------------------------------------------------------- */
.feature { display: grid; gap: var(--space-6); align-items: center; }
@media (min-width: 48rem) {
  .feature { grid-template-columns: 1fr 1fr; }
  .feature--flip .feature__media { order: 2; }
}
.feature + .feature { margin-top: var(--space-10); padding-top: var(--space-10); border-top: 1px solid var(--line); }

/* --- Hero ----------------------------------------------------------------- */
.hero {
  position: relative;
  display: grid;
  place-items: center;
  gap: var(--space-5);
  padding-block: var(--space-12);
  text-align: center;
  overflow: hidden;
}
.hero__bloom {
  position: absolute;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  background:
    radial-gradient(40rem 28rem at 50% 42%, color-mix(in srgb, var(--spark-5) 26%, transparent), transparent 70%),
    radial-gradient(26rem 20rem at 38% 55%, color-mix(in srgb, var(--spark-4) 20%, transparent), transparent 70%),
    radial-gradient(24rem 18rem at 62% 34%, color-mix(in srgb, var(--spark-6) 22%, transparent), transparent 70%),
    conic-gradient(from 0deg at 50% 45%,
      color-mix(in srgb, var(--spark-1) 18%, transparent),
      color-mix(in srgb, var(--spark-3) 18%, transparent),
      color-mix(in srgb, var(--spark-6) 18%, transparent),
      color-mix(in srgb, var(--spark-1) 18%, transparent));
  filter: blur(28px);
  animation: bloom-turn 64s linear infinite;
}
/* Fine noise so the gradients cannot band on wide gamut displays. */
.hero__bloom::after {
  content: '';
  position: absolute;
  inset: 0;
  opacity: .28;
  mix-blend-mode: overlay;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='3'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
}
@keyframes bloom-turn { to { transform: rotate(1turn); } }
@media (prefers-reduced-motion: reduce) { .hero__bloom { animation: none; } }

.hero > * { position: relative; z-index: 1; }
.hero__logo { width: min(38vw, 15rem); height: auto; }
.hero__wordmark {
  font-family: var(--font-display);
  font-size: var(--step-4);
  line-height: 1;
  background: var(--spark-gradient);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}
.hero__tagline { max-width: 40ch; color: var(--text-dim); font-size: var(--step-1); }

/* --- Game strip ----------------------------------------------------------- */
.game-strip {
  position: relative;
  padding-block: var(--space-12);
  background-color: var(--game-bg);
  background-size: cover;
  background-position: center;
  isolation: isolate;
}
.game-strip::before {
  content: '';
  position: absolute;
  inset: 0;
  z-index: -1;
  background: linear-gradient(to bottom,
    color-mix(in srgb, var(--ink-950) 82%, transparent),
    color-mix(in srgb, var(--ink-950) 62%, transparent));
}
.game-strip__inner { display: grid; gap: var(--space-6); align-items: center; }
@media (min-width: 52rem) {
  .game-strip__inner { grid-template-columns: 1fr 1fr; }
  .game-strip--flip .game-strip__media { order: 2; }
}
.game-strip__logo { max-width: 22rem; height: auto; }
.game-strip__title { font-size: var(--step-3); color: var(--game-accent); }
.game-strip__body { display: flex; flex-direction: column; gap: var(--space-4); align-items: flex-start; }

/* --- Retro CRT screen (Kabonk! scope only) -------------------------------- */
.kabonk .shot {
  position: relative;
  box-sizing: content-box;
  background: #fff;
  border-top: 3px solid #cdcdcd;
  border-bottom: 3px solid #cdcdcd;
  border-radius: 50% / 10%;
  filter: grayscale(.8) contrast(1.2) brightness(1.1);
  transform: scale(.94);
  transition: filter var(--dur) var(--ease), border-radius var(--dur) var(--ease),
              transform var(--dur) var(--ease), box-shadow var(--dur) var(--ease);
}
.kabonk .shot::before {
  content: '';
  position: absolute;
  top: 10%; right: -5%; bottom: 10%; left: -5%;
  background: #fff;
  border-left: 3px solid #cdcdcd;
  border-right: 3px solid #cdcdcd;
  border-radius: 5% / 50%;
  z-index: -1;
}
.kabonk .shot:hover,
.kabonk a:focus-visible .shot {
  filter: none;
  border-radius: var(--radius);
  border-color: var(--game-accent);
  box-shadow: var(--glow);
  transform: scale(1);
}

/* --- Lightbox ------------------------------------------------------------- */
.lightbox {
  position: fixed;
  inset: 0;
  z-index: 60;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--space-6);
  background: rgba(0, 0, 0, .9);
}
.lightbox img {
  max-width: 92vw;
  max-height: 86vh;
  border-radius: var(--radius);
  box-shadow: var(--shadow-lg);
}
.lightbox__close, .lightbox__nav {
  position: absolute;
  padding: var(--space-3);
  background: transparent;
  border: 1px solid var(--line);
  border-radius: var(--radius);
  color: #fff;
  font-size: 1.5rem;
  line-height: 1;
  cursor: pointer;
}
.lightbox__close { top: var(--space-5); right: var(--space-5); }
.lightbox__nav--prev { left: var(--space-4); }
.lightbox__nav--next { right: var(--space-4); }

/* --- Social row ----------------------------------------------------------- */
.social-row { display: flex; flex-wrap: wrap; gap: var(--space-6); justify-content: center; }
.social-row a { display: flex; color: var(--text); transition: color var(--dur) var(--ease); }
.social-row a:hover { color: var(--accent); }
.social-row svg { width: 32px; height: 32px; fill: currentColor; }
```

- [ ] **Step 2: Verify every promised class exists and braces stay balanced**

```bash
node -e "
const css=require('fs').readFileSync('assets/css/site.css','utf8');
const open=(css.match(/{/g)||[]).length, close=(css.match(/}/g)||[]).length;
if(open!==close) throw new Error('unbalanced braces '+open+'/'+close);
const need=['.btn','.btn--primary','.btn--ghost','.chip','.card','.card__media','.card__body',
  '.team-card','.prose','.grid--2','.grid--3','.grid--4','.feature','.hero','.hero__bloom',
  '.game-strip','.shot','.lightbox','.social-row'];
const missing=need.filter(c=>!css.includes(c));
if(missing.length) throw new Error('missing classes: '+missing.join(', '));
if(!/\.kabonk \.shot/.test(css)) throw new Error('CRT effect must be scoped to .kabonk');
console.log('component layer ok —', need.length, 'classes,', open, 'rules');"
```

Expected: `component layer ok — 19 classes, <n> rules`.

- [ ] **Step 3: Verify no raw colours leaked in below the token block**

Hex values are permitted only in the `:root` block, the two palette scope classes, and the
CRT chrome (`#fff`, `#cdcdcd`), which are physical screen-bezel colours rather than brand
colours.

```bash
node -e "
const css=require('fs').readFileSync('assets/css/site.css','utf8');
const body=css.slice(css.indexOf('/* --- Reset'));
const allowed=new Set(['#fff','#cdcdcd']);
const hits=(body.match(/#[0-9a-fA-F]{3,8}\b/g)||[]).filter(h=>!allowed.has(h.toLowerCase()));
if(hits.length) throw new Error('raw colours below tokens: '+[...new Set(hits)].join(', '));
console.log('no stray raw colours');"
```

Expected: `no stray raw colours`.

- [ ] **Step 4: Commit**

```bash
git add assets/css/site.css
git commit -m "feat: component layer — buttons, cards, hero bloom, game strips, CRT, lightbox"
```

---

## Task 6: Home page

**Files:**
- Create: `index.html` (replaces the Task 1 stub)

**Interfaces:**
- Consumes: chrome markup from Task 4, components from Task 5.
- Produces: anchor targets `#games`, `#studio`, `#contact` that the nav in every page links to; and the `.card` markup pattern for update teasers that Task 9 mirrors in `updates/index.html`.

Copy is verbatim from spec §9. Do not paraphrase it.

- [ ] **Step 1: Write the page**

Create `index.html`. Head, nav and footer are copied from `404.html` with the title,
description and canonical changed; only `<main>` differs:

```html
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Spark of Chaos — simulation games studio</title>
  <meta name="description" content="We are Spark of Chaos, a game development company located in Nijmegen, The Netherlands. Creators of Kabonk! and Fernweh.">
  <meta name="theme-color" content="#f38f55">
  <link rel="canonical" href="https://sparkofchaos.com/">
  <link rel="icon" href="/assets/img/brand/logo.svg">
  <link rel="mask-icon" href="/assets/img/brand/logo.svg" color="#f38f55">
  <link rel="alternate" type="application/rss+xml" title="Spark of Chaos updates" href="/updates/feed.xml">
  <meta property="og:site_name" content="Spark of Chaos">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="en_US">
  <meta property="og:url" content="https://sparkofchaos.com/">
  <meta property="og:title" content="Spark of Chaos — simulation games studio">
  <meta property="og:description" content="We are Spark of Chaos, a game development company located in Nijmegen, The Netherlands. Creators of Kabonk! and Fernweh.">
  <meta property="og:image" content="https://sparkofchaos.com/assets/img/brand/og-kabonk.png">
  <meta property="og:image:width" content="2400">
  <meta property="og:image:height" content="1260">
  <meta property="og:image:type" content="image/png">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:site" content="@SparkOfChaoses">
  <meta name="twitter:creator" content="@SparkOfChaoses">
  <link rel="preload" as="font" type="font/woff2" href="/assets/fonts/exo-2-variable.woff2" crossorigin>
  <link rel="preload" as="font" type="font/woff2" href="/assets/fonts/bruno-ace-sc-400.woff2" crossorigin>
  <link rel="stylesheet" href="/assets/css/site.css">
  <script src="/assets/js/site.js" defer></script>
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>

  <!-- Nav: copy verbatim from 404.html -->

  <main id="main">

    <section class="hero">
      <div class="hero__bloom" aria-hidden="true"></div>
      <img class="hero__logo" src="/assets/img/brand/logo.svg" alt="" width="240" height="240">
      <h1 class="hero__wordmark">Spark of Chaos</h1>
      <p class="hero__tagline">We build simulation games and experiences people enjoy.</p>
      <div class="btn-row">
        <a class="btn btn--primary" href="#games">Our games</a>
        <a class="btn btn--ghost" href="https://discord.gg/2RtsJUMprB" target="_blank" rel="noopener">Join our Discord</a>
      </div>
    </section>

    <h2 class="visually-hidden" id="games">Our games</h2>

    <section class="game-strip kabonk" style="background-image: url('/assets/img/kabonk/level-05.png')" aria-labelledby="game-kabonk">
      <div class="container game-strip__inner">
        <div class="game-strip__media">
          <img src="/assets/img/kabonk/logo.png" alt="Kabonk!" width="800" height="300" loading="lazy" decoding="async">
        </div>
        <div class="game-strip__body">
          <span class="chip">Out now</span>
          <h3 class="game-strip__title" id="game-kabonk">Kabonk!</h3>
          <p>Step into neon-lit arcades and break, blast, and push your way through bricks and obstacles in this nostalgic brick-breaking classic. Use physics, trick-shots, and power-ups in your advance to beat challenging levels and rack up high scores.</p>
          <div class="btn-row">
            <a class="btn btn--primary" href="https://store.steampowered.com/app/3628790/Kabonk/" target="_blank" rel="noopener">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11.979 0C5.678 0 .511 4.86.022 11.037l6.432 2.658c.545-.371 1.203-.59 1.912-.59.063 0 .125.004.187.009l2.863-4.142V8.81c0-2.35 1.909-4.26 4.261-4.26s4.261 1.91 4.261 4.26-1.91 4.261-4.261 4.261h-.098l-4.065 2.91c0 .052.005.104.005.156 0 1.849-1.512 3.361-3.361 3.361-1.608 0-2.958-1.138-3.282-2.65L.875 14.533C1.843 20.488 6.506 24 11.979 24c6.627 0 12.021-5.394 12.021-12.021C24 5.394 18.606 0 11.979 0z"/></svg>
              Available on Steam
            </a>
            <a class="btn btn--ghost" href="/kabonk.html">Learn more</a>
          </div>
        </div>
      </div>
    </section>

    <section class="game-strip game-strip--flip fernweh" aria-labelledby="game-fernweh">
      <div class="container game-strip__inner">
        <div class="game-strip__media">
          <p class="hero__wordmark" style="font-size: var(--step-3)">Fernweh</p>
        </div>
        <div class="game-strip__body">
          <span class="chip">In development</span>
          <h3 class="game-strip__title" id="game-fernweh">Fernweh</h3>
          <p>Plan and design expansive campsites, manage resources, accommodate travelers and enjoy organic growth toward the ultimate camping experience you&rsquo;ve always dreamed of.</p>
          <div class="btn-row">
            <a class="btn btn--primary" href="/fernweh.html">Learn more</a>
          </div>
        </div>
      </div>
    </section>

    <section class="section section--alt" aria-labelledby="latest">
      <div class="container">
        <h2 id="latest">Latest</h2>
        <div class="grid grid--3" style="margin-top: var(--space-6)">
          <!-- Task 9 fills this with the seed post card; keep newest first, max 3. -->
        </div>
        <p style="margin-top: var(--space-6)"><a href="/updates/">All updates &rarr;</a></p>
      </div>
    </section>

    <section class="section" id="studio" aria-labelledby="studio-h">
      <div class="container">
        <h2 id="studio-h">Studio</h2>
        <p class="prose" style="margin-top: var(--space-4)">We are Spark of Chaos, a game development company located in Nijmegen, The Netherlands. Creators of Kabonk! and Fernweh.</p>
        <div class="grid grid--2" style="margin-top: var(--space-8)">
          <div class="team-card">
            <img src="/assets/img/team/rob.jpg" alt="Rob" width="128" height="128" loading="lazy" decoding="async">
            <h3>Rob</h3>
            <p>When I&rsquo;m grown up, I want to be a game developer!</p>
          </div>
          <div class="team-card">
            <img src="/assets/img/team/mathieu.png" alt="Mathieu" width="128" height="128" loading="lazy" decoding="async">
            <h3>Mathieu</h3>
            <p>Developer by day, designer by night.</p>
            <div class="team-card__links">
              <a href="https://github.com/casmo" target="_blank" rel="noopener"><svg viewBox="0 0 98 96" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M48.854 0C21.839 0 0 22 0 49.217c0 21.756 13.993 40.172 33.405 46.69 2.427.49 3.316-1.059 3.316-2.362 0-1.141-.08-5.052-.08-9.127-13.59 2.934-16.42-5.867-16.42-5.867-2.184-5.704-5.42-7.17-5.42-7.17-4.448-3.015.324-3.015.324-3.015 4.934.326 7.523 5.052 7.523 5.052 4.367 7.496 11.404 5.378 14.235 4.074.404-3.178 1.699-5.378 3.074-6.6-10.839-1.141-22.243-5.378-22.243-24.283 0-5.378 1.94-9.778 5.014-13.2-.485-1.222-2.184-6.275.486-13.038 0 0 4.125-1.304 13.426 5.052a46.97 46.97 0 0 1 12.214-1.63c4.125 0 8.33.571 12.213 1.63 9.302-6.356 13.427-5.052 13.427-5.052 2.67 6.763.97 11.816.485 13.038 3.155 3.422 5.015 7.822 5.015 13.2 0 18.905-11.404 23.06-22.324 24.283 1.78 1.548 3.316 4.481 3.316 9.126 0 6.6-.08 11.897-.08 13.526 0 1.304.89 2.853 3.316 2.364 19.412-6.52 33.405-24.935 33.405-46.691C97.707 22 75.788 0 48.854 0z"/></svg><span class="visually-hidden">Mathieu on GitHub</span></a>
              <a href="https://www.linkedin.com/in/mathieuderuiter/" target="_blank" rel="noopener"><svg viewBox="0 0 382 382" aria-hidden="true"><path d="M347.445 0H34.555C15.471 0 0 15.471 0 34.555v312.889C0 366.529 15.471 382 34.555 382h312.889C366.529 382 382 366.529 382 347.444V34.555C382 15.471 366.529 0 347.445 0zM118.207 329.844c0 5.554-4.502 10.056-10.056 10.056H65.345c-5.554 0-10.056-4.502-10.056-10.056V150.403c0-5.554 4.502-10.056 10.056-10.056h42.806c5.554 0 10.056 4.502 10.056 10.056v179.441zM86.748 123.432c-22.459 0-40.666-18.207-40.666-40.666S64.289 42.1 86.748 42.1s40.666 18.207 40.666 40.666-18.206 40.666-40.666 40.666zM341.91 330.654c0 5.106-4.14 9.246-9.246 9.246H286.73c-5.106 0-9.246-4.14-9.246-9.246v-84.168c0-12.556 3.683-55.021-32.813-55.021-28.309 0-34.051 29.066-35.204 42.11v97.079c0 5.106-4.139 9.246-9.246 9.246h-44.426c-5.106 0-9.246-4.14-9.246-9.246V149.593c0-5.106 4.14-9.246 9.246-9.246h44.426c5.106 0 9.246 4.14 9.246 9.246v15.655c10.497-15.753 26.097-27.912 59.312-27.912 73.552 0 73.131 68.716 73.131 106.472v86.846z"/></svg><span class="visually-hidden">Mathieu on LinkedIn</span></a>
              <a href="https://mathieuderuiter.nl" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 6H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4h-2v4H6V8h4V6zm4-2v2h3.59l-8.3 8.29 1.42 1.42L19 7.41V11h2V4h-7z"/></svg><span class="visually-hidden">Mathieu&rsquo;s website</span></a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="section section--alt" id="contact" aria-labelledby="contact-h">
      <div class="container">
        <h2 id="contact-h" style="text-align:center">Contact</h2>
        <div class="social-row" style="margin-top: var(--space-6)">
          <!-- Discord, X, Instagram, YouTube, Bluesky.
               Reuse the Discord path from 404.html; the other four paths are in
               the old markup at:
               git show gh-pages:public/static/sparkofchaos.com/index.html
               Each anchor: target="_blank" rel="noopener", svg aria-hidden,
               plus a <span class="visually-hidden"> name. -->
        </div>
      </div>
    </section>

  </main>

  <!-- Footer: copy verbatim from 404.html -->
</body>
</html>
```

- [ ] **Step 2: Recover the four remaining social icon paths**

The X, Instagram, YouTube and Bluesky SVG paths already exist in the old build. Extract
rather than retype them:

```bash
git show gh-pages:public/static/sparkofchaos.com/index.html \
  | grep -o 'aria-label=[A-Za-z]* href=[^>]*>.\{0,40\}' | head -8
```

Copy each `<path d="…">` into the `.social-row` block, wrapped as described in the comment.

- [ ] **Step 3: Verify structure, anchors and copy fidelity**

```bash
node tools/serve.js & sleep 1
node -e "
const http=require('http');
http.get('http://localhost:8000/', r => {
  let b=''; r.on('data',c=>b+=c); r.on('end',()=>{
    const need=[['h1','hero__wordmark'],['bloom','hero__bloom'],
      ['games anchor','id=\"games\"'],['studio anchor','id=\"studio\"'],
      ['contact anchor','id=\"contact\"'],['kabonk strip','game-strip kabonk'],
      ['fernweh strip','fernweh'],['steam link','store.steampowered.com/app/3628790'],
      ['discord','discord.gg/2RtsJUMprB'],['canonical','rel=\"canonical\"'],
      ['og image','og:image']];
    for(const [n,f] of need) if(!b.includes(f)) throw new Error('missing '+n);
    // Verbatim copy check
    if(!b.includes('nostalgic brick-breaking classic')) throw new Error('Kabonk pitch altered');
    if(!b.includes('ultimate camping experience')) throw new Error('Fernweh pitch altered');
    if(!b.includes('Nijmegen, The Netherlands')) throw new Error('studio line altered');
    // Exactly one h1
    const h1=(b.match(/<h1/g)||[]).length;
    if(h1!==1) throw new Error('expected 1 h1, found '+h1);
    // Every img has alt, width and height
    for(const tag of b.match(/<img[^>]*>/g)||[]) {
      for(const attr of ['alt=','width=','height=']) if(!tag.includes(attr))
        throw new Error('img missing '+attr+': '+tag.slice(0,70));
    }
    console.log('index.html ok');
  });
});"
kill %1
```

Expected: `index.html ok`.

- [ ] **Step 4: Verify no external asset requests**

Only `discord.gg`, `store.steampowered.com`, `github.com`, `linkedin.com`,
`mathieuderuiter.nl`, `x.com`, `instagram.com`, `youtube.com` and `bsky.app` may appear,
and only as `href` on anchors — never as `src`.

```bash
grep -o 'src="https\?://[^"]*"' index.html && echo 'FAIL: external asset' || echo 'no external assets: ok'
```

Expected: `no external assets: ok`.

- [ ] **Step 5: Commit**

```bash
git add index.html
git commit -m "feat: home page — hero, game strips, studio and contact"
```

---

## Task 7: Kabonk! page, lightbox and video guard

**Files:**
- Create: `kabonk.html`
- Modify: `assets/js/site.js` (append lightbox and reduced-motion video guard)

**Interfaces:**
- Consumes: chrome (Task 4), components (Task 5), `.kabonk` palette (Task 3).
- Produces: the `[data-lightbox]` contract — an `<a href="full-image.jpg" data-lightbox="gallery-name">` wrapping a thumbnail `<img>`. `site.js` groups by the attribute value so arrow keys move within a gallery. Any future gallery uses this same contract.

All copy comes from spec §6.3. The two tables there are the source of truth.

- [ ] **Step 1: Append the lightbox and video guard to `site.js`**

Insert both `guard(...)` blocks immediately before the closing `})();` of `assets/js/site.js`:

```js
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
```

- [ ] **Step 2: Write the page**

Create `kabonk.html`. Head/nav/footer copy `404.html` with title, description, canonical and
OG changed. `<body class="kabonk">` applies the palette to the whole page.

```html
<body class="kabonk">
```

Head differences:

```html
  <title>Kabonk! — a neon brick breaker by Spark of Chaos</title>
  <meta name="description" content="Kabonk! is a remake on the classic brick breaker from the 1980s. Physics-based paddle, trick-shots, power-ups and 50+ neon arcade levels. Out now on Steam.">
  <link rel="canonical" href="https://sparkofchaos.com/kabonk.html">
  <meta property="og:url" content="https://sparkofchaos.com/kabonk.html">
  <meta property="og:title" content="Kabonk! — a neon brick breaker by Spark of Chaos">
  <meta property="og:description" content="Kabonk! is a remake on the classic brick breaker from the 1980s. Physics-based paddle, trick-shots, power-ups and 50+ neon arcade levels. Out now on Steam.">
  <meta property="og:image" content="https://sparkofchaos.com/assets/img/brand/og-kabonk.png">
```

`<main id="main">` contents:

```html
    <section class="hero" style="background-image: url('/assets/img/kabonk/level-05.png'); background-size: cover; background-position: center">
      <img class="hero__logo" style="width: min(60vw, 30rem)" src="/assets/img/kabonk/logo.png" alt="Kabonk!" width="800" height="300">
      <p><span class="chip">Out now</span></p>
      <h1 class="visually-hidden">Kabonk!</h1>
      <a class="btn btn--primary" href="https://store.steampowered.com/app/3628790/Kabonk/" target="_blank" rel="noopener">Available on Steam</a>
    </section>

    <section class="section">
      <div class="container container--narrow">
        <h2>Launch trailer</h2>
        <video style="margin-top: var(--space-5); width: 100%; border-radius: var(--radius)"
               controls preload="none"
               poster="/assets/img/kabonk/trailer-poster.png"
               width="1920" height="1080">
          <source src="/assets/video/kabonk-launch-trailer.webm" type="video/webm">
          <p><a href="/assets/video/kabonk-launch-trailer.webm">Download the launch trailer</a>.</p>
        </video>
      </div>
    </section>

    <section class="section section--alt">
      <div class="container">

        <div class="feature">
          <div class="feature__media">
            <img class="shot" src="/assets/img/kabonk/screenshot-03.jpg" alt="A neon Kabonk! arcade level mid-play" width="1280" height="720" loading="lazy" decoding="async">
          </div>
          <div class="prose">
            <p><strong>Kabonk! is a remake on the classic brick breaker from the 1980s.</strong> In Kabonk! you control a physics based paddle and navigate through multiple arcade levels by breaking bricks with different types of spheres, each with unique physics-based properties. Try smashing difficult bricks with more force or add spin-effects to reach difficult objects.</p>
          </div>
        </div>

        <div class="feature feature--flip">
          <div class="feature__media">
            <video class="shot" autoplay loop muted playsinline preload="none"
                   poster="/assets/img/kabonk/screenshot-01.jpg" width="1280" height="720">
              <source src="/assets/video/kabonk-trick-shots.webm" type="video/webm">
            </video>
          </div>
          <div class="prose">
            <p>Use <strong>physics based controls</strong> and create trick-shots to beat challenging levels.</p>
          </div>
        </div>

        <div class="feature">
          <div class="feature__media">
            <video class="shot" autoplay loop muted playsinline preload="none"
                   poster="/assets/img/kabonk/screenshot-02.jpg" width="1280" height="720">
              <source src="/assets/video/kabonk-powerups.webm" type="video/webm">
            </video>
          </div>
          <div class="prose">
            <p><strong>Unlock and use a variety of unique power-ups</strong> to gain the edge and overcome challenging levels.</p>
          </div>
        </div>

        <div class="feature feature--flip">
          <div class="feature__media">
            <video class="shot" autoplay loop muted playsinline preload="none"
                   poster="/assets/img/kabonk/screenshot-04.jpg" width="1280" height="720">
              <source src="/assets/video/kabonk-leaderboards.webm" type="video/webm">
            </video>
          </div>
          <div class="prose">
            <p>Beat challenging levels and rack up high scores and <strong>get your name in the live leader boards</strong> in game!</p>
          </div>
        </div>

        <div class="feature">
          <div class="feature__media">
            <video class="shot" autoplay loop muted playsinline preload="none"
                   poster="/assets/img/kabonk/level-14.png" width="1280" height="720">
              <source src="/assets/video/kabonk-unique-levels.webm" type="video/webm">
            </video>
          </div>
          <div class="prose">
            <p><strong>Unlock exciting and unique levels</strong> with extreme sparks &amp; bonks!</p>
          </div>
        </div>

      </div>
    </section>

    <section class="section">
      <div class="container" style="text-align:center">
        <a class="btn btn--primary" href="https://store.steampowered.com/app/3628790/Kabonk/" target="_blank" rel="noopener">Available on Steam</a>
      </div>
    </section>

    <section class="section section--alt" aria-labelledby="shots">
      <div class="container">
        <h2 id="shots">Screenshots</h2>
        <div class="grid grid--4" style="margin-top: var(--space-6)">
          <a href="/assets/img/kabonk/screenshot-01.jpg" data-lightbox="kabonk">
            <img class="shot" src="/assets/img/kabonk/screenshot-01.jpg" alt="Kabonk! screenshot one" width="640" height="360" loading="lazy" decoding="async">
          </a>
          <!-- Repeat for screenshot-02, -03, -04, incrementing the alt text.
               Same data-lightbox="kabonk" value so arrow keys walk the gallery. -->
        </div>
      </div>
    </section>

    <section class="section" aria-labelledby="levels">
      <div class="container">
        <h2 id="levels" style="text-align:center">Levels</h2>
        <p style="text-align:center; margin-top: var(--space-4)">Explore a variety of unique Kabonk! breakout levels, each offering its own distinct challenge and style.</p>
        <div class="grid grid--4" style="margin-top: var(--space-8)">

          <article class="card">
            <img class="card__media shot" src="/assets/img/kabonk/level-01.png" alt="Kabonk! Level 01" width="640" height="400" loading="lazy" decoding="async">
            <div class="card__body">
              <h3>Level 01</h3>
              <p>Enter the world of neon and blast bricks until the level is clear.</p>
            </div>
          </article>

          <!-- Repeat this <article> for the remaining seven levels, using spec §6.3's
               level table verbatim for name, image and description:
                 Level 14        level-14.png
                 Level 21        level-21.png
                 Level 32        level-32.png
                 Level 38        level-38.png
                 Level 47        level-47.png
                 Bonus level 2   level-bonus-2.png
                 Endless Level   level-endless.png -->

        </div>
      </div>
    </section>
```

- [ ] **Step 3: Verify the page, the media wiring and the lightbox contract**

```bash
node --check assets/js/site.js && echo 'site.js syntax ok'
node tools/serve.js & sleep 1
node -e "
const http=require('http');
http.get('http://localhost:8000/kabonk.html', r => {
  let b=''; r.on('data',c=>b+=c); r.on('end',()=>{
    if(!/<body class=\"kabonk\"/.test(b)) throw new Error('missing .kabonk palette scope');
    // All five feature clips restored
    for(const v of ['kabonk-launch-trailer','kabonk-trick-shots','kabonk-powerups',
                    'kabonk-leaderboards','kabonk-unique-levels'])
      if(!b.includes(v+'.webm')) throw new Error('missing video '+v);
    // Every video preload=none and has a poster
    const vids=b.match(/<video[^>]*>/g)||[];
    if(vids.length!==5) throw new Error('expected 5 videos, found '+vids.length);
    for(const v of vids){
      if(!v.includes('preload=\"none\"')) throw new Error('video without preload=none: '+v.slice(0,60));
      if(!v.includes('poster=')) throw new Error('video without poster: '+v.slice(0,60));
    }
    // Gallery + levels
    const lb=(b.match(/data-lightbox=\"kabonk\"/g)||[]).length;
    if(lb!==4) throw new Error('expected 4 lightbox links, found '+lb);
    const cards=(b.match(/<article class=\"card\">/g)||[]).length;
    if(cards!==8) throw new Error('expected 8 level cards, found '+cards);
    if(!b.includes('How long will you hold the line?')) throw new Error('endless level copy missing');
    if(!b.includes('Commander Keen theme')) throw new Error('level 21 copy missing');
    // No Matter.js
    if(/matter/i.test(b)) throw new Error('Matter.js must not be carried over');
    for(const tag of b.match(/<img[^>]*>/g)||[])
      for(const a of ['alt=','width=','height='])
        if(!tag.includes(a)) throw new Error('img missing '+a+': '+tag.slice(0,70));
    console.log('kabonk.html ok — 5 videos, 4 gallery links, 8 levels');
  });
});"
kill %1
```

Expected: `site.js syntax ok` then `kabonk.html ok — 5 videos, 4 gallery links, 8 levels`.

- [ ] **Step 4: Verify every referenced media file actually exists on disk**

```bash
node -e "
const fs=require('fs');
const html=fs.readFileSync('kabonk.html','utf8');
const refs=[...html.matchAll(/(?:src|href|poster)=\"(\/assets\/[^\"]+)\"/g)].map(m=>m[1]);
const missing=[...new Set(refs)].filter(p=>!fs.existsSync('.'+p));
if(missing.length) throw new Error('missing files:\n  '+missing.join('\n  '));
console.log('all', new Set(refs).size, 'asset references resolve');"
```

Expected: `all <n> asset references resolve`.

- [ ] **Step 5: Commit**

```bash
git add kabonk.html assets/js/site.js
git commit -m "feat: Kabonk! page with restored feature videos, gallery lightbox and levels"
```

---

## Task 8: Fernweh page

**Files:**
- Create: `fernweh.html`
- Modify: `assets/css/site.css` (append the Fernweh dusk scene)

**Interfaces:**
- Consumes: chrome (Task 4), components (Task 5), `.fernweh` palette (Task 3).
- Produces: `.dusk` — the CSS-only scene standing in for absent artwork (spec §11). It must be replaceable by a single `background-image: url(...)` declaration when real art exists.

- [ ] **Step 1: Append the dusk scene**

Append to `assets/css/site.css`:

```css
/* --- Fernweh dusk scene ---------------------------------------------------
   Stands in for artwork that does not exist yet (spec §11). To replace with a
   real image later, override exactly one declaration:
     .fernweh .dusk { background: url('/assets/img/fernweh/hero.jpg') center/cover; }
   -------------------------------------------------------------------------- */
.dusk {
  position: absolute;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  background:
    /* campfire glow */
    radial-gradient(18rem 12rem at 50% 78%, color-mix(in srgb, var(--game-accent) 42%, transparent), transparent 68%),
    /* treeline */
    conic-gradient(from 200deg at 12% 100%, transparent 0 38%, color-mix(in srgb, var(--game-accent-2) 26%, #000) 38% 44%, transparent 44%),
    conic-gradient(from 200deg at 28% 100%, transparent 0 39%, color-mix(in srgb, var(--game-accent-2) 20%, #000) 39% 45%, transparent 45%),
    conic-gradient(from 160deg at 76% 100%, transparent 0 38%, color-mix(in srgb, var(--game-accent-2) 24%, #000) 38% 44%, transparent 44%),
    conic-gradient(from 160deg at 90% 100%, transparent 0 39%, color-mix(in srgb, var(--game-accent-2) 18%, #000) 39% 45%, transparent 45%),
    /* dusk sky */
    linear-gradient(to bottom,
      var(--game-bg) 0%,
      color-mix(in srgb, var(--game-accent) 14%, var(--game-bg)) 52%,
      color-mix(in srgb, var(--game-accent) 30%, var(--game-bg)) 78%,
      var(--game-bg) 100%);
}
.dusk::after {
  content: '';
  position: absolute;
  inset: 0;
  opacity: .22;
  mix-blend-mode: overlay;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='3'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
}
.definition {
  font-family: var(--font-body);
  color: var(--text-dim);
}
.definition strong { color: var(--game-accent); }
.definition .ipa { font-style: normal; letter-spacing: .02em; }
```

- [ ] **Step 2: Write the page**

Create `fernweh.html` with `<body class="fernweh">`, chrome copied from `404.html`, and head:

```html
  <title>Fernweh — a camping tycoon in development at Spark of Chaos</title>
  <meta name="description" content="Fernweh is a camping tycoon in development. Plan and design expansive campsites, manage resources, accommodate travelers and grow the ultimate camping experience.">
  <link rel="canonical" href="https://sparkofchaos.com/fernweh.html">
  <meta property="og:url" content="https://sparkofchaos.com/fernweh.html">
  <meta property="og:title" content="Fernweh — a camping tycoon in development">
  <meta property="og:description" content="Fernweh is a camping tycoon in development. Plan and design expansive campsites, manage resources, accommodate travelers and grow the ultimate camping experience.">
  <meta property="og:image" content="https://sparkofchaos.com/assets/img/brand/og-kabonk.png">
```

> Note: Fernweh has no artwork, so it borrows the studio OG image. Task 12 flags this as a
> follow-up. It is still an improvement — today the page has no distinct tags at all.

`<main id="main">`:

```html
    <section class="hero">
      <div class="dusk" aria-hidden="true"></div>
      <p><span class="chip">In development</span></p>
      <h1 class="hero__wordmark">Fernweh</h1>
      <p class="hero__tagline">camping tycoon</p>
      <p class="definition prose" style="text-align:center">
        <strong>*Fernweh</strong> <span class="ipa">/ˈfɛʁnveː/</span><br>
        noun — an ache for travel; missing a distant place one has never visited.
      </p>
    </section>

    <section class="section">
      <div class="container container--narrow prose">
        <blockquote>Plan and design expansive campsites, manage resources, accommodate travelers and enjoy organic growth toward the ultimate camping experience you&rsquo;ve always dreamed of.</blockquote>
        <p>Every time I&rsquo;m camping with my family my inspiration goes haywire. Maybe because it is one of the relaxing moments of the year or maybe the sparks of creativity go into overdrive when we are back to the roots of human life. Whatever the case, I&rsquo;m always dreaming of creating the ultimate camping tycoon. And it is a big dream, designing a tycoon game with so many good games already dominating the market. Where does one even start and more important, how does the end game even look that isn&rsquo;t just a copy of the good games out there?</p>
        <p>Well, we&rsquo;re not sure, but we both are really eager to explore. So with hundreds of hours daydreaming, discussing and debating here is the pitch.</p>
      </div>
    </section>

    <section class="section section--alt">
      <div class="container container--narrow prose">

        <h2>Pillar 1: Planning</h2>
        <p>Build facilities from scratch, cultivate plants from seeds and maintain your aging properties. This isn&rsquo;t just about setting up a campsite: you&rsquo;re preparing for generations of seasonal visitors.</p>
        <p>In plain:</p>
        <ul>
          <li>plan far ahead because you won&rsquo;t be able to click &amp; drag your properties around</li>
          <li>there is no magic bulldozer tool that cleans up trees or unmaintained vegetation</li>
        </ul>

        <h2>Pillar 2: Management</h2>
        <p>Oversee the movement of travelers, address their needs, recruit staff and adapt quickly during stressful situations.</p>
        <p>In plain:</p>
        <ul>
          <li>each visitor has their own needs and preferences, so you may need to keep an eye out where to house your families with young children</li>
          <li>you will always work at your camping but growing might require extra help, be wary though, in low winter seasons they still need payment and work</li>
          <li>animals are cute, but no one likes sleeping next to a bee hive or mosquito breeding place</li>
        </ul>

        <h2>Pillar 3: Enjoyment</h2>
        <p>Observe the changing rhythm of the seasons, gaze at the stars shimmering above the dancing campfires, and sense the pulse of nature alive in a vibrant, ever-changing, ever-growing world.</p>
        <p>In plain:</p>
        <ul>
          <li>while there are many challenges, the enjoyment of organic play, stay and growth should never bore</li>
          <li>even during quiet nights there is always something to see: a cozy campfire, a little party, a much needed toilet break</li>
          <li>seasons are a big part of gameplay: sun, rain and snow are more than ecstatic. No one likes 35 degrees in a tent or having nothing to do in rainy seasons.</li>
        </ul>

      </div>
    </section>

    <section class="section">
      <div class="container container--narrow prose">
        <p>We are very excited to keep you in the loop while developing this game. If you like the concept get involved! We are always looking for ideas or feedback!</p>
        <p class="btn-row">
          <a class="btn btn--primary" href="https://discord.gg/2RtsJUMprB" target="_blank" rel="noopener">Join our Discord</a>
          <a class="btn btn--ghost" href="/updates/">Read the devlog</a>
        </p>
      </div>
    </section>
```

- [ ] **Step 3: Verify structure and that all three pillars survived verbatim**

```bash
node tools/serve.js & sleep 1
node -e "
const http=require('http');
http.get('http://localhost:8000/fernweh.html', r => {
  let b=''; r.on('data',c=>b+=c); r.on('end',()=>{
    if(!/<body class=\"fernweh\"/.test(b)) throw new Error('missing .fernweh palette scope');
    if(!b.includes('class=\"dusk\"')) throw new Error('missing dusk scene');
    for(const p of ['Pillar 1: Planning','Pillar 2: Management','Pillar 3: Enjoyment'])
      if(!b.includes(p)) throw new Error('missing '+p);
    for(const c of ['magic bulldozer tool','mosquito breeding place','35 degrees in a tent',
                    'an ache for travel','hundreds of hours daydreaming'])
      if(!b.includes(c)) throw new Error('copy altered or missing: '+c);
    const li=(b.match(/<li>/g)||[]).length;
    if(li!==8) throw new Error('expected 8 In-plain bullets, found '+li);
    const h1=(b.match(/<h1/g)||[]).length;
    if(h1!==1) throw new Error('expected 1 h1, found '+h1);
    if(!b.includes('discord.gg/2RtsJUMprB')) throw new Error('missing Discord CTA');
    console.log('fernweh.html ok — 3 pillars, 8 bullets');
  });
});"
kill %1
```

Expected: `fernweh.html ok — 3 pillars, 8 bullets`.

- [ ] **Step 4: Verify the art-swap seam is a single declaration**

```bash
grep -c 'background:' assets/css/site.css >/dev/null
node -e "
const css=require('fs').readFileSync('assets/css/site.css','utf8');
const m=css.match(/\.dusk\s*{[^}]*}/);
if(!m) throw new Error('.dusk rule not found');
if((m[0].match(/background:/g)||[]).length!==1)
  throw new Error('.dusk must carry exactly one background declaration so art swaps cleanly');
if(!css.includes(\"assets/img/fernweh/hero.jpg\")) throw new Error('swap instructions comment missing');
console.log('art-swap seam ok');"
```

Expected: `art-swap seam ok`.

- [ ] **Step 5: Commit**

```bash
git add fernweh.html assets/css/site.css
git commit -m "feat: Fernweh page with CSS dusk scene standing in for artwork"
```

---

## Task 9: Updates section

**Files:**
- Create: `updates/index.html`, `updates/_template.html`, `updates/2026-09-06-a-new-home.html`, `updates/feed.xml`
- Modify: `index.html` (fill the `Latest` grid left empty in Task 6)

**Interfaces:**
- Consumes: chrome (Task 4), `.card` and `.prose` (Task 5).
- Produces: the four-file publish contract that STYLE.md documents in Task 11 — a new post
  page, a card in `updates/index.html`, a card in the home `Latest` grid, and an `<item>` in
  `feed.xml`. The card markup is identical in the index and on the home page, so it can be
  copy-pasted between them unchanged.

- [ ] **Step 1: Write the post template**

Create `updates/_template.html`. Every field needing a change is marked `TODO:` — this is
deliberate, it is a fill-in-the-blanks starting point, not an unfinished file:

```html
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>TODO: Post title — Spark of Chaos</title>
  <meta name="description" content="TODO: one sentence, under 160 characters.">
  <meta name="theme-color" content="#f38f55">
  <link rel="canonical" href="https://sparkofchaos.com/updates/TODO-filename.html">
  <link rel="icon" href="/assets/img/brand/logo.svg">
  <link rel="mask-icon" href="/assets/img/brand/logo.svg" color="#f38f55">
  <link rel="alternate" type="application/rss+xml" title="Spark of Chaos updates" href="/updates/feed.xml">
  <meta property="og:site_name" content="Spark of Chaos">
  <meta property="og:type" content="article">
  <meta property="og:locale" content="en_US">
  <meta property="og:url" content="https://sparkofchaos.com/updates/TODO-filename.html">
  <meta property="og:title" content="TODO: Post title">
  <meta property="og:description" content="TODO: one sentence, under 160 characters.">
  <meta property="og:image" content="https://sparkofchaos.com/assets/img/brand/og-kabonk.png">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:site" content="@SparkOfChaoses">
  <link rel="preload" as="font" type="font/woff2" href="/assets/fonts/exo-2-variable.woff2" crossorigin>
  <link rel="preload" as="font" type="font/woff2" href="/assets/fonts/bruno-ace-sc-400.woff2" crossorigin>
  <link rel="stylesheet" href="/assets/css/site.css">
  <script src="/assets/js/site.js" defer></script>
</head>
<!-- TODO: set the palette scope — class="kabonk", class="fernweh", or omit for studio news -->
<body>
  <a class="skip-link" href="#main">Skip to content</a>

  <!-- Nav: copy verbatim from /404.html -->

  <main id="main">
    <article class="section">
      <div class="container container--narrow">
        <p><span class="chip">TODO: Kabonk! | Fernweh | Studio</span></p>
        <h1 style="margin-top: var(--space-4)">TODO: Post title</h1>
        <p class="card__meta"><time datetime="TODO: 2026-09-06">TODO: 6 September 2026</time></p>

        <div class="prose" style="margin-top: var(--space-6)">
          <p>TODO: body copy.</p>
        </div>

        <p style="margin-top: var(--space-8)"><a href="/updates/">&larr; All updates</a></p>
      </div>
    </article>
  </main>

  <!-- Footer: copy verbatim from /404.html -->
</body>
</html>
```

- [ ] **Step 2: Write the seed post**

Create `updates/2026-09-06-a-new-home.html` from the template, replacing every `TODO:`.
Chip `Studio`, no palette class, canonical and `og:url`
`https://sparkofchaos.com/updates/2026-09-06-a-new-home.html`, title *"A new home for Spark
of Chaos"*, `<time datetime="2026-09-06">6 September 2026</time>`. Body:

```html
        <div class="prose" style="margin-top: var(--space-6)">
          <p>We rebuilt sparkofchaos.com from scratch. The old site was generated by a content management system; this one is plain HTML, CSS and JavaScript, with no build step and no dependencies. It loads faster, it will still work in ten years, and it is far easier for us to update.</p>
          <p>The more interesting part is what it makes room for: this page. <a href="/fernweh.html">Fernweh</a> is in active development, and we would rather show that work as it happens than disappear for a year and reappear with a trailer. Expect notes on what we are building, what we got wrong, and what we are still arguing about.</p>
          <p>If you want to follow along, <a href="/updates/feed.xml">subscribe to the feed</a> or come and talk to us on <a href="https://discord.gg/2RtsJUMprB" target="_blank" rel="noopener">Discord</a>. And if you have not played <a href="/kabonk.html">Kabonk!</a> yet, it is out now on Steam.</p>
        </div>
```

- [ ] **Step 3: Write the index**

Create `updates/index.html` with chrome, title *"Updates — Spark of Chaos"*, canonical and
`og:url` `https://sparkofchaos.com/updates/`, meta description *"Notes from the studio on
what we are building — devlogs for Fernweh, patch notes for Kabonk!, and news from Spark of
Chaos."*, and:

```html
    <section class="section">
      <div class="container">
        <h1>Updates</h1>
        <p class="prose" style="margin-top: var(--space-4)">Notes from the studio on what we are building.</p>

        <div class="grid grid--3" style="margin-top: var(--space-8)">

          <article class="card">
            <div class="card__body">
              <p class="card__meta"><time datetime="2026-09-06">6 September 2026</time> · <span class="chip">Studio</span></p>
              <h3><a href="/updates/2026-09-06-a-new-home.html">A new home for Spark of Chaos</a></h3>
              <p>A rebuilt site with no build step, no dependencies — and room for a devlog.</p>
            </div>
          </article>

          <!-- Newest first. Paste new cards directly above this comment. -->

        </div>
      </div>
    </section>
```

- [ ] **Step 4: Write the feed**

Create `updates/feed.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title>Spark of Chaos — updates</title>
    <link>https://sparkofchaos.com/updates/</link>
    <description>Notes from the studio on what we are building.</description>
    <language>en-us</language>
    <atom:link href="https://sparkofchaos.com/updates/feed.xml" rel="self" type="application/rss+xml"/>
    <item>
      <title>A new home for Spark of Chaos</title>
      <link>https://sparkofchaos.com/updates/2026-09-06-a-new-home.html</link>
      <guid isPermaLink="true">https://sparkofchaos.com/updates/2026-09-06-a-new-home.html</guid>
      <pubDate>Sun, 06 Sep 2026 09:00:00 +0000</pubDate>
      <description>A rebuilt site with no build step, no dependencies — and room for a devlog.</description>
    </item>
    <!-- Newest first. Paste new items directly above this comment. -->
  </channel>
</rss>
```

- [ ] **Step 5: Fill the home page Latest grid**

In `index.html`, replace the placeholder comment inside the `Latest` grid with the same
`<article class="card">` block from Step 3, unchanged.

- [ ] **Step 6: Verify the whole updates path**

```bash
node tools/serve.js & sleep 1
node -e "
const http=require('http');
function get(p){return new Promise((res,rej)=>http.get('http://localhost:8000'+p,r=>{
  let b='';r.on('data',c=>b+=c);r.on('end',()=>res({code:r.statusCode,body:b,type:r.headers['content-type']}));}).on('error',rej));}
(async()=>{
  const idx=await get('/updates/');
  if(idx.code!==200) throw new Error('updates index not served at /updates/');
  if(!idx.body.includes('2026-09-06-a-new-home.html')) throw new Error('seed post not linked from index');

  const post=await get('/updates/2026-09-06-a-new-home.html');
  if(post.code!==200) throw new Error('seed post 404');
  if(/TODO:/.test(post.body)) throw new Error('seed post still contains TODO markers');
  if(!post.body.includes('datetime=\"2026-09-06\"')) throw new Error('post missing machine-readable date');
  if((post.body.match(/<h1/g)||[]).length!==1) throw new Error('post needs exactly one h1');

  const feed=await get('/updates/feed.xml');
  if(feed.code!==200) throw new Error('feed 404');
  if(!/application\/xml/.test(feed.type)) throw new Error('feed served as '+feed.type);
  if((feed.body.match(/<item>/g)||[]).length!==(feed.body.match(/<\/item>/g)||[]).length)
    throw new Error('feed items unbalanced');
  if(!feed.body.includes('2026-09-06-a-new-home.html')) throw new Error('seed post missing from feed');

  const home=await get('/');
  if(!home.body.includes('2026-09-06-a-new-home.html')) throw new Error('home Latest teaser not filled');

  const tpl=await get('/updates/_template.html');
  if(!/TODO:/.test(tpl.body)) throw new Error('template should keep its TODO markers');

  console.log('updates path ok — index, post, feed, home teaser all wired');
})();"
kill %1
```

Expected: `updates path ok — index, post, feed, home teaser all wired`.

- [ ] **Step 7: Commit**

```bash
git add updates index.html
git commit -m "feat: updates section with post pages, index, RSS feed and home teaser"
```

---

## Task 10: Sitemap and cross-page metadata audit

**Files:**
- Create: `sitemap.xml`
- Modify: any page whose metadata the audit finds wanting

**Interfaces:**
- Consumes: all pages from Tasks 4, 6, 7, 8, 9.
- Produces: `sitemap.xml` listing the six public pages. `updates/_template.html` is
  deliberately excluded.

- [ ] **Step 1: Write the sitemap**

Create `sitemap.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc>https://sparkofchaos.com/</loc><changefreq>monthly</changefreq><priority>1.0</priority></url>
  <url><loc>https://sparkofchaos.com/kabonk.html</loc><changefreq>monthly</changefreq><priority>0.9</priority></url>
  <url><loc>https://sparkofchaos.com/fernweh.html</loc><changefreq>monthly</changefreq><priority>0.9</priority></url>
  <url><loc>https://sparkofchaos.com/updates/</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>
  <url><loc>https://sparkofchaos.com/updates/2026-09-06-a-new-home.html</loc><lastmod>2026-09-06</lastmod><priority>0.5</priority></url>
</urlset>
```

- [ ] **Step 2: Audit metadata across every page**

This is the step that fixes the live bug from spec §12 — three pages currently share one
description and one OG image, so Fernweh previews as Kabonk!.

```bash
node -e "
const fs=require('fs');
const pages=['index.html','kabonk.html','fernweh.html','404.html',
             'updates/index.html','updates/2026-09-06-a-new-home.html'];
const seenTitle=new Map(), seenDesc=new Map();
for (const p of pages) {
  const h=fs.readFileSync(p,'utf8');
  const title=(h.match(/<title>([^<]*)<\/title>/)||[])[1];
  const desc=(h.match(/<meta name=\"description\" content=\"([^\"]*)\"/)||[])[1];
  const canon=(h.match(/rel=\"canonical\" href=\"([^\"]*)\"/)||[])[1];
  if(!title) throw new Error(p+': no title');
  if(!desc) throw new Error(p+': no description');
  if(!canon) throw new Error(p+': no canonical');
  if(desc.length>160) throw new Error(p+': description '+desc.length+' chars, max 160');
  if(seenTitle.has(title)) throw new Error('duplicate title: '+p+' and '+seenTitle.get(title));
  if(seenDesc.has(desc)) throw new Error('duplicate description: '+p+' and '+seenDesc.get(desc));
  seenTitle.set(title,p); seenDesc.set(desc,p);
  if(!h.includes('rel=\"alternate\" type=\"application/rss+xml\"')) throw new Error(p+': no RSS autodiscovery');
  if(!h.includes('/assets/css/site.css')) throw new Error(p+': stylesheet not linked');
  if(!h.includes('lang=\"en\"')) throw new Error(p+': missing lang');
}
console.log('metadata ok —', pages.length, 'pages, all titles and descriptions unique');"
```

Expected: `metadata ok — 6 pages, all titles and descriptions unique`. Fix any page the
audit rejects, then re-run until it passes.

- [ ] **Step 3: Verify the sitemap matches reality**

```bash
node -e "
const fs=require('fs');
const xml=fs.readFileSync('sitemap.xml','utf8');
const locs=[...xml.matchAll(/<loc>https:\/\/sparkofchaos\.com\/([^<]*)<\/loc>/g)].map(m=>m[1]);
for (const l of locs) {
  const p = l==='' ? 'index.html' : (l.endsWith('/') ? l+'index.html' : l);
  if(!fs.existsSync(p)) throw new Error('sitemap lists missing page: '+l);
}
if(xml.includes('_template')) throw new Error('template must not be in the sitemap');
if(!locs.includes('')) throw new Error('home page missing from sitemap');
console.log('sitemap ok —', locs.length, 'urls, all resolve');"
```

Expected: `sitemap ok — 5 urls, all resolve`.

- [ ] **Step 4: Commit**

```bash
git add sitemap.xml index.html kabonk.html fernweh.html 404.html updates
git commit -m "feat: sitemap and per-page metadata so each page previews correctly"
```

---

## Task 11: STYLE.md and CLAUDE.md

**Files:**
- Create: `STYLE.md`, `CLAUDE.md`

**Interfaces:**
- Consumes: everything built so far.
- Produces: the documentation layer. This is the deliverable that makes the site
  "manageable through Claude" — the user chose hand-edited HTML precisely because these two
  documents carry the conventions that a build step would otherwise enforce.

- [ ] **Step 1: Write `STYLE.md`**

It must contain these six sections, with real content — no summaries of intent:

1. **Design tokens** — a table of every custom property from `:root`, its value, and when to
   use it. Plus the rule: never write a raw colour outside `:root`.
2. **Components** — for each class in Task 5's Interfaces list, a one-line purpose and a
   copy-paste HTML snippet lifted from the page where it is actually used.
3. **Content rules** —
   - `.jpg` for photographic screenshots, `.png` for game art with flat colour, `.webm` for video, `.svg` for icons and the logo.
   - Longest edge 1920px. Screenshots ≤ 400 KB, level art ≤ 800 KB, feature clips ≤ 2.5 MB.
   - Filenames lowercase and hyphenated, prefixed by game: `assets/img/<game>/<thing>-<nn>.png`.
   - Every `<img>` needs `alt`, `width`, `height`; below the fold add `loading="lazy" decoding="async"`.
   - Every `<video>` needs `preload="none"` and a `poster`.
4. **Voice and tone** — derived from the existing copy: direct, warm, concrete, first-person
   plural, sparing exclamation, no marketing superlatives. Include three do/don't pairs
   using real sentences from the site.
5. **Recipes** — ordered, file-by-file, each naming every file to touch:
   - *Add a level to Kabonk!* — add image to `assets/img/kabonk/`, copy one `<article class="card">` in `kabonk.html`, update its `src`/`alt`/`h3`/`p`. One file, one image.
   - *Add or replace a screenshot* — image in, plus the `<a data-lightbox="kabonk">` block; keep the `data-lightbox` value identical so the gallery stays one group.
   - *Publish an update* — exactly four files: new page from `updates/_template.html`, card in `updates/index.html`, same card in the `Latest` grid in `index.html`, `<item>` in `updates/feed.xml`. Then add the `<loc>` to `sitemap.xml`.
   - *Add a game* — new `<game>.html` from an existing game page, a `.game-strip` on `index.html`, a palette scope class in `site.css`, a `<loc>` in `sitemap.xml`, an OG image.
   - *Remove a game* — delete the page, its strip on `index.html`, its assets, its palette class, its sitemap entry; then grep for inbound links.
   - *Swap in Fernweh artwork* — override the single `background` declaration on `.dusk`, as documented in the CSS comment.
   - *Change a colour or a font* — token edits only.
6. **Checks before committing** — the ad-hoc verification commands from Tasks 6, 7 and 10,
   collected in one place so they can be re-run after any content change.

- [ ] **Step 2: Write `CLAUDE.md`**

```markdown
# Spark of Chaos — website

Static site for sparkofchaos.com. Plain HTML, CSS and JavaScript. **No build step, no
dependencies, no framework.** The repository root is the deployed site: what you commit to
`www` is exactly what ships.

## Before you change anything

Read `STYLE.md`. It holds the design tokens, the component snippets, the content rules and a
step-by-step recipe for every common change. Follow the recipe rather than improvising —
there is no build step to catch mistakes.

## Rules

1. Never add a dependency, a package manager, a bundler or a CDN link. No network request
   may leave the origin.
2. Never hardcode a colour. Use a custom property from `:root` in `assets/css/site.css`.
3. Every page must render complete and readable with JavaScript disabled. JS is enhancement
   only; anything JS hides must be scoped to `[data-js]`.
4. Every `<img>` needs `alt`, `width` and `height`. Every `<video>` needs `preload="none"`
   and a `poster`.
5. Decorative motion goes behind `@media (prefers-reduced-motion: reduce)`.
6. Work on `www`. Never touch `gh-pages`, `main` or `static-page` — they are backups of the
   previous site.

## Where things live

| What | Where |
|---|---|
| Home, game pages, 404 | `index.html`, `kabonk.html`, `fernweh.html`, `404.html` |
| Devlog | `updates/` — `index.html`, `_template.html`, dated post files, `feed.xml` |
| All styling | `assets/css/site.css` — tokens first, then base, then components |
| All scripting | `assets/js/site.js` — nav, lightbox, fire canvas, video guard |
| Images and video | `assets/img/<game>/`, `assets/img/brand/`, `assets/img/team/`, `assets/video/` |
| Fonts | `assets/fonts/` — self-hosted, see its README before touching |

Per-game colours are CSS custom property overrides on a scope class (`.kabonk`,
`.fernweh`) applied to `<body>`. To restyle a game, change those three properties — not the
components.

## Preview locally

    node tools/serve.js      # http://localhost:8000

## Deploy

Push to `www`. `.github/workflows/deploy.yml` publishes the repository root to GitHub Pages.
There is nothing to build.

## Before committing

- [ ] `node --check assets/js/site.js`
- [ ] Every new image has `alt`, `width`, `height`
- [ ] Page title and meta description are unique across the site
- [ ] New page added to `sitemap.xml`; new post also added to `updates/feed.xml`
- [ ] No `https://` in any `src` attribute
- [ ] Previewed at a narrow width as well as wide

## Design decisions worth knowing

The full rationale is in `docs/superpowers/specs/2026-09-06-spark-of-chaos-site-design.md`.
Fernweh has no artwork yet, so its hero is a CSS scene (`.dusk`) built to be swapped for a
real image in one declaration. The Matter.js brick-breaker footer from the old site was
dropped deliberately — it was the only CDN dependency.
```

- [ ] **Step 3: Verify the docs match the code they describe**

Documentation that drifts is worse than none, so assert the cross-references resolve:

```bash
node -e "
const fs=require('fs');
const style=fs.readFileSync('STYLE.md','utf8');
const claude=fs.readFileSync('CLAUDE.md','utf8');
const css=fs.readFileSync('assets/css/site.css','utf8');

for (const s of ['Design tokens','Components','Content rules','Voice','Recipes'])
  if(!style.includes(s)) throw new Error('STYLE.md missing section: '+s);
if(/TODO|TBD/.test(style)) throw new Error('STYLE.md has placeholders');
if(/TODO|TBD/.test(claude)) throw new Error('CLAUDE.md has placeholders');

// Every token STYLE.md documents must exist in the stylesheet.
for (const m of style.matchAll(/\`(--[a-z0-9-]+)\`/g))
  if(!css.includes(m[1]+':')) throw new Error('STYLE.md documents unknown token '+m[1]);

// Every file path the docs name must exist.
for (const doc of [['STYLE.md',style],['CLAUDE.md',claude]])
  for (const m of doc[1].matchAll(/\`((?:assets|tools|updates|docs)\/[A-Za-z0-9._\/-]+)\`/g))
    if(!fs.existsSync(m[1]) && !m[1].includes('<')) throw new Error(doc[0]+' names missing path '+m[1]);

console.log('docs cross-references ok');"
```

Expected: `docs cross-references ok`.

- [ ] **Step 4: Commit**

```bash
git add STYLE.md CLAUDE.md
git commit -m "docs: style guide and Claude operating instructions"
```

---

## Task 12: Whole-site verification

**Files:**
- Modify: whatever the checks reject

**Interfaces:**
- Consumes: the finished site.
- Produces: a verified branch ready for the user to set as default. No new interfaces.

- [ ] **Step 1: Verify no internal link 404s anywhere**

```bash
node tools/serve.js & sleep 1
node -e "
const fs=require('fs'), http=require('http');
const pages=['index.html','kabonk.html','fernweh.html','404.html',
             'updates/index.html','updates/2026-09-06-a-new-home.html'];
const targets=new Set();
for (const p of pages)
  for (const m of fs.readFileSync(p,'utf8').matchAll(/(?:href|src|poster)=\"(\/[^\"#]*)\"/g))
    targets.add(m[1]);
function head(p){return new Promise(res=>http.get('http://localhost:8000'+p,r=>{r.resume();res(r.statusCode);}));}
(async()=>{
  const bad=[];
  for (const t of targets) { const c=await head(t); if(c!==200) bad.push(t+' -> '+c); }
  if(bad.length) throw new Error('broken internal links:\n  '+bad.join('\n  '));
  console.log('all', targets.size, 'internal links resolve');
})();"
```

Expected: `all <n> internal links resolve`.

- [ ] **Step 2: Verify the no-JavaScript contract**

The site must be fully readable without `site.js`. Since we cannot disable JS in curl, we
assert the structural guarantee instead: nothing is hidden except behind `[data-js]`.

```bash
node -e "
const fs=require('fs');
const css=fs.readFileSync('assets/css/site.css','utf8');
// Find rules that hide content, and require each to be [data-js]-scoped or a known-safe case.
// These match on the SELECTOR only — the rule body is not part of \`sel\`.
const safe=[/\.skip-link/,/\.visually-hidden/,/\.lightbox/,/#fire/,/\.fire-fallback/,/\.nav__toggle/];
const offenders=[];
for (const m of css.matchAll(/([^{}]+){([^}]*display:\s*none[^}]*)}/g)) {
  const sel=m[1].trim();
  if(sel.includes('[data-js]')) continue;
  if(safe.some(r=>r.test(sel))) continue;
  offenders.push(sel);
}
if(offenders.length) throw new Error('display:none not scoped to [data-js]:\n  '+offenders.join('\n  '));
console.log('no-JS contract ok');"

grep -q 'setAttribute(.data-js' assets/js/site.js && echo 'data-js flag set: ok'
```

Expected: `no-JS contract ok` and `data-js flag set: ok`.

- [ ] **Step 3: Verify nothing leaves the origin**

```bash
node -e "
const fs=require('fs');
const pages=['index.html','kabonk.html','fernweh.html','404.html',
             'updates/index.html','updates/_template.html','updates/2026-09-06-a-new-home.html'];
const bad=[];
for (const p of pages) {
  const h=fs.readFileSync(p,'utf8');
  for (const m of h.matchAll(/(?:src|poster)=\"(https?:[^\"]*)\"/g)) bad.push(p+': '+m[1]);
  for (const m of h.matchAll(/<link[^>]*href=\"(https?:[^\"]*)\"/g)) bad.push(p+': '+m[1]);
}
if(bad.length) throw new Error('external asset requests:\n  '+bad.join('\n  '));
console.log('zero external asset requests across', pages.length, 'pages');"
```

Expected: `zero external asset requests across 7 pages`.

- [ ] **Step 4: Verify accessibility basics site-wide**

```bash
node -e "
const fs=require('fs');
const pages=['index.html','kabonk.html','fernweh.html','404.html',
             'updates/index.html','updates/2026-09-06-a-new-home.html'];
for (const p of pages) {
  const h=fs.readFileSync(p,'utf8');
  if((h.match(/<h1/g)||[]).length!==1) throw new Error(p+': needs exactly one h1');
  if(!h.includes('class=\"skip-link\"')) throw new Error(p+': missing skip link');
  if(!h.includes('id=\"main\"')) throw new Error(p+': skip-link target missing');
  for (const tag of h.match(/<img[^>]*>/g)||[])
    for (const a of ['alt=','width=','height='])
      if(!tag.includes(a)) throw new Error(p+': img missing '+a);
  for (const tag of h.match(/<a [^>]*target=\"_blank\"[^>]*>/g)||[])
    if(!tag.includes('rel=')) throw new Error(p+': target=_blank without rel=noopener');
  // Icon-only links need an accessible name.
  for (const m of h.matchAll(/<a [^>]*>\s*<svg[\s\S]*?<\/a>/g))
    if(!/visually-hidden|aria-label/.test(m[0])) throw new Error(p+': icon link without accessible name');
}
console.log('accessibility basics ok across', pages.length, 'pages');"
```

Expected: `accessibility basics ok across 6 pages`.

- [ ] **Step 5: Confirm the backup branches are untouched**

```bash
git fetch origin --quiet
for b in gh-pages main static-page; do
  local_sha=$(git rev-parse origin/$b)
  echo "$b @ $local_sha"
done
git log --oneline origin/gh-pages -1
echo "--- www must share no history with gh-pages ---"
git merge-base www origin/gh-pages 2>/dev/null && echo 'FAIL: www is not an orphan' || echo 'www is a clean orphan branch: ok'
```

Expected: the three branch SHAs print, and `www is a clean orphan branch: ok`.

- [ ] **Step 6: Push the branch**

```bash
git push -u origin www
```

The user sets the default branch themselves — do not attempt it.

- [ ] **Step 7: Deferred visual checks**

The user has no browser available at the time of writing, so these could not be automated
away and must be run later. Record them in the final report rather than silently skipping.

When a browser is available, confirm at 375px, 768px and 1440px:

- [ ] Hero spark bloom reads as intentional, no gradient banding
- [ ] Mobile nav opens, closes on Esc/outside click, returns focus to the toggle
- [ ] Kabonk! CRT effect: desaturated at rest, full colour with magenta glow on hover
- [ ] Lightbox: opens, arrows walk the four screenshots, Esc closes, focus returns
- [ ] Footer fire canvas animates and pauses when scrolled out of view
- [ ] Fernweh dusk scene holds up next to Kabonk!'s real art
- [ ] With `prefers-reduced-motion` on: bloom static, fire replaced by glyph, clips paused
- [ ] Deploy succeeds and `sparkofchaos.com` serves the new site

- [ ] **Step 8: Final commit**

```bash
git add -A
git commit -m "chore: whole-site verification fixes" --allow-empty
git push
```

---

## Notes for the executor

- **Copy is not yours to improve.** Kabonk! and Fernweh text is the studio's existing
  voice, carried over verbatim per spec §9. Fix a genuine typo, do not rewrite a sentence.
- **Chrome duplication is intentional.** Nav and footer are repeated across seven files
  because the alternative is a build step, which the user explicitly rejected. When you
  change one, change all — `grep -l 'nav__panel' *.html updates/*.html` finds them.
- **`updates/_template.html` keeps its `TODO:` markers.** They are the interface. Task 12's
  external-request check includes it; the metadata audit in Task 10 does not.
- **If a verification command fails, fix the code, not the command** — unless the command
  is genuinely wrong, in which case fix it and say so in the task report.
- **One deliberate divergence from the spec.** Spec §6.1 describes the footer as carrying
  the fire canvas, a studio line, a social row and a copyright. The plan's footer (Task 4)
  carries only the fire canvas line, because the social row already exists as its own
  `#contact` section on the home page and repeating all five icons in the footer of seven
  pages is duplication without benefit. If the user wants the fuller footer, add
  `.social-row` and a copyright line to the `<footer>` in Task 4 and propagate to all
  pages — the component already exists.
