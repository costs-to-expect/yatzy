<?php
declare(strict_types=1);

/*
 * What the pages say about the game. Everything that is the game's, rather than the app's, is here so a sibling scorer
 * (Scrabble, Carcassonne) changes this file, the mark in App\View\Icons and its own sheet, and the rest is shared.
 */
return [
    'key' => 'yatzy',
    'name' => 'Yatzy',
    'tagline' => 'Game Scorer',
    // The mark in the logo tile, a game is told apart by its mark and name, never its colour
    'mark' => 'yatzy',
    // What the tile of a player says, they open their score sheet
    'action' => 'Open score sheet',
    'min_players' => 1,
    // A game with a fixed number of turns shows the ring and "8 of 15 turns" on a player's tile
    'turns' => 15,
    'game_name' => 'Yatzy game',
    'game_description' => 'Yatzy game create via the Yatzy app',
];
