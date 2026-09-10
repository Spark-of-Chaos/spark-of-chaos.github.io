# Dyneria page — design

**Date:** 9 September 2026
**Scope:** add a game page for Dyneria to sparkofchaos.com, built from the Steam store page.

## Source

Steam app `5096670`. Everything on the page comes from the store listing, pulled via
`store.steampowered.com/api/appdetails?appids=5096670`:

- 8 screenshots at 1920×1080. **No trailer** — the listing has no video.
- **Steam's own art is small** — the 460×215 header capsule is all it publishes;
  `capsule_616x353.jpg`, `library_hero.jpg` and `logo.png` all 404. Superseded on
  10 September 2026, when the studio supplied proper masters: a 3840×1240 island plate with
  no lettering, a 1280×720 transparent logo, and 1916×821 pre-composed key art.
- Developer and publisher: Spark of Chaos. Windows only. Single-player.
- **Release date: "Coming soon", no price.** The game is announced and wishlistable, not
  released.

## Decisions

**Framing: coming soon, not out now.** The chip reads `Coming soon` and the primary button
reads `Wishlist on Steam`, matching what a visitor sees the moment they click through. When
the store page flips to released, three strings change: the chip and the two button labels
on `dyneria.html`, plus the chip and button label on the `index.html` strip.

**Shape: image-led, like Kabonk rather than Fernweh.** Dyneria has 8 real screenshots, so
each Steam section becomes a `.feature` row paired with the shot that actually demonstrates
it, and all 8 collect in a lightbox gallery at the foot of the page:

| Section | Screenshot |
|---|---|
| The world runs itself | `screenshot-05` — climate readout over a parched map |
| Eight races | `screenshot-04` — village clusters, each race building its own way |
| Everything is about supply | `screenshot-06` — the trade-route web, `screenshot-08` — armies at a border |
| You are their God | `screenshot-03` — the powers panel |
| Play the game | `screenshot-02` — a volcano erupting |

The five power domains are `.card`s in a `.grid--3` under the powers row rather than five
bullets crammed beside the screenshot — they are the "five domains, 50+ spells" claim, and
they earn the space.

**Palette (`.dyneria`):** `--game-accent: #ffc334` sampled from Dyneria's own Steam
wordmark, `--game-accent-2: #c2d24b` from its world art, `--game-bg: #0d1226` from the
store capsule background. Distinct from Kabonk's magenta and Fernweh's peach.

**Copy is the Steam text, verbatim.** Per STYLE.md §4 the studio's own copy is not rewritten
for house style, so "Getting refused is remembered", "the usual answer is no" and "Or maybe,
they have been deceived…" all stand. Genuine typos fixed, sentences left alone:
`superseeds→supersedes`, `believe→belief`, `virusses→viruses`, `powerfull→powerful`,
`trough→through`, `foe's→foes`, `An swamp→A swamp`, `mirekin→Mirekin`.

**No devlog post.** The page, the home strip and the sitemap only. The announcement post is
better written in the studio's own voice later.

## Consequences for shared CSS

Three things the existing scopes did not need, all confined to `.dyneria`:

1. **A hero scrim.** `.dyneria .hero::before`, mixed from `--shade` so it tunes by ratio
   rather than by editing a flat colour. It sits between the plate and the logo, tuned light
   on purpose: enough to carry the tagline and the ghost button, not enough to mute the art.
2. **A `drop-shadow()` on the logo.** `.dyneria .hero__logo` is a transparent PNG, so a
   border, radius or `box-shadow` would draw a box around empty space; the filter follows
   the glyph edges instead. The rectangular screenshots keep the border and radius.
3. **`padding-inline` on the hero.** `.hero` has none of its own — Kabonk's hero holds one
   button and Fernweh's holds none. Dyneria is the first to put a `.btn-row` in a hero, and
   without inline padding it overflows a 390px viewport.

Screenshots take the plain `--radius` with a `--line` hairline. `.shot` is deliberately not
used: that CRT treatment is scoped to `.kabonk` and belongs to Kabonk!.

The hero went through two earlier shapes, and both left the scope cleaner on the way out.
First the shared `.hero__wordmark` gradient text, which needed overrides because
`--spark-gradient` opens on `--spark-1`, a navy that vanished against the map greens. Then
the Steam capsule as a flat rectangle, which needed a border, radius and panel shadow. The
real logo needs none of it.

**The home strip has no media column.** Kabonk's holds a logo and Fernweh's a text wordmark
over a flat background. Dyneria's tried a text wordmark, which sat unreadably on the world
art while only repeating the gold title beside it — so the art is the strip background and
the body takes the first column. Now that `logo.png` exists it would drop straight into that
slot, which would make all three strips consistent; left out here only because it was not
asked for.

## Images

**The hero is two layers, not one flat image.** `hero.jpg` is the island plate with no
lettering, resized from the 3840×1240 master to 1920×620. `logo.png` is the transparent logo
cropped to its true alpha bounds — 1046×324, from a 1280×720 canvas that was 85% empty.
Layering them is what keeps the logo in the sky above the volcano at every viewport, because
`cover` crops the plate's sides and never its middle. A pre-composed image would crop its own
lettering away on a phone.

`logo.png` is a **quantised 8-bit PNG with Floyd–Steinberg dithering**: 59 KiB against
317 KiB for full RGBA, and indistinguishable at the 544px it renders at, verified by
compositing both over the brightest part of the plate — max channel difference 23, mean 0.89.
Plain quantising without the dither bands visibly in the gold, so any re-export must dither.

The 8 gallery files keep their native 1920×1080 including the HUD — that is what the game
looks like.

All 9 land under STYLE.md's 400 KB screenshot budget. The sources are already JPEG, so PNG
cannot help: re-encoding `screenshot-03` as a 256-colour PNG measured both larger *and*
further from the source than the JPEG. Checked at 1:1 that the flat pixel art survives the
compression, and it does.

`key-art.jpg` (1200×514) is the pre-composed art and exists only to be the `og:image`: a
transparent PNG makes a poor link preview, and the bare plate carries no branding. It lives
with the other Dyneria art rather than in `assets/img/brand/`, so all three files stay
together. Dyneria no longer borrows Kabonk!'s OG image the way `fernweh.html` still does.

The three supplied masters total 14 MB and are not referenced by any page. Because the
repository root *is* the deployed site, leaving them in would publish 14 MB of dead weight,
so they came out after the web assets were derived. Their blobs stay in git history at
`60b5989` either way — removing them from `HEAD` changes what Pages serves, not what the
clone costs.

## Files

| File | Change |
|---|---|
| `dyneria.html` | new |
| `index.html` | leads the `.game-strip` run, unflipped; Kabonk!'s and Fernweh's flips unchanged |
| `assets/css/site.css` | `.dyneria` palette line, Dyneria world-art block |
| `sitemap.xml` | `<loc>` for `dyneria.html` |
| `assets/img/dyneria/` | `hero.jpg`, `logo.png`, `key-art.jpg`, `screenshot-01`–`08.jpg` |
| `STYLE.md` | palette table, scope docs, metadata-audit page list |

## Known gaps

- **No trailer**, because Steam has none. When one exists, the page wants a `Launch trailer`
  section copied from `kabonk.html` — `preload="none"` and a poster, per rule 4.
- **The Steam store page still shows the old 460×215 capsule.** The site now has better art
  than the storefront it links to; worth uploading the masters to Steamworks.
- **Fernweh's `og:image` still points at `og-kabonk.png`**, so its link previews show the
  wrong game. Out of scope here, but now the only page with that problem.
