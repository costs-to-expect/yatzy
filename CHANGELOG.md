# Changelog

The complete changelog for the Costs to Expect REST API, our changelog follows the format defined at https://keepachangelog.com/en/1.0.0/

## [Unreleased]
### Added
- A new landing page. A score sheet to try right in the hero (the real rows, the real bonus tracker and tips, nothing is
  sent anywhere), then "How a game night goes", four steps with one phone that changes as they scroll past (a picture
  under each step on a small screen), and a closing call to register. `public/js/landing.js`.
- The new design, built into every page. A Launchpad home page ("Who's scoring?", a tile for every player, the next game
  two taps away), a new score sheet (tap how many you rolled, a number pad, the totals always in view, a bonus tracker,
  an Everyone panel that updates by itself), and a teal look with the Figtree typeface for every other page. Costs to
  Expect purple is kept for the footer and the account pages. It is built from shared Blade components, the colours,
  icons and per game words are in one place so the Scrabble and Carcassonne scorers can reuse them, see `design/README.md`.
- A score is shown on the screen straight away and saved in the background, one at a time and in the order they were
  tapped. A pill says Saving, Saved or Not saved, a failed save keeps its score on the screen with a Retry, nothing
  is lost and leaving the page with an unsaved score asks first.
- The server checks every score against what five dice can make: one pair is 2 to 12 (even), two pair 6 to 22 (even),
  three of a kind 3 to 18, four of a kind 4 to 24, a full house is three of one number and two of another (7 to 28, never
  10 or 25), Chance 5 to 30. The combination has to exist, and a combination that is already scored is not overwritten
  (a retry of the same score is accepted). The number pad checks the same totals, the page is given the rules by the
  server and says which totals can be typed.
- Undo, change and clear a score. They are switched off, set `SCORE_CORRECTIONS=true` once the API is confirmed to
  replace the score sheet it is sent, see the README.
- Play again with the players of the last game, started in one tap from the home page, and the players of the last game
  are chosen for the next one. Several open games are switched between on the home page.
- Forgot password, a signed-out player can ask for a link to create a new password. The request to the API carries the
  `COSTS_TO_EXPECT_INTERNAL_API_KEY` header, as the API requires for register and forgot password.
- Signing out revokes the bearer token in the API, as well as forgetting the cookies.
- Tests, they use an in-memory SQLite database and fake every request to the API, `composer test`.
- GitHub Actions runs the tests on PHP 8.2, 8.3, 8.4 and 8.5 for every push and pull request.
- Tailwind CSS v4 through the standalone CLI (`bin/css`), with a teal theme and the Figtree typeface, self-hosted in
  `public/fonts`.
### Changed
- The large straight is worth 20 (2-3-4-5-6), as in the standard rules, it was 30. Games already played keep the 30 they
  stored.
- Updated to Laravel 12 and PHPUnit 11, the app still runs on PHP 8.2.
- Share links store the owner's bearer token encrypted with the application key, the migration encrypts the existing
  links and changes the column from `json` to `text`. Changing `APP_KEY` makes the links of games in progress unreadable,
  they only live until the game is completed.
- The queued account deletion jobs are encrypted, they carry the player's bearer token, and they revoke the token in
  the API once the deletion has been requested, it stayed valid until it expired.
- Removing a player from a game is a POST, it deleted a score sheet from a link.
- Everyone's scores (`/game/{id}/player-scores`, `/public/game/{token}/player-scores`) are JSON, the score sheet draws them.
- The score responses carry the sheet as it is now, so the browser can catch up.
- The compiled CSS lives in `public/css/{version}/app.css` and the scripts in `public/js`, `config/app/version.php`
  has a version for each so a deployed app never serves stale files.
- The Docker image no longer includes `.env` and `.git`, and rebuilds quicker when only code changes.
- The version and release date moved to `config/app/version.php`, alongside the CSS version.
- Updated the README, `composer install`, the queue worker, the score sheet rules and the environment variables were
  missing.
### Removed
- Bootstrap, the SCSS source, `public/package.json`, `public/yarn.lock` and axios, the app needs no Node or yarn.
- Laravel Sanctum, the skeleton `User` model and factory, `routes/api.php`, `routes/channels.php`, the broadcast
  provider and the `users`, `password_resets` and `personal_access_tokens` tables (only when empty, the migration
  leaves a table that has rows for a person to look at).
- The off canvas menu, the toast component and the toast messages, scores are confirmed in a snackbar, and the bonus
  message endpoints, the bonus tracker above the upper section replaced them.
### Fixed
- The stay signed-in checkbox was never submitted, so it did nothing.
- A weak password on the create password page was a server error, the API's errors are now shown.
- Create password sent the email and token the wrong way round when returning to the form with errors.
- The typed password was flashed into the session, and written into the form, when sign-in or create password failed.
- A 401 from the API when signing in was a server error, and the API's validation errors when signing in were lost, the
  guard reported the word "fields".
- The winner of a completed game was wrong for some scores, the players were not sorted properly.
- Players typed in a textarea on Windows, or with blank lines, were created with stray line endings or as empty names.
- Completing or deleting a game that doesn't exist was a 500 rather than a 404.
- The public score sheet crashed when the score sheet couldn't be read from the API.
- A failed account deletion still removed the sessions and told the player their account had been deleted.
- The games page was a server error when the API failed, and a failure creating the Yatzy resource right after its
  resource type crashed instead of reporting the API's status.
- Scores could be anything: `score-upper` and `score-lower` stored whatever they were sent, from a signed-in player
  or from anyone holding a public link, a three of a kind of 30, a full house of 5.
- A score failing to save only went to the browser console, it looked saved and was lost.

## [1.05.0] - [2026-09-13]
### Added
- Composer available inside the app container.
### Changed
- Registration now authenticates to the API with an internal API key, matching the API's new internal-only auth endpoints.
- Minor SEO improvements to the landing page and noindexed the auth/utility pages.

## [1.04.0] - [2023-10-12]
### Added
- Added full account deletion.
- Added a getting started section for new sign-ups, allows the user to create players and begin a game in one go.
### Changed
- Updated Yahtzee account deletion, uses the API to delete the account rather than brute force.
- Switched to action and view controllers.
- Automatically sign-in the user after password creation.
- Switched to the support@costs-to-expect.com email and removed Twitter from footer.
- Updated content throughout the app.
- Updated to Laravel 10
- Updated to Boostrap 5.3
- Updated to PHP8.2

## [v1.03.0] - [2023-07-03]
### Added
- Added Budget Pro to the footer
### Changed
- Updated the example ENV file
- Updated dependencies
- Set version and release date
### Fixed
- Corrected a link

## [1.02.0] - [2023-01-30]
### Changed
- Updated authentication to match recent changes to the Costs to Expect API.

## [1.01.1] - [2022-08-30]
### Fixed
- Corrected a type.
- Adjusted min and max for combinations.
- Impossible to score full house.
- Corrected combination outputs in log, missing space.
- Show end of score sheet toast
- Player list should check for 15 turns.
- When a player has finished their row should change in the player scores table.

## [1.01.0] - [2022-08-29]
### Changed
- Added a "How to score" section to the top of each score sheet.
- Added text explaining all the options above open games.
- Renamed the "Share" link.
- Improved the experience for new users, added text to guide the user.
### Fixed
- Corrected a validation error when credentials are invalid.
- Corrected menu links.

## [1.00.0] - [2022-08-27]

Initial release of the Yatzy scorer, this App started as a copy of our Yahtzee game scorer.
