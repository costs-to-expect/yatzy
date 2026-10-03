[![Minimum PHP Version](https://img.shields.io/badge/php-^8.2-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/costs-to-expect/yatzy/blob/main/LICENSE)
[![Tests](https://github.com/costs-to-expect/yatzy/actions/workflows/tests.yml/badge.svg)](https://github.com/costs-to-expect/yatzy/actions/workflows/tests.yml)
[![Laravel Forge Site Deployment Status](https://img.shields.io/endpoint?url=https%3A%2F%2Fforge.laravel.com%2Fsite-badges%2F13270108-d2e9-459a-9491-8cd34a122001&style=flat-square)](https://forge.laravel.com/servers/581137/sites/2028561)

# Yatzy Game Scoring

## Overview

Game scoring for Yatzy, powered by the Costs to Expect API.

![Score sheet](/resources/art/score-sheet.png)

There are no local users, the app signs players in with the API and keeps their bearer token in a cookie. Everything
else, players, games and score sheets, is stored in the API, the app's own database only holds sessions, the queue,
the public score sheet links and registrations that are waiting for a password.

## Other Apps

[Yahtzee](https://github.com/costs-to-expect/yahtzee)

We plan to create Apps for each of the Board and dice games we play, the Apps will all be Open Source, you 
are free to create your own and then submit a PR to the Costs to Expect [API](https://github.com/costs-to-expect/api) 
to add the new game type.

## Set up

I'm going to assume you are using Docker, if not, you should be able to work out what you need to run for your 
development setup.

Go to the project root directory and run the below.

### Environment

* $ `docker network create costs.network` *
* $ `cp .env.example .env` and set the empty values, see **Configuration** below
* $ `docker compose build`
* $ `docker compose up -d`
* $ `docker exec yatzy.app composer install`
* $ `docker exec yatzy.app php artisan key:generate`

After generating the key, you need to restart your containers, so run down and up again to force the new key to be used.

* $ `docker exec yatzy.app php artisan migrate`
* $ `docker exec yatzy.app php artisan queue:work`

Run `php artisan migrate` again whenever you pull, the share links need it (see **Share links**).

Open the app at the address in `APP_URL` (`http://yatzy.game-scorer.local`, add `127.0.0.1 yatzy.game-scorer.local` to your
hosts file). `SESSION_DOMAIN` ties the session cookie to that address, so signing in from any other (`localhost`, the
Yahtzee scorer's address, which also uses port 80) fails with a 419 Page Expired, the browser throws the cookie away. Set
`SESSION_DOMAIN=null` if you want it to work on any address.

The queue worker sends the emails (create password, forgot password, account deletion), leave it running while you 
develop, nothing is sent without it.

*We include a network for local development purposes, I need to connect to a local version of the Costs to Expect
API, You probably don't need this so remove the network section from your docker compose file and don't create the
network.

Composer is part of the app image, run it with `docker exec yatzy.app composer ...` so dependencies are always
installed with the PHP the app runs on.

### Configuration

| Variable | Purpose |
|---|---|
| `API_URL` | The Costs to Expect API |
| `APP_DEV`, `API_URL_DEV` | Set `APP_DEV=true` to use `API_URL_DEV` instead, for a local copy of the API |
| `ITEM_TYPE_ID`, `ITEM_SUBTYPE_ID` | The API's game item type and its Yatzy subtype |
| `COSTS_TO_EXPECT_INTERNAL_API_KEY` | See below |
| `SESSION_NAME_USER`, `SESSION_NAME_BEARER` | Names of the cookies that hold the player's id and bearer token |
| `ERROR_EMAIL` | Where failed API calls, such as a score that could not be logged, are reported |
| `SCORE_CORRECTIONS` | `true` switches on undo, change and clear for a score, see below, it is `false` unless you set it |

**The internal API key.** The API only lets its own trusted apps register an account and request a password reset, 
both return a token which the app emails, so they are protected by an `X-Internal-Api-Key` header. Set 
`COSTS_TO_EXPECT_INTERNAL_API_KEY` to the same value as `INTERNAL_API_KEY` in the API's `.env`. Without it, 
registering and forgot password both fail with "This route can only be called by a trusted internal service", 
nothing else needs it.

## Testing

```bash
docker exec yatzy.app composer test
```

The tests use an in-memory SQLite database and fake every request to the API, they never touch the development 
database (the test case refuses to run against anything else) or a real API. `phpunit.xml` sets everything they 
use, including a throwaway application key, so they need no `.env`.

GitHub Actions runs them on PHP 8.2, 8.3, 8.4 and 8.5 for every push and pull request, see 
`.github/workflows/tests.yml`. 8.2 is the version the app runs on, the others are the versions it is moving to.

PHPUnit can't test the scripts in `public/js`, the score sheet script included. They have no automated tests yet, 
Yahtzee's [browser tests](https://github.com/costs-to-expect/yahtzee/tree/main/tests/e2e) (the real app against a mock of
the API, driven by Playwright) are the place to start.

## Frontend assets

CSS is compiled with the standalone Tailwind CLI, there is no Node or yarn involved.

```bash
bin/css          # one-off build
bin/css --watch  # rebuild on change
```

The first run downloads the right binary for your machine into `bin/`. The output goes to 
`public/css/{version}/app.css`, the version is the `css` value in `config/app/version.php`, bump it when 
the CSS changes so deployed apps don't serve stale files, bump `js` when `public/js` changes. Commit the compiled file,
the server does not build it.

Every page is built from the Blade layouts and components in `resources/views/components` (the layouts, the icon
sprite, the avatar and ring, the sheet, the fields and alerts), the classes the pages share (buttons, cards, form
controls) are in `resources/css/app.css`, and the scripts are plain JavaScript, no build step: `public/js/ui.js` is on
every page (sheets, the snackbar, confetti, copy a link), `public/js/score-sheet.js` draws the score sheet and `public/js/landing.js` is the score sheet to try and the walkthrough on the landing page. Only the
places listed in `resources/css/app.css` are scanned for classes, add a path there if classes are ever built
somewhere new. The reasoning behind the look, the colours and the typeface is in [design](design/README.md).

What the pages say about the game (its name, mark, words and number of turns) is in `config/app/game.php` and the marks
are in `app/View/Icons.php`, a sibling scorer (Scrabble, Carcassonne) copies the theme and the components and changes those.

## Score sheets

A score sheet is stored in the API as the whole sheet, the app reads it, adds or removes a combination, works out the
totals and writes it back (`App\Actions\Game\ChangeScore`, the rules are in `App\Support\ScoreRules`). The server decides
what is allowed, the browser is only asked nicely: the page is given the rules (`ScoreRules::lowerRules()`) so the score
sheet script offers exactly the scores the server accepts.

What a combination can score comes from the dice, five of them:

| | Scores | Can be |
|---|---|---|
| Ones to sixes | The dice of that number added up | 0 to 5 of them, 0 is a scratch |
| Upper bonus | 50 | A total of 63 or more in the upper section |
| One pair | The two dice | 2 to 12, even |
| Two pair | The four dice, two different pairs | 6 to 22, even |
| Three of a kind | The three dice | 3 to 18, in threes |
| Four of a kind | The four dice | 4 to 24, in fours |
| Full house | All five dice, three of one number and two of another | 7 to 28, but not 10 or 25 |
| Small straight | 15, 1-2-3-4-5 | |
| Large straight | 20, 2-3-4-5-6 | |
| Yatzy | 50, five of a kind | |
| Chance | All five dice | 5 to 30, it can't be scratched |

Every combination but Chance can be scratched for 0. A game is fifteen turns, six in the upper section and nine in the
lower. A test rolls every hand of five dice and checks the rules allow exactly the scores a hand can make.

**Undo, change and clear are off** (`SCORE_CORRECTIONS=false`). Removing a combination from a sheet only works when
the API replaces the sheet it is sent, rather than merging into the stored one. Everything else the app does only adds
a combination, so that has never been needed. Before switching it on, clear a score on a test game and reload the
page: if the score comes back the API merges and it has to stay off. When it is off a scored row is locked, as it always
was, and the server refuses to overwrite or clear a score.

## Share links

Every player in a game has a public link that lets anyone who has it score for that player, it lives until the game is
completed or deleted. The link is a token that the app maps back to the game, the player and the **owner's bearer
token**, so the parameters are encrypted with `APP_KEY` in the `share_token` table (`App\Casts\EncryptedParameters`). Someone
who can read the table but not the key learns nothing. Changing `APP_KEY` makes the links of games in progress
unreadable, so change it between games. A link stops working when the owner signs out (the API revokes the token).

The `share_token.parameters` column changes from `json` to `text` in a migration (`encrypt_share_token_parameters`). Run
it as part of the deploy, before anyone starts a game: links already stored are still read without it, but MySQL refuses
to store a new, encrypted, link in the `json` column, so no game can be created until it has run.

## PHP and Laravel versions

The app runs on Laravel 12 and PHP 8.2, `composer.json` pins the platform to 8.2 so a `composer update` on a newer 
PHP on your machine can't pick packages the production server can't run. When the app moves to PHP 8.4 or later,
change the Dockerfile image, `require.php` and `config.platform.php` together, Laravel 13 needs PHP 8.3 or later, 
the tests pass on PHP 8.2 and 8.5.
