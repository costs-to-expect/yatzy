# Design: the look, the home page and the score sheet

The look and the two screens for the Costs to Expect game scorers: Yahtzee first, Yatzy (this app) next, then Scrabble
and Carcassonne. The design was made in the Yahtzee scorer and ported to this one, see **What Yatzy changes** at the end.
The design is built into the app, every page uses it. This is the reasoning behind it, for whoever changes it and
for the Scrabble and Carcassonne scorers. The pages are the Blade layouts and components in
`resources/views/components`, the classes they share are in `resources/css/app.css` and the scripts are in `public/js`
(`ui.js` on every page, `score-sheet.js`), `README.md` says how they fit together. The mockups it was designed with are
in the history of the Yahtzee repository, before the commit that built them into the app.

Where the app differs from the design: undo, change and clear are behind `SCORE_CORRECTIONS` (off until the API is
confirmed to replace the score sheet), "Play again" uses the last finished game, the time labels appear when the API says
when a game started, the player scores for the Everyone panel are JSON, and the bonus tracker replaced the bonus messages.

## What was decided

- **The home page is the Launchpad**: one question, "Who's scoring?", a tile for every player, the next game two taps
  away. The other two options (game cards, a league table) were dropped. A league table can still become a section
  of the page later if there is somewhere to read the statistics from.
- **The score sheet**: tap how many you rolled, a number pad, the totals always in view, a bonus tracker, everyone's scores beside it.
- **The look is polished and friendly, not "gamer"**: warm paper, white cards, soft shadows, one deep teal, a friendly
  typeface, no neon, no dark theme, no sound effects. It has to work for Yatzy, Scrabble and Carcassonne, so nothing in
  it is about dice.
- **The colour moved from Costs to Expect purple to teal**, and the purple is kept as an acknowledgement, see below.

## The look

### Colour

| Colour | Where it is used | Rule |
|---|---|---|
| **Teal** `brand-*`, `700` is `#057176` | Buttons, links, rings, bars, the leader's tile, the logo tile | The same for every scorer. A game is told apart by its mark and name, never its colour, so a new game never needs a new palette |
| **Costs to Expect purple** `cte-*`, `#8A1786` | The "A Costs to Expect app" footer lockup (the real logo) and the account pages | Nowhere else. The app is a Costs to Expect app, the purple is how it says so |
| **Gold** (amber) | A crown for who is leading now, a trophy for who won a finished game | Never a warning |
| **Green, amber, red** | Saved, not saved, delete | Only ever status, always with an icon and words, teal is never one of them |
| **Players** | Six soft avatar colours (rose, sky, lime, violet, orange, slate) | A player gets theirs from their place in the players list, so they keep it in every game and nothing is stored |
| **Paper** `#F7F5F0`, stone | The page, text and borders | Warm greys, not the cold default |

How the teal is used, because it is easy to get wrong: **700** is the working colour for buttons, links and text on
white or paper (5.8:1). **800** is the hover for 700 (darker, never lighter) and the text on the pale tints. **50 and 100**
are tints for soft buttons and selected rows. **600 and lighter** are for rings, bars and fills only, white text on
600 is 4:1.

Why teal: it is not purple, it is not a status colour (it sits 37 degrees from the success green and 42 from a link
blue, so it is never mistaken for either) and it is friendly rather than corporate. Costs to Expect purple is at
330 degrees on the colour wheel, gold is at 85 and the teal is at 200, nearly evenly spaced, so the three belong
together and the footer lockup does not look pasted on.

### Type

**Figtree**, self-hosted in `public/fonts` (SIL Open Font License, latin and latin-ext so a name such as "Łukasz" does
not fall back to another font mid-word, 30 KB in total, only the files a page needs are downloaded). It is friendly without
being childish and it has real **tabular figures**, so a column of scores lines up and a total never jiggles as it changes,
which two of the other candidates (DM Sans and Fraunces) could not do. Scores are 800 weight, headings 800, names and
buttons 700, body 400 and 600.

None of the sibling apps loads a web font (Cashflow and Expense name Inter in their theme without loading it, Budget Pro
declares no font), they all render in the system font, so this is the first to actually ship a typeface.

### Accessibility, measured

- Every text and background pair used was checked from the compiled theme, all 35 pass WCAG AA (4.5:1). The lightest text
  is `stone-500` on white at 4.79:1, used only for the small "Game Scorer" label.
- Touch targets on a phone: rows are 64px, number pad keys 56px, every other primary control 44px or more. Only the
  Tonight and Yesterday pills (40px, in a 48px control) and the footer links (32 to 40px) are smaller.
- The sheet is a native `<dialog>`: focus is trapped, Escape closes it, the page behind is inert. Focus returns to the row
  that was tapped, and survives the list being redrawn.
- Totals are `aria-live`, toggles are `aria-pressed`, a chosen player chip shows a tick and not only a colour, a failed
  save has an icon, words and a banner.
- Animations use `motion-safe`, transitions use `motion-reduce:transition-none` and the confetti does not run for
  anyone who asks for reduced motion.

## The home page: the Launchpad

On a phone a player is a one tap row with a chevron, from `sm` up it is a tile with a button, in two
columns for two or four players and three otherwise. The bottom tab bar (Home, Games, Players, Account) is where a thumb
reaches, it replaces the off-canvas menu.

**Game night** (a game is running)
- Tiles are sorted by score, the leader has a crown and a tinted tile, a ring around the avatar shows turns played.
- Share links opens a sheet with a Copy link button per player (today it is "copy the URL, or long press and share").
- Finish game shows the standings and warns if someone has not scored every turn. Delete game is behind the options menu and
  asks first, it is no longer the first button on every game.
- Next game is a chip picker with the last game's players already chosen, so the next game is one tap.
- Several open games switch with the Tonight and Yesterday pills.

**No game running**: "Play again with Ada, Ben & Cleo" is the whole page. **First visit**: one box for the names, the
textarea the app has today.

The same page serves every game, with the game's mark, name, words and tile meta (`config/app/game.php`). Yatzy has a ring and "8 of 15 turns", Scrabble and Carcassonne have no ring (they have no fixed
number of turns) and show the last move.

What it needs from the app:

- Turns per player and the scores sorted: `Index::home` already loads every open game's score sheets.
- The players of the last completed game, for "Play again" and the pre-chosen chips.
- "Started 40 min ago" needs a created time from the API, drop the label if the item has none.
- A small per-game config: name, mark, the tile button's words, the minimum players, and whether the game has a fixed
  number of turns (the ring).

## The score sheet

What it fixes, from how the sheet works today (`score-sheet.blade.php`, `public/js/score-sheet.js`):

| Today | In the design |
|---|---|
| Errors only go to `console.log`, a failed save looks like a saved one and the score is lost | The screen updates at once, a pill shows Saving, Saved or Not saved, a failed save gets a banner under the totals and a Retry on the row. Nothing is lost |
| A score locks for good, no undo, no correcting a slip | Undo in a snackbar for six seconds, and tapping a scored row offers Change score or Clear score |
| Upper scores are typed as totals (`9` for threes), easy to get wrong | Tap how many you rolled, 0 to 5, the app works out the points, and 0 is the scratch |
| Two controls per row, scratch checkboxes are tiny | One control per row, the whole row, 64px high, with a hint under it |
| Totals sit mid-page and at the bottom, you cannot see what a score did | Total, upper, bonus and lower are always in view, the total pops when it changes, and a thin line of 15 segments shows the turns |
| The bonus message arrives in the middle of the form | A bonus tracker sits above the upper rows, "Three sixes would get you the bonus", and turns green when it lands |
| A number field and the phone keyboard covering half the screen | A number pad inside the sheet, or just type on a laptop |
| A table of every player below the form, refreshed by redrawing it | An Everyone panel (a sidebar on a laptop), live, sorted, with the leader's crown |
| Nothing at 15 of 15 | A completion card with the owner's Complete the game button, no hunting for it on the home page |
| `prefers-reduced-motion`, focus handling and `aria-live` not considered | All three, see above |

What it needs from the API and the app:

1. **Validate scores on the server.** `scoreUpper` and `scoreLower` store whatever `dice`, `combo` and `score` they are
   sent. Accept only the fifteen combinations and the scores a combination can produce. Undo,
   change and clear are only safe once this is in.
2. **A way to remove a score from a sheet.** Today the API calls only ever add a key. Undo, Change and Clear need that.
3. The ten second poll for Everyone should update the panel, not redraw the whole sheet. Real saves would use `fetch`, so
   `axios` is no longer needed.

## How the next games plug in

The home page and score sheet are built from the same pieces for every game. Yahtzee and Yatzy are built, the other two
columns are a sketch of where the same pieces would go.

| Piece | Yatzy | Scrabble | Carcassonne |
|---|---|---|---|
| Player tile | Ring for turns, "8 of 15 turns" | No ring, "Last: QUIZ +52" | No ring, "Last: city +12" |
| Tile button | Open score sheet | Add a turn | Add points |
| Players | Any number | 2 to 4 | 2 to 5 |
| The sheet | Thirteen combinations to fill | A running list of turns | A running list of scoring events, by feature |
| Entry sheet | Count chips, Score or Scratch, number pad | Number pad, optional word, +50 bingo toggle | Quick chips for the usual points, number pad |

The same for every game: the total bar, undo, the saved state, the Everyone panel, sharing, finishing, the empty states,
the footer. The number pad is the piece to build once and reuse, it is the entry for a Yatzy sum, a Scrabble turn and
a Carcassonne score.

## Reusing it

- `resources/css/app.css` only scans the places it lists (`source(none)`), add a path there if classes are ever built
  somewhere new. Bump `css` in `config/app/version.php` when the CSS changes and `js` when `public/js` does.
- Scrabble and Carcassonne are separate apps, copy `resources/css/app.css`, `public/fonts`, the Blade components,
  `app/View/Icons.php` and `public/js/ui.js` and they have the look. To give one game its own accent, override
  `--color-brand-*` in that app, nothing else changes. Change `config/app/game.php` and write the sheet.
- The number pad (`score-sheet.js`) is the piece to lift out and reuse.

## What Yatzy changes

Yatzy is the second game on the design. The look, the pages, the components and the scripts are the Yahtzee scorer's, what
is different is the game:

| | Yahtzee | Yatzy |
|---|---|---|
| Mark | A die showing five | A die showing six, `yatzy` in `app/View/Icons.php` |
| Turns | 13 | 15, six in the upper section and nine in the lower |
| Upper bonus | 35 points from 63 | 50 points from 63 |
| Lower section | Three and four of a kind, full house, two straights, Yahtzee, Chance | One pair, two pair, three and four of a kind, full house, two straights, Yatzy, Chance |
| Always the same | Full house 25, small straight 30, large straight 40, Yahtzee 50 | Small straight 15, large straight 20, Yatzy 50 |
| Totals | Three and four of a kind and Chance add up all five dice, 5 to 30 | Each combination adds up its own dice: one pair 2 to 12, two pair 6 to 22, three of a kind 3 to 18, four of a kind 4 to 24, a full house 7 to 28, Chance 5 to 30 |
| Extra Yahtzees | Three +100 buttons under the lower section | Nothing, there is no bonus for a second Yatzy |

A Yatzy total can only be some of the numbers (a full house is never 10 or 25), so the number pad says which totals can be
typed and refuses the ones no dice can make. The page is given those totals by the server (`ScoreRules::lowerRules()`), the
script keeps the words of each combination (its name, hint and badge) and the server keeps the numbers, so the two cannot
disagree. The upper section is the same as Yahtzee's: tap how many you rolled.

## Not designed yet

Sign in, register and the account pages, the games list and players pages, dark mode, and the Scrabble and Carcassonne
score sheets themselves, only their place in the home page.
