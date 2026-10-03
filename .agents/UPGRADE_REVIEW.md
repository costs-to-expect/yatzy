# Yatzy: upgrade and auth review

**Date:** 2026-10-03  
**Status:** Laravel 12 on PHP 8.2, forgot password added, the design built into every page (Tailwind, a teal theme, Figtree,
Bootstrap and Node removed), share links encrypted, the token revoked after account deletion, the Sanctum leftovers removed,
scores checked on the server against what five dice can make. 487 PHPUnit tests with GitHub Actions CI. No browser tests,
see the follow-ups at the bottom.

This is the Yahtzee upgrade (`costs-to-expect/yahtzee`, branch `upgrade`, its own review is in its `.agents` folder) applied
to Yatzy. Yatzy began as a copy of Yahtzee, so it had the same code and, with two exceptions noted below, the same bugs.
What differs is the game, see "Where Yatzy differs from Yahtzee".

## What this app is

A Laravel front end for the Costs to Expect API. There are no local users: signing in posts to the API, the bearer
token and user id go in two cookies, and the "user" is whoever the API says owns the token. Players, games and score
sheets live in the API as resource types, resources, categories (players) and items (games, with the score sheet
stored as item data). The app's own database holds sessions, cache, the queue, `share_token` (public score sheet
links) and `partial_registration`.

## What was checked, and how

| | |
|---|---|
| PHPUnit, 487 tests | Pass on PHP 8.2.34 in the app container (`docker exec yatzy.app composer test`) and on PHP 8.5.10. CI also runs 8.3 and 8.4, not run here |
| Every combination's allowed scores | A test rolls all 7,776 hands of five dice and checks `ScoreRules` allows exactly the scores a hand can make |
| The share link migration | Run against a throwaway MySQL 8.0.46 container: up encrypts a plain JSON row and changes the column to `text`, the app reads it back, down decrypts it and restores `json`. Yahtzee's review could only run it on SQLite |
| The pages and scripts | The real app run against a stand-in for the API and driven in a browser: every Yatzy combination on the number pad (what is accepted and refused), the fixed rows and scratching, the upper bonus, finishing a game, a public link, undo, change and clear (`SCORE_CORRECTIONS=true`), a failed save and its retry, phone and laptop widths |

**Not checked.** None of it has run against the real API, only a stand-in (a PHP port of Yahtzee's Node mock, kept out of the
repository), so the API's behaviour on a write is assumed from how the app has always used it. The browser pass was by hand and
cannot be repeated, nothing in the repository tests the scripts (see the follow-ups). Only WebKit and a Chromium were used.
Emails were not sent, the tests fake them. Yahtzee's browser tests also run [axe-core](https://github.com/dequelabs/axe-core) on
every page and dialog, that was not run here: the templates are Yahtzee's, the Yatzy rows, the number pad's messages and the
share links dialog are new.

## Auth review

The code Yatzy had matches Yahtzee's before the upgrade, so Yahtzee's review of it against Budget Pro applies as it stands.
The API only gates two routes with `X-Internal-Api-Key` (`VerifyInternalApiKey` middleware, it fails closed when
`INTERNAL_API_KEY` is empty): `auth/register` and `auth/forgot-password`.

| | Yatzy before | Yatzy now |
|---|---|---|
| Internal key on register | yes (`Http::post(internal: true)`) | yes, tested |
| Internal key on forgot password | no flow existed | flow added, sends the key, tested |
| Key sent on nothing else | yes | proved by tests, no other request carries it |
| Sign-out revokes the API token | no, only forgot the cookies | yes, and the account deletion jobs revoke it once the API has been asked to delete |
| Guard caches the resolved user | no, every `Auth::user()` called the API | yes |
| User provider authenticates with the player's token | **no, it sent the cookie's name as the token** | yes |
| `rehashPasswordIfRequired` on the provider | missing, a fatal error on Laravel 11+ (the first thing the upgrade hit) | yes |
| Queued jobs that carry the token are encrypted | no | yes (`ShouldBeEncrypted`) |
| Stay signed-in checkbox works | **never submitted** | yes |
| The API's validation errors when signing in | **lost, the guard kept the word "fields"** | yes, shown on the form |

One deliberate difference remains:

- **Account deletion revokes the token from the queued job, not from the redirect.** Yatzy queues the delete job with a
  five second delay and the job uses the player's token, so the redirect that signs the player out only forgets the
  cookies (`Guard::logout(false)`) and the job revokes the token (`App\Jobs\Concerns\RevokesBearerToken`) once the API has been
  asked to delete, whether or not that request worked. If no queue worker is running the token stays valid until it expires.
- **The sign-in request carries no bearer**, a stale token from an earlier sign-in used to be sent to the public route.

The local `.env` does not have `COSTS_TO_EXPECT_INTERNAL_API_KEY` set, register and forgot password will be refused by the
local API until it is.

## Bugs found by the tests, fixed, each has a test

1. The winner of a completed game was wrong for some scores (a sort that never answered "before"), `[92, 181, 197]` crowned 181.
2. The stay signed-in checkbox had no `name`, so it was never submitted.
3. The create password page threw on the API's validation errors, and sent the email and token the wrong way round.
4. A 401 from the API when signing in was a server error (the error had a different shape).
5. Typed passwords were flashed into the session, and written back into the create password form, on a failed attempt.
6. Players typed into the "Let's get started" textarea kept stray `\r` characters on Windows, and a blank line became an empty name.
7. Completing or deleting a game that does not exist was a 500, the 404 was caught and re-thrown as a 500.
8. The public score sheet crashed (`JsonResponse` used as an array) when the API could not return the score sheet.
9. A failed account deletion carried on, removed the player's sessions and emailed them to say it was done.
10. The games page was a server error when the API failed, and a failure creating the Yatzy resource right after
    creating its resource type crashed instead of reporting the API's status.
11. Yatzy only: the guard answered a 422 from the API with the word "fields", so sign-in never showed why it failed.
12. Yatzy only: the score sheet took any number, a three of a kind of 30 or a full house of 5, from a signed-in player and from
    anyone holding a public link. The number inputs had limits (`max="22"` for a full house that can make 28) but nothing enforced them.

## Where Yatzy differs from Yahtzee

| | Yahtzee | Yatzy |
|---|---|---|
| Turns | 13 | 15, six upper and nine lower |
| Upper bonus | 35 from 63 | 50 from 63 |
| Lower section | Three and four of a kind, full house, two straights, Yahtzee, Chance | One pair, two pair, three and four of a kind, full house, two straights, Yatzy, Chance |
| Fixed scores | Full house 25, small straight 30, large straight 40, Yahtzee 50 | Small straight 15, **large straight 20**, Yatzy 50 |
| Totals | Three and four of a kind and Chance add up all five dice | Each combination adds up its own dice, full house and Chance all five |
| Extra Yahtzees | Three bonus buttons, +100 each | None |
| The mark | A die showing five | A die showing six |

- **The large straight is now worth 20** (2-3-4-5-6), as in the standard rules, it was 30 from the first release. A game that
  already scored its large straight keeps the 30 it stored. This was a decision, asked for and confirmed.
- **The server refuses a total no dice can make**: a full house of 10 or 25, a three of a kind that is not three of a number.
  If a group has always played three of a kind as all five dice (the page has always said "total of the three matching
  dice") the server now refuses it.
- The page is given the rules by the server (`ScoreRules::lowerRules()`) and the number pad uses them, Yahtzee's script keeps
  its own copy, which was fine for three totals between 5 and 30 but would drift for a full house.
- Yahtzee's home page fetches the signed-in user on every load and never uses it. Yatzy never did, so it does not now.
- The auth and utility pages stay `noindex,follow`, as Yatzy had them (Yahtzee's are `noindex, nofollow`), and the
  landing page keeps its own title and descriptions.
- The delete player route and `start` live in the action controller, as Yatzy always had them.

## Not changed, and worth knowing

1. **MySQL is still 8.0**, which reached end of life in April 2026, to be dealt with in a server move. A Docker volume
   that has been run by 8.0 is upgraded in place by 8.4 on first start and cannot go back, take a `mysqldump` first.
   The local database only holds sessions, cache, jobs and share links.
2. **Before deploying the share link migration**, run `php artisan migrate --pretend` and take a `mysqldump` of `share_token`
   (it only holds links for games in progress). Changing `APP_KEY` later makes the links of games in progress unreadable.
   **Run the migration as part of the deploy, before anyone starts a game.** Links already stored are read without it, but
   while the column is still `json` MySQL refuses a new, encrypted, link (error 3140) and no game can be created. This was
   found, and checked on MySQL 8.0, when a local copy was running the new code with the migration still pending.
3. **`docker-compose.yml` hands `DB_ROOT_PASSWORD` to MySQL and `.env.example` does not define it**, so a fresh
   `cp .env.example .env` leaves the MySQL container unable to start ("password option is not specified") until you add it.
4. **Undo, change and clear a score are built and switched off** (`SCORE_CORRECTIONS=false`). Removing a combination only
   works if the API replaces the score sheet it is sent rather than merging into it, nothing the app did before needed
   that. Check on a test game (clear a score, reload) before setting it to `true`.
5. **A share link stops working when the owner signs out**, the API revokes the token the link holds. Sign-out revoking
   the token is wanted, but a game that is still being played through links ends for everyone who has one. Links are only
   as long lived as the owner's session.
6. **The "started 40 min ago" and "last played on" labels need a created time from the API**, the app reads `created_at`
   (or `created`) from a game and leaves the label out when there is none, nothing else changes. "Play again" and the
   preselected players come from the last finished game, **the app assumes the API returns the newest finished game first**
   (the old home page's Recent Games relied on the same order), if that is not so the wrong game's players are offered.
7. **Forgot password shows whether an email has an account** (the API's 404 is put on the form). Register has always
   allowed this, show the confirmation page for a 404 if you would rather not.
8. **Production needs a queue worker** (`QUEUE_CONNECTION=database`). The forgot password email is queued like the
   register one, the account deletion jobs revoke the token, check the Forge daemon is running.
9. **The scripts have no automated tests.** Yahtzee's Playwright suite (`tests/e2e`) was deliberately not ported, so the score
   sheet script, the landing page demo and `ui.js` are only covered by the by-hand browser pass above. The mock API and the
   suite in the Yahtzee repository are the place to start, a Yatzy sheet's rows and rules are in `score-sheet.js` and
   `ScoreRules`.
10. **The bonus messages were removed** (the endpoints, `bonus.blade.php` and their test), the bonus tracker above the upper
    section replaced them. They are in the history if you want the old messages back.
11. Small things left alone: `Controller::bootstrap()` creates another resource type whenever the API returns more than one,
    and the player scores for the Everyone panel are read every ten seconds by every open sheet, that is one request for the
    game's players and one for its score sheets each time.
12. **The design is built for Yahtzee and Yatzy only.** The Scrabble and Carcassonne scorers copy `resources/css/app.css`,
    `public/fonts`, the Blade components, `app/View/Icons.php` and `public/js/ui.js`, and change `config/app/game.php` and
    their own sheet, see `design/README.md`. Not designed: dark mode and the Scrabble and Carcassonne sheets themselves.

## Moving to PHP 8.4 or later

Change the Dockerfile image, `require.php` and `config.platform.php` together, then `composer update`. Laravel 13
resolves with this app's dependencies (the same ones as Yahtzee, not run here) and needs PHP 8.3 or later. The suite already
passes on PHP 8.5, `config/database.php` has the PHP 8.5 safe `Mysql::ATTR_SSL_CA` constant from the Laravel skeleton.
