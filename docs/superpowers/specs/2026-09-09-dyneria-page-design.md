# Dyneria page — design

**Date:** 9 September 2026
**Scope:** add a game page for Dyneria to sparkofchaos.com, built from the Steam store page.

## Source

Steam app `5096670`. Everything on the page comes from the store listing, pulled via
`store.steampowered.com/api/appdetails?appids=5096670`:

- 8 screenshots at 1920×1080. **No trailer** — the listing has no video.
- **The 460×215 header capsule is the only branded wordmark art**, and Steam publishes
  nothing larger: `capsule_616x353.jpg`, `library_hero.jpg` and `logo.png` all 404. There is
  no transparent logo.
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
   rather than by editing a flat colour. It pushes the olive-green world screenshot back far
   enough for the capsule to read as the subject.
2. **Lift on the capsule.** `--radius`, a `--line` hairline and `--shadow-lg` on
   `.dyneria .hero__logo`, so it sits on the backdrop instead of blending into it.
3. **`padding-inline` on the hero.** `.hero` has none of its own — Kabonk's hero holds one
   button and Fernweh's holds none. Dyneria is the first to put a `.btn-row` in a hero, and
   without inline padding it overflows a 390px viewport.

Screenshots take the plain `--radius` with a `--line` hairline. `.shot` is deliberately not
used: that CRT treatment is scoped to `.kabonk` and belongs to Kabonk!.

The hero briefly used the shared `.hero__wordmark` gradient text instead of the capsule.
That needed its own scope overrides — `--spark-gradient` opens on `--spark-1`, a navy that
vanished against the map greens — and all of them came out again once the capsule landed.

**The home strip has no media column.** Kabonk's holds a logo and Fernweh's a text wordmark
over a flat background. Dyneria's tried a text wordmark, which sat unreadably on the world
art while only repeating the gold title beside it — so the art is the strip background and
the body takes the first column. The capsule could go in that slot now that it is committed;
it was left out deliberately, because the strip already carries the same art as its
background and the title is already gold.

## Images

`hero.jpg` is `screenshot-01` cropped to `(0, 130, 1920, 1010)`, which removes the in-game
HUD top bar, the belief meter and the bottom toolbar. The 8 gallery files keep their native
1920×1080 including the HUD — that is what the game looks like.

All 9 land under STYLE.md's 400 KB screenshot budget. The sources are already JPEG, so PNG
cannot help: re-encoding `screenshot-03` as a 256-colour PNG measured both larger *and*
further from the source than the JPEG. Checked at 1:1 that the flat pixel art survives the
compression, and it does.

`capsule.jpg` is the Steam capsule at 1200×561 — 2.6× Lanczos from the 460×215 original,
which is all Steam publishes. It serves as both the hero image and the `og:image`, so a link
preview shows the branded art rather than a screenshot, and Dyneria no longer borrows
Kabonk!'s OG image the way `fernweh.html` still does. One file, so it cannot drift from
itself; it is therefore the one OG image not under `assets/img/brand/`.

## Files

| File | Change |
|---|---|
| `dyneria.html` | new |
| `index.html` | leads the `.game-strip` run, unflipped; Kabonk!'s and Fernweh's flips unchanged |
| `assets/css/site.css` | `.dyneria` palette line, Dyneria world-art block |
| `sitemap.xml` | `<loc>` for `dyneria.html` |
| `assets/img/dyneria/` | `capsule.jpg`, `hero.jpg`, `screenshot-01`–`08.jpg` |
| `STYLE.md` | palette table, scope docs, metadata-audit page list |

## Known gaps

- **No trailer**, because Steam has none. When one exists, the page wants a `Launch trailer`
  section copied from `kabonk.html` — `preload="none"` and a poster, per rule 4.
- **The capsule is capped by its 460×215 source.** At the 544px it renders at, that is
  ample; if it ever needs to run wider — a full-bleed banner, say — it will go soft, and the
  fix is new art from Steamworks, not a bigger upscale.
- **Fernweh's `og:image` still points at `og-kabonk.png`**, so its link previews show the
  wrong game. Out of scope here, but now the only page with that problem.
