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

`tools/serve.js` serves the repository root on port 8000; it is the local preview and mirrors
GitHub Pages path resolution closely enough to catch link and MIME mistakes before pushing.

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

## Repo hygiene

`.superpowers/` is git-ignored scratch space (planning notes, task briefs, working files for
this kind of session). It must never be committed — if `git status` shows it as untracked
that's expected, and if it somehow becomes staged, unstage it rather than committing it.
