# Changelog

The complete changelog for the Costs to Expect REST API, our changelog follows the format defined at https://keepachangelog.com/en/1.0.0/

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
