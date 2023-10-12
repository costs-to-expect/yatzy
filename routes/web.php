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
    [\App\Http\Controllers\Action\Share::class, 'scoreUpper']
)->name('public.score-upper.action');

Route::post(
    '/public/score-sheet/{token}/score-lower',
    [\App\Http\Controllers\Action\Share::class, 'scoreLower']
)->name('public.score-lower.action');

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

        Route::post(
            '/start',
            [\App\Http\Controllers\Action\Game::class, 'start']
        )->name('start');

        Route::get(
            '/new-game',
            [Game::class, 'newGame']
        )->name('game.create.view');

        Route::post(
            '/new-game',
            [\App\Http\Controllers\Action\Game::class, 'newGame']
        )->name('game.create.action');

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
            [\App\Http\Controllers\Action\Game::class, 'complete']
        )->name('game.complete.action');

        Route::post(
            '/game/{game_id}/complete-and-play-again',
            [\App\Http\Controllers\Action\Game::class, 'completeAndPlayAgain']
        )->name('game.complete.play-again.action');

        Route::post(
            '/game/{game_id}/delete',
            [\App\Http\Controllers\Action\Game::class, 'deleteGame']
        )->name('game.delete.action');

        Route::post(
            '/game/score-upper',
            [\App\Http\Controllers\Action\Game::class, 'scoreUpper']
        )->name('game.score-upper.action');

        Route::get(
            '/game/{game_id}/player/{player_id}/bonus',
            [Game::class, 'playerBonus']
        )->name('game.player.bonus');

        Route::get(
            '/game/{game_id}/player/{player_id}/delete',
            [\App\Http\Controllers\Action\Game::class, 'deleteGamePlayer']
        )->name('game.player.delete');

        Route::post(
            '/game/score-lower',
            [\App\Http\Controllers\Action\Game::class, 'scoreLower']
        )->name('game.score-lower.action');

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
            [\App\Http\Controllers\Action\Game::class, 'addPlayersToGame']
        )->name('game.add-players.action');


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
            [\App\Http\Controllers\Action\Player::class, 'newPlayer']
        )->name('player.create.action');

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
