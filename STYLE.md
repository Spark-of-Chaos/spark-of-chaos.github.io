# Spark of Chaos — style guide

This is the document a build step would otherwise be. There is no linter, no template engine
and no component library — just plain HTML files that copy shared markup from each other by
hand. Follow the recipes in section 5 rather than improvising; they name every file a change
touches.

## 1. Design tokens

Every colour, space, radius, shadow, font and timing value the site uses is a custom property
declared in the `:root` block of `assets/css/site.css` (lines 24–93). **Never write a raw
colour, pixel gap, or timing value below that block — reference the token instead.** If the
value you need does not exist yet, add a token to `:root`, don't inline a literal.

### Canvas (backgrounds and borders)

| Token | Value | Use for |
|---|---|---|
| `--ink-950` | `#06070c` | Page background (`body`), skip-link text colour, `::selection` text colour |
| `--ink-900` | `#0b0e16` | Alternating section background (`.section--alt`), nav panel background on mobile, footer background, default `--game-bg` |
| `--ink-800` | `#121724` | Card and team-card surfaces |
| `--ink-700` | `#1b2233` | Reserved — declared but not yet consumed by any component; still a legitimate darker-surface step if one is needed |
| `--line` | `rgba(255, 255, 255, .09)` | Hairline borders: nav bottom border, card border, hr, feature row divider |
| `--shade` | `#000` | Pure black — used **only** as the dark end of a `color-mix()`, e.g. the lightbox scrim and the Fernweh dusk treeline. Never used as a standalone background so those effects can be tuned by mixing, not by editing a flat colour. |
| `--crt-glass` | `#fff` | The physical white of a CRT screen's glass — `.kabonk .shot` background |
| `--crt-bezel` | `#cdcdcd` | The physical grey of a CRT bezel — `.kabonk .shot` top/bottom border |

### Text

| Token | Value | Use for |
|---|---|---|
| `--text` | `#e9edf6` | Default body text colour, nav links |
| `--text-dim` | `#98a2b8` | Secondary/meta text: footer copy, card meta line, team-card links, prose blockquote |
| `--text-bright` | `#fff` | Brighter than `--text`; used for `<strong>` emphasis inside `.prose` and for the lightbox close/nav button glyphs sitting over the dark scrim |

### Spark ramp

The seven-stop gradient is the site's signature — the original ember particle colours, carried
over from the old build.

| Token | Value |
|---|---|
| `--spark-1` | `#0f4170` |
| `--spark-2` | `#214846` |
| `--spark-3` | `#a9c464` |
| `--spark-4` | `#f8322b` |
| `--spark-5` | `#f28c33` |
| `--spark-6` | `#fac453` |
| `--spark-7` | `#f4f3be` |
| `--spark-gradient` | `linear-gradient(135deg, var(--spark-1) … var(--spark-7))` |

Use `--spark-gradient` (not the individual stops) for anything meant to read as "the spark of
chaos" — the hero wordmark and the footer "Spark of Chaos" wordmark are both
`background: var(--spark-gradient)` clipped to text. Individual stops (`--spark-4`, `--spark-5`,
`--spark-6`) are also mixed into the hero bloom's radial glows.

### Accent

| Token | Value | Use for |
|---|---|---|
| `--accent` | `#f38f55` | Default link colour, focus ring, skip-link background, `theme-color` meta tag |
| `--accent-2` | `#f0ba4c` | Link hover colour |

### Per-game palette

| Token | Default (`:root`) | Use for |
|---|---|---|
| `--game-accent` | `var(--accent)` | Primary button fill, chip border/text, card hover border, `.shot` hover glow — anything that should recolour per game |
| `--game-accent-2` | `var(--accent-2)` | Primary button hover fill |
| `--game-bg` | `var(--ink-900)` | Game-strip background colour before the image/scene layers over it |

These three are overridden per game by a scope class applied to `<body>` (game pages) or a
section (home page strips):

```css
.kabonk  { --game-accent: #ff00ff; --game-accent-2: #a3e0fc; --game-bg: #14030f; }
.fernweh { --game-accent: #f0a45a; --game-accent-2: #7fb98a; --game-bg: #0b120e; }
.dyneria { --game-accent: #ffc334; --game-accent-2: #c2d24b; --game-bg: #0d1226; }
```

Dyneria's gold is sampled from its own Steam wordmark, the moss green from its world art
and the navy from its store capsule background.

To restyle a game, change these three declarations only — never edit the component rules
that consume `--game-accent`/`--game-accent-2`/`--game-bg`. See recipe 5.7.

### Space (4px base)

| Token | Value | | Token | Value |
|---|---|---|---|---|
| `--space-1` | `.25rem` | | `--space-6` | `2rem` |
| `--space-2` | `.5rem` | | `--space-8` | `3rem` |
| `--space-3` | `.75rem` | | `--space-10` | `4rem` |
| `--space-4` | `1rem` | | `--space-12` | `6rem` |
| `--space-5` | `1.5rem` | | | |

Use for every `padding`, `gap` and `margin`. Section vertical rhythm uses `--space-12`; card and
button internals use `--space-2`–`--space-5`.

### Radius

| Token | Value | Use for |
|---|---|---|
| `--radius-sm` | `4px` | Focus outline corners, skip-link |
| `--radius` | `8px` | Buttons, chips-as-pills use 999px separately, images, lightbox image |
| `--radius-lg` | `16px` | Cards, team-card |

### Elevation

| Token | Value | Use for |
|---|---|---|
| `--shadow` | `0 2px 12px rgba(0, 0, 0, .4)` | Primary button resting shadow |
| `--shadow-lg` | `0 12px 40px rgba(0, 0, 0, .55)` | Lightbox image shadow |
| `--glow` | `0 0 32px color-mix(in srgb, var(--game-accent) 45%, transparent)` | Primary button hover, `.shot` hover/focus in the Kabonk CRT effect |

### Type

| Token | Value | Use for |
|---|---|---|
| `--font-display` | `'Bruno Ace SC', ui-serif, Georgia, serif` | `h1`, `h2`, `h3`, hero wordmark, nav brand, game-strip title |
| `--font-body` | `'Exo 2', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif` | Everything else |
| `--step--1` | `clamp(.833rem, .79rem + .2vw, .938rem)` | Fine print: card meta, footer, chip |
| `--step-0` | `clamp(1rem, .95rem + .25vw, 1.125rem)` | Body text (the `body` default) |
| `--step-1` | `clamp(1.25rem, 1.15rem + .5vw, 1.5rem)` | `h3`, blockquote |
| `--step-2` | `clamp(1.6rem, 1.4rem + 1vw, 2.25rem)` | `h2` |
| `--step-3` | `clamp(2rem, 1.6rem + 2vw, 3.25rem)` | `h1`, game-strip title |
| `--step-4` | `clamp(2.75rem, 2rem + 3.75vw, 5.5rem)` | Hero wordmark only |

All six are fluid (`clamp()`), so there is no separate mobile/desktop type scale to keep in sync.

### Layout

| Token | Value | Use for |
|---|---|---|
| `--measure` | `68ch` | Max width of `.prose` text blocks, for readable line length |
| `--container` | `72rem` | Max width of `.container` |
| `--container-narrow` | `52rem` | Max width of `.container--narrow` (game page bodies, devlog posts) |

### Motion

| Token | Value | Use for |
|---|---|---|
| `--ease` | `cubic-bezier(.2, .6, .3, 1)` | Every `transition`/`animation` easing |
| `--dur` | `200ms` | Every hover/focus transition duration |

The hero bloom's 64-second rotation and the Fernweh dusk scene are the only long-running
animations; both are hidden under `@media (prefers-reduced-motion: reduce)` (§172–180 and
§439 of `assets/css/site.css`). Any new decorative motion must do the same — see §3 below.

## 2. Components

One-line purpose plus a real usage snippet for each class from the component layer
(`assets/css/site.css`, "Card", "Button", "Grid", etc. sections). Copy these, don't retype them.

### `.btn`, `.btn--primary`, `.btn--ghost`

Inline-flex button. `.btn--primary` fills with `--game-accent`; `.btn--ghost` is an outlined
variant for a secondary action sitting next to a primary one. From `index.html`:

```html
<a class="btn btn--primary" href="#games">Our games</a>
<a class="btn btn--ghost" href="https://discord.gg/2RtsJUMprB" target="_blank" rel="noopener">Join our Discord</a>
```

### `.chip`

A small pill label for status ("Out now", "In development") or category ("Studio"). From
`index.html`:

```html
<span class="chip">Out now</span>
```

### `.card`, `.card__media`, `.card__body`, `.card__meta`

A surface for a linked teaser (devlog post) or an unlinked showcase item (a Kabonk level).
Update teaser, from `updates/index.html`:

```html
<article class="card">
  <div class="card__body">
    <p class="card__meta"><time datetime="2026-09-06">6 September 2026</time> · <span class="chip">Fernweh</span></p>
    <h3><a href="/updates/2026-09-06-wander-a-day.html">Wander a day, a first milestone</a></h3>
    <p>How long should a day, a season and a year take in a camping tycoon? Our first milestone: timing and beats.</p>
  </div>
</article>
```

Media card (no link at all), from `kabonk.html`:

```html
<article class="card">
  <img class="card__media shot" src="/assets/img/kabonk/level-01.png" alt="Kabonk! Level 01" width="640" height="400" loading="lazy" decoding="async">
  <div class="card__body">
    <h3>Level 01</h3>
    <p>Enter the world of neon and blast bricks until the level is clear.</p>
  </div>
</article>
```

**Warning:** `.card a::after` (in `assets/css/site.css`) stretches an invisible overlay across
the *entire* card so the whole card becomes clickable, not just the text of the link. This means
**a `.card` must contain exactly one `<a>`.** A second link inside a card is silently unclickable
— the first link's stretched overlay sits on top of it and there is no error, console warning or
visual sign that anything is wrong. No page currently does this. Do not add a second link inside
a card; if you need two actions, use two cards or move the second action outside the card.

### `.team-card`

A centred profile card for the Studio section. From `index.html`:

```html
<div class="team-card">
  <img src="/assets/img/team/rob.jpg" alt="Rob" width="128" height="128" loading="lazy" decoding="async">
  <h3>Rob</h3>
  <p>When I&rsquo;m grown up, I want to be a game developer!</p>
</div>
```

### `.prose`

Constrains a block of running text to a readable measure and adds spacing between children.
From `fernweh.html`:

```html
<div class="container container--narrow prose">
  <blockquote>Plan and design expansive campsites, manage resources, accommodate travelers and enjoy organic growth toward the ultimate camping experience you&rsquo;ve always dreamed of.</blockquote>
  <p>Every time I&rsquo;m camping with my family my inspiration goes haywire. Maybe because it is one of the relaxing moments of the year or maybe the sparks of creativity go into overdrive when we are back to the roots of human life. Whatever the case, I&rsquo;m always dreaming of creating the ultimate camping tycoon. And it is a big dream, designing a tycoon game with so many good games already dominating the market. Where does one even start and more important, how does the end game even look that isn&rsquo;t just a copy of the good games out there?</p>
  <!-- a second <p> and the closing </div> follow in fernweh.html; truncated here for length -->
```

### `.grid`, `.grid--2`, `.grid--3`, `.grid--4`

A CSS grid that auto-fits columns down to a minimum item width — 22rem, 18rem and 14rem for the
`.grid--2`, `.grid--3` and `.grid--4` modifiers respectively. Pick the modifier by how narrow an
item can get before it looks cramped. `.grid--2`, from `index.html`'s Studio section:

```html
<div class="grid grid--2" style="margin-top: var(--space-8)">
```

`.grid--4`, from `kabonk.html`'s Levels section:

```html
<div class="grid grid--4" style="margin-top: var(--space-8)">
```

### `.feature`, `.feature--flip`

A two-column media-plus-text row that stacks on narrow screens; the `.feature--flip` modifier
swaps which side the media sits on. From `kabonk.html`:

```html
<div class="feature">
  <div class="feature__media">
    <img class="shot" src="/assets/img/kabonk/screenshot-03.jpg" alt="A neon Kabonk! arcade level mid-play" width="1280" height="720" loading="lazy" decoding="async">
  </div>
  <div class="prose">
    <p><strong>Kabonk! is a remake on the classic brick breaker from the 1980s.</strong> In Kabonk! you control a physics based paddle and navigate through multiple arcade levels by breaking bricks with different types of spheres, each with unique physics-based properties. Try smashing difficult bricks with more force or add spin-effects to reach difficult objects.</p>
  </div>
</div>
```

### `.shot`

Applies the retro CRT-screen treatment (curved edges, desaturated-until-hover) but **only inside
the `.kabonk` scope** (the rule is `.kabonk .shot`, not `.shot` alone — on any other page the
class does nothing visually). From `kabonk.html`:

```html
<img class="shot" src="/assets/img/kabonk/screenshot-03.jpg" alt="A neon Kabonk! arcade level mid-play" width="1280" height="720" loading="lazy" decoding="async">
```

### The Dyneria scope (`.dyneria .hero`, `.dyneria .hero__logo`, `.dyneria .feature__media img`)

Dyneria's hero is the **Steam capsule** — `assets/img/dyneria/capsule.jpg`, which carries the
game's own gold wordmark — as an `.hero__logo`, over a world screenshot. The `<h1>` is
`visually-hidden` next to it, exactly as `kabonk.html` does with its logo.

Two things about that capsule are worth knowing before you touch it:

- **Steam publishes it at 460×215 and nothing larger.** `capsule_616x353.jpg`,
  `library_hero.jpg` and `logo.png` all 404 for this app. The committed file is that capsule
  upscaled 2.6× with Lanczos to 1200×561; it holds up because the source art is crisp, and
  the ceiling is the source, not the resampler. Do not upscale it further.
- **`.hero__logo` is `min(38vw, 15rem)` by default**, which is sized for a logo, not a
  capsule. `dyneria.html` overrides it inline to `min(84vw, 34rem)` — that caps the render at
  544px, so the 1200px file still lands at 2.2× density on a desktop and 3.7× on a phone.

The scope adds three rules in `assets/css/site.css`. A scrim on `.dyneria .hero::before`, to
push the olive-green screenshot back far enough that the capsule reads as the subject. The
`--radius`, `--line` hairline and `--shadow-lg` that lift the capsule off that backdrop. And
`padding-inline` — `.hero` has none of its own, and Dyneria is the only page that puts a
`.btn-row` inside a hero, which overflows a phone viewport without it.

Screenshots take the same plain `--radius` and hairline. **Do not use `.shot` on a Dyneria
image** — that CRT treatment is scoped to `.kabonk` and is Kabonk!'s gimmick.

The same capsule file is the page's `og:image`, so a link preview shows the branded art
rather than a screenshot. It is the one OG image not under `assets/img/brand/`: it would be
a byte-for-byte copy of the capsule, and one file cannot drift from itself.

### `.lightbox` (the `[data-lightbox]` contract)

`.lightbox` itself is a class `site.js` applies to an overlay `<div>` it creates at runtime —
you never write it in markup. What you write is the trigger contract: an anchor pointing at the
full-size image, wrapping a thumbnail `<img>`, with `data-lightbox="<gallery-name>"`. Every
anchor sharing the same gallery name becomes one arrow-key-navigable group. From `kabonk.html`:

```html
<a href="/assets/img/kabonk/screenshot-01.jpg" data-lightbox="kabonk">
  <img class="shot" src="/assets/img/kabonk/screenshot-01.jpg" alt="Kabonk! screenshot one" width="640" height="360" loading="lazy" decoding="async">
</a>
```

Without JavaScript this is a plain link to the full-size image — the gallery still works, it
just opens the image directly instead of in an overlay.

### `.social-row`

A row of icon-only links to social profiles, each with a visually-hidden text label for screen
readers. From `index.html` (one entry shown; the row repeats this pattern for Discord, X,
Instagram, YouTube and Bluesky):

```html
<div class="social-row" style="margin-top: var(--space-6)">
  <a href="https://discord.gg/2RtsJUMprB" target="_blank" rel="noopener">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.317 4.37a19.79 19.79 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.865-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.058a.082.082 0 0 0 .031.056 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.1 13.1 0 0 1-1.872-.892.077.077 0 0 1-.008-.128c.126-.094.252-.192.372-.291a.074.074 0 0 1 .078-.011c3.928 1.793 8.18 1.793 12.061 0a.074.074 0 0 1 .079.01c.12.099.246.198.373.292a.077.077 0 0 1-.007.128c-.598.35-1.22.642-1.873.891a.077.077 0 0 0-.04.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.029 19.84 19.84 0 0 0 6.002-3.03.077.077 0 0 0 .032-.055c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.029zM8.02 15.331c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.211 0 2.176 1.095 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.211 0 2.176 1.095 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>
    <span class="visually-hidden">Discord</span>
  </a>
  <!-- four more <a> entries and the closing </div> follow in index.html, same shape, for X, Instagram, YouTube and Bluesky -->
```

### `.game-strip`, `.game-strip--flip`

A full-width band presenting one game on the home page, with the game's palette scope applied
directly to the `<section>` (rather than `<body>`, since the home page shows every game). From
`index.html`:

```html
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
```

### `.hero`, `.hero__bloom`

The top-of-page banner. `.hero__bloom` is the swirling gradient-and-noise backdrop behind the
home page hero specifically — game pages use their own hero treatment (a background image on
Kabonk, `.dusk` on Fernweh) instead. From `index.html`:

```html
<section class="hero">
  <div class="hero__bloom" aria-hidden="true"></div>
  <img class="hero__logo" src="/assets/img/brand/logo.svg" alt="" width="240" height="240">
  <h1 class="hero__wordmark">Spark of Chaos</h1>
  <p class="hero__tagline">We build simulation games and experiences people enjoy.</p>
  <!-- a .btn-row with the two hero buttons and the closing </section> follow in index.html; see §2 .btn above -->
```

## 3. Content rules

- **File formats.** `.jpg` for photographic screenshots, `.png` for game art with flat colour
  (logos, level tiles), `.webm` for video, `.svg` for icons and the logo.
- **Dimensions and weight.** Longest edge 1920px. Screenshots ≤ 400 KB, level art ≤ 800 KB,
  feature clips ≤ 2.5 MB.
- **Filenames** are lowercase and hyphenated, prefixed by game:
  `assets/img/<game>/<thing>-<nn>.png` (e.g. `assets/img/kabonk/level-14.png`,
  `assets/img/kabonk/screenshot-03.jpg`). Two-digit, zero-padded numbers keep listings sorted.
- **Every `<img>` needs `alt`, `width` and `height`.** Purely decorative images (the hero logo on
  the home page, the hero bloom overlay) get `alt=""` — they are marked `aria-hidden="true"` or
  are already redundant with adjacent text. Below the fold, add `loading="lazy" decoding="async"`
  (see any `.card__media` or `.shot` in `kabonk.html` for the pattern); above-the-fold images
  (hero logos) omit `loading="lazy"` so they aren't deferred.
- **Every `<video>` needs `preload="none"` and a `poster`.** Autoplaying background clips also
  need `loop muted playsinline` — see `assets/js/site.js`'s `video` guard, which pauses and
  un-hides controls on any `video[autoplay]` when `prefers-reduced-motion: reduce` is set. Adding
  a new autoplaying clip gets this behaviour for free; you don't need to touch `site.js`.
- **Decorative motion** (anything with `animation:` that isn't user-triggered) must be disabled
  or hidden under `@media (prefers-reduced-motion: reduce)`. `.hero__bloom`'s rotation and the
  Fernweh `.dusk` scene both already do this — copy their pattern rather than inventing a new one.

## 4. Voice and tone

Direct, warm, concrete, first-person plural ("we"), sparing with exclamation marks, no marketing
superlatives ("best-in-class", "revolutionary", "unforgettable"). The copy carries a bit of
Dutch–English idiom — phrasing that reads as translated-from-Dutch rather than native
marketing copy (*"my inspiration goes haywire"*, *"I&rsquo;m always dreaming of creating the
ultimate camping tycoon"*) — and that idiom is part of the voice, not a defect to smooth over.

**Standing rule:** existing studio copy is carried over verbatim from page to page and from the
old site, and is *not* to be "improved" for house style. If you spot a genuine typo, fix the
typo — do not rewrite the sentence around it.

Three do/don't pairs. The "do" side is a real sentence from the site; the "don't" side is an
illustrative contrast showing the direction *not* to take it.

1. **Do** (`index.html` hero tagline): *"We build simulation games and experiences people
   enjoy."* — plain, concrete, first person plural.
   **Don't**: *"We craft immersive, best-in-class gaming experiences that redefine the genre."*
   — superlatives and empty adjectives the site never uses.

2. **Do** (`kabonk.html`): *"Kabonk! is a remake on the classic brick breaker from the 1980s."*
   — states exactly what the thing is.
   **Don't**: *"Kabonk! delivers an unforgettable retro rush!"* — vague, and an exclamation mark
   doing the work a concrete sentence should do.

3. **Do** (`fernweh.html`): *"Every time I&rsquo;m camping with my family my inspiration goes
   haywire."* — warm, personal, and keeps the idiom rather than correcting it to "goes into
   overdrive" or similar.
   **Don't**: *"Camping trips serve as a primary source of creative inspiration for our design
   team."* — same fact, but distant and corporate; nobody on this site talks like a press
   release.

## 5. Recipes

### 5.1 Add a level to Kabonk!

One file, one image.

1. Add the level image to `assets/img/kabonk/` — `.png`, ≤ 800 KB, longest edge 1920px, named
   `level-<nn>.png` (or `level-bonus-<n>.png` / `level-endless.png` for specials).
2. In `kabonk.html`, copy one existing `<article class="card">` from the Levels grid (see
   §2 `.card`).
3. Update its `src`, `alt` (`"Kabonk! Level <nn>"`), the `<h3>` title, and the `<p>` description.
4. Nothing else changes — no sitemap entry, no other file. Level cards aren't individually
   linked or listed elsewhere.

### 5.2 Add or replace a screenshot

1. Add the image to `assets/img/kabonk/` (or replace an existing file at the same path).
2. In `kabonk.html`, the screenshot lives inside an `<a data-lightbox="kabonk">` wrapping an
   `<img class="shot">` (see §2 `.lightbox`). Update the `src` on both the anchor's `href` and
   the `<img>`, and the `alt` text.
3. **Keep the `data-lightbox="kabonk"` value identical** — it's what keeps every screenshot in
   one arrow-key-navigable group. Changing it (or misspelling it) silently splits the gallery.

### 5.3 Publish an update

Five files, in order. Task 9 proved this path end to end with the seed post, since replaced by
`updates/2026-09-06-wander-a-day.html`.

1. **Create the post** by copying `updates/_template.html` to
   `updates/<YYYY-MM-DD>-<kebab-title>.html`. The template deliberately ships with placeholder
   text marking every field that must change — the four capital letters spelling "to-do"
   followed by a colon, in the `<title>`, the description, the canonical URL, the OG tags
   (including `og:image` — point it at an image for the post, or leave `og-kabonk.png` as the
   studio default when the post has no artwork of its own), the `<body>` opening comment
   (palette scope), the chip, the `<h1>`, the `<time>` element (twice — `datetime` and its
   visible text), and the body copy. **Every one of those placeholders must be replaced** before
   the post is real; search the finished file for that four-letter marker to check none remain.
   Also set the `<body>` class if the post belongs to a game: `class="kabonk"` or
   `class="fernweh"`; omit it for studio news, exactly as the comment in the template says.
2. **Add a card to `updates/index.html`.** Copy the existing `<article class="card">` block from
   its post list and fill in the date, `<time datetime>`, chip, link `href`, title and teaser
   sentence. Paste the new card directly below the
   `<!-- Newest first. Paste new cards directly below this comment. -->` marker, keeping newest
   posts first.
3. **Paste the same card into `index.html`'s Latest grid** (`id="latest"` section). This block
   is byte-identical between `updates/index.html` and `index.html` by design — write it once,
   paste it into both, and keep at most the 3 most recent posts in `index.html` (drop the oldest
   if you're already at 3).
4. **Add an `<item>` to `updates/feed.xml`.** Copy the existing `<item>` block and fill in
   `<title>`, `<link>`, `<guid isPermaLink="true">` (same URL as `<link>`), `<pubDate>` and
   `<description>`. Paste it directly below the
   `<!-- Newest first. Paste new items directly below this comment. -->` marker. **`<pubDate>`
   must be RFC-822** — the exact format the existing item uses is
   `Sun, 06 Sep 2026 09:00:00 +0000`: three-letter weekday, day, three-letter month, four-digit
   year, `HH:MM:SS`, then a timezone offset. Get the weekday right for the actual date (e.g.
   `date -d "2026-09-06" +%A` prints `Sunday`) — an RSS reader will not correct a wrong weekday
   for you, it will just look subtly broken.
5. **Add a `<loc>` to `sitemap.xml`.** One line, following the existing post entry's pattern:
   `<url><loc>https://sparkofchaos.com/updates/<YYYY-MM-DD>-<kebab-title>.html</loc><lastmod><YYYY-MM-DD></lastmod><priority>0.5</priority></url>`.
   `updates/_template.html` itself is never added to the sitemap.

### 5.4 Add a game

1. Copy an existing game page (`kabonk.html` or `fernweh.html`) to `<game>.html` and rewrite the
   `<head>` (title, description, canonical, OG tags — see the metadata audit in §6), the
   `<body class="...">` scope, and everything inside `<main>`.
2. Add a `.game-strip` to `index.html`'s games section, following the pattern in §2
   `.game-strip` — decide `game-strip--flip` or not based on whichever side balances the
   existing strips.
3. Add a palette scope class to `assets/css/site.css`, alongside `.kabonk` and `.fernweh`:
   `.<game> { --game-accent: …; --game-accent-2: …; --game-bg: …; }`.
4. Add a `<loc>` for `<game>.html` to `sitemap.xml`.
5. Add an OG image for the new game (or reuse an existing one) and point the new page's
   `og:image` at it.

### 5.5 Remove a game

1. Delete the game's page (`<game>.html`).
2. Delete its `.game-strip` section from `index.html`.
3. Delete its asset directory (`assets/img/<game>/`) and any `assets/video/<game>-*.webm` clips.
4. Delete its palette scope class from `assets/css/site.css`.
5. Delete its `<loc>` entry from `sitemap.xml`.
6. Grep the rest of the site for inbound links before finishing:
   `grep -rn "<game>.html" --include=*.html .` — fix or remove anything that still points at it
   (nav, other game pages, devlog posts).

### 5.6 Swap in Fernweh artwork

Fernweh has no real hero art yet — `.dusk` in `assets/css/site.css` is a CSS-only scene standing
in for it (campfire glow, treeline, dusk sky gradient, film-grain noise). The CSS comment above
`.dusk` documents the exact replacement:

```css
/* Stands in for artwork that does not exist yet (spec §11). To replace with a
   real image later, override exactly one declaration:
     .fernweh .dusk { background: url('/assets/img/fernweh/hero.jpg') center/cover; } */
```

Add the image to `assets/img/fernweh/`, then add that one override rule after `.dusk` in
`site.css`. Don't delete `.dusk`'s existing rules — the override rule wins by cascade order and
specificity, and keeping the original means reverting is a one-line removal.

### 5.7 Change a colour or a font

Token edits only, in `assets/css/site.css`'s `:root` block (or a `.kabonk`/`.fernweh` scope
override for a per-game change — see §1). Never edit a component rule to hardcode the new value;
every component already reads from a token, so changing the token cascades everywhere it's used.

## 6. Checks before committing

There is no test framework or CI check beyond the Pages deploy — these are the ad-hoc
verification commands used while building the site (Tasks 6, 7 and 10 of
`docs/superpowers/plans/2026-09-06-spark-of-chaos-site.md`), collected here so they can be
re-run after any content change. Start the preview server first:

```bash
node tools/serve.js & sleep 1
```

**Script syntax:**

```bash
node --check assets/js/site.js && echo 'site.js syntax ok'
```

**No external asset requests** — every `src` must be same-origin (this only checks `index.html`;
repeat for any page you changed):

```bash
grep -o 'src="https\?://[^"]*"' index.html && echo 'FAIL: external asset' || echo 'no external assets: ok'
```

**Kabonk! media wiring** — every video has `preload="none"` and a `poster`, the lightbox has the
expected number of grouped links, every `<img>` has `alt`/`width`/`height`. The counts below (5
videos, 4 lightbox links, 8 level cards) are specific to the current page — update them in your
own copy of this command if you add or remove a video, screenshot or level:

```bash
node -e "
const http=require('http');
http.get('http://localhost:8000/kabonk.html', r => {
  let b=''; r.on('data',c=>b+=c); r.on('end',()=>{
    if(!/<body class=\"kabonk\"/.test(b)) throw new Error('missing .kabonk palette scope');
    const vids=b.match(/<video[^>]*>/g)||[];
    if(vids.length!==5) throw new Error('expected 5 videos, found '+vids.length);
    for(const v of vids){
      if(!v.includes('preload=\"none\"')) throw new Error('video without preload=none: '+v.slice(0,60));
      if(!v.includes('poster=')) throw new Error('video without poster: '+v.slice(0,60));
    }
    const lb=(b.match(/data-lightbox=\"kabonk\"/g)||[]).length;
    if(lb!==4) throw new Error('expected 4 lightbox links, found '+lb);
    const cards=(b.match(/<article class=\"card\">/g)||[]).length;
    if(cards!==8) throw new Error('expected 8 level cards, found '+cards);
    for(const tag of b.match(/<img[^>]*>/g)||[])
      for(const a of ['alt=','width=','height='])
        if(!tag.includes(a)) throw new Error('img missing '+a+': '+tag.slice(0,70));
    console.log('kabonk.html ok');
  });
});"
```

**Every asset a page references actually exists on disk** (swap the filename to check a
different page). Note this only scans HTML attributes — `site.css` also references
`/assets/img/dyneria/hero.jpg` in a `url()`, and the `.dusk` comment block names a Fernweh
hero image that deliberately does not exist yet, so strip CSS comments before checking
stylesheet urls or you will chase a phantom:

```bash
node -e "
const fs=require('fs');
const html=fs.readFileSync('kabonk.html','utf8');
const refs=[...html.matchAll(/(?:src|href|poster)=\"(\/assets\/[^\"]+)\"/g)].map(m=>m[1]);
const missing=[...new Set(refs)].filter(p=>!fs.existsSync('.'+p));
if(missing.length) throw new Error('missing files:\n  '+missing.join('\n  '));
console.log('all', new Set(refs).size, 'asset references resolve');"
```

**Metadata audit across every page** — unique title, unique description, description
**≤ 160 characters** (this limit is real: it broke once during the build, when three pages
shared one description and Fernweh's link preview showed Kabonk!'s text), RSS autodiscovery,
stylesheet linked, `lang` set. Add any new page's path to the `pages` array:

```bash
node -e "
const fs=require('fs');
const pages=['index.html','kabonk.html','fernweh.html','dyneria.html','404.html',
             'updates/index.html','updates/2026-09-06-wander-a-day.html'];
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

**Sitemap matches reality** — every listed URL resolves to a file on disk, the template is
excluded, the home page is present:

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

Stop the server when done: `kill %1`.
