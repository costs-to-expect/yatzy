<?php

use App\Http\Controllers\View\Authentication;
use App\Http\Controllers\View\Game;
use App\Http\Controllers\View\Index;
use App\Http\Controllers\View\Player;
use App\Http\Controllers\View\Share;
use Illuminate\Support\Facades\Route;

Route::get(
    '/create-password',
    [Authentication::class, 'createPassword']
)->name('create-password.view');

Route::post(
    '/create-password',
    [\App\Http\Controllers\Action\Authentication::class, 'createPassword']
)->name('create-password.action');

Route::get(
    '/',
    [Index::class, 'landing']
)->name('landing');

Route::get(
    '/sign-in',
    [Authentication::class, 'signIn']
)->name('sign-in.view');

Route::post(
    '/sign-in',
    [\App\Http\Controllers\Action\Authentication::class, 'signIn']
)->name('sign-in.action');

Route::get(
    '/register',
    [Authentication::class, 'register']
)->name('register.view');

Route::post(
    '/register',
    [\App\Http\Controllers\Action\Authentication::class, 'register']
)->name('register.action');

Route::get(
    '/registration-complete',
    [Authentication::class, 'registrationComplete']
)->name('registration-complete');

Route::get(
    '/sign-out',
    [Authentication::class, 'signOut']
)->name('sign-out');

Route::get(
    '/public/score-sheet/{token}',
    [Share::class, 'scoreSheet']
)->name('public.score-sheet');

Route::post(
    '/public/score-sheet/{token}/score-upper',
    [Share::class, 'scoreUpper']
)->name('public.score-upper');

Route::post(
    '/public/score-sheet/{token}/score-lower',
    [Share::class, 'scoreLower']
)->name('public.score-lower');

Route::get(
    '/public/game/{token}/player-scores',
    [Share::class, 'playerScores']
)->name('public.player-scores');

Route::get(
    '/public/game/{token}/bonus',
    [Share::class, 'playerBonus']
)->name('public.bonus');

Route::group(
    [
        'middleware' => [
            'auth'
        ]
    ],
    static function() {
        Route::get(
            '/home',
            [Index::class, 'home']
        )->name('home');


        Route::get(
            '/new-game',
            [Game::class, 'newGame']
        )->name('game.create.view');

        Route::post(
            '/new-game',
            [Game::class, 'newGameProcess']
        )->name('game.create.process');

        Route::get(
            '/game/{game_id}/player/{player_id}/score-sheet',
            [Game::class, 'scoreSheet']
        )->name('game.score-sheet');

        Route::get(
            '/games',
            [Game::class, 'index']
        )->name('games');

        Route::get(
            '/games/{game_id}',
            [Game::class, 'show']
        )->name('game.show');

        Route::post(
            '/game/{game_id}/complete',
            [Game::class, 'complete']
        )->name('game.complete');

        Route::post(
            '/game/{game_id}/complete-and-play-again',
            [Game::class, 'completeAndPlayAgain']
        )->name('game.complete.play-again');

        Route::post(
            '/game/{game_id}/delete',
            [Game::class, 'deleteGame']
        )->name('game.delete');

        Route::post(
            '/game/score-upper',
            [Game::class, 'scoreUpper']
        )->name('game.score-upper');

        Route::get(
            '/game/{game_id}/player/{player_id}/bonus',
            [Game::class, 'playerBonus']
        )->name('game.player.bonus');

        Route::get(
            '/game/{game_id}/player/{player_id}/delete',
            [Game::class, 'deleteGamePlayer']
        )->name('game.player.delete');

        Route::post(
            '/game/score-lower',
            [Game::class, 'scoreLower']
        )->name('game.score-lower');

        Route::get(
            '/game/{game_id}/player-scores',
            [Game::class, 'playerScores']
        )->name('game.player-scores');

        Route::get(
            '/add-players-to-game/{game_id}',
            [Game::class, 'addPlayersToGame']
        )->name('game.add-players.view');

        Route::post(
            '/add-players-to-game/{game_id}',
            [Game::class, 'addPlayersToGameProcess']
        )->name('game.add-players.process');


        Route::get(
            '/players',
            [Player::class, 'index']
        )->name('players');

        Route::get(
            '/new-player',
            [Player::class, 'newPlayer']
        )->name('player.create.view');

        Route::post(
            '/new-player',
            [Player::class, 'newPlayerProcess']
        )->name('player.create.process');

        Route::get(
            '/account',
            [Authentication::class, 'account']
        )->name('account');

        Route::get(
            '/account/confirm-delete-yatzy-account',
            [Authentication::class, 'confirmDeleteYatzyAccount']
        )->name('account.confirm-delete-yatzy-account');

        Route::post(
            '/account/delete-yatzy-account',
            [\App\Http\Controllers\Action\Authentication::class, 'deleteYatzyAccount']
        )->name('account.delete-yatzy-account.action');

        Route::get(
            '/account/confirm-delete-account',
            [Authentication::class, 'confirmDeleteAccount']
        )->name('account.confirm-delete-account');

        Route::post(
            '/account/delete-account',
            [\App\Http\Controllers\Action\Authentication::class, 'deleteAccount']
        )->name('account.delete-account.action');
    }
);
