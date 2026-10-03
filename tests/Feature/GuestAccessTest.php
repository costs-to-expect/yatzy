<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\FakesTheApi;
use Tests\TestCase;

class GuestAccessTest extends TestCase
{
    use FakesTheApi;

    /**
     * @return array<string, array{string, string}> the method and uri of every signed-in only route
     */
    public static function signedInOnlyRoutes(): array
    {
        return [
            'home' => ['GET', '/home'],
            'games' => ['GET', '/games'],
            'game overview' => ['GET', '/games/g-1'],
            'new game form' => ['GET', '/new-game'],
            'create game' => ['POST', '/new-game'],
            'start' => ['POST', '/start'],
            'score sheet' => ['GET', '/game/g-1/player/p-1/score-sheet'],
            'remove player' => ['POST', '/game/g-1/player/p-1/delete'],
            'player scores' => ['GET', '/game/g-1/player-scores'],
            'complete game' => ['POST', '/game/g-1/complete'],
            'complete game and play again' => ['POST', '/game/g-1/complete-and-play-again'],
            'delete game' => ['POST', '/game/g-1/delete'],
            'score upper' => ['POST', '/game/score-upper'],
            'score lower' => ['POST', '/game/score-lower'],
            'score clear' => ['POST', '/game/score-clear'],
            'add players form' => ['GET', '/add-players-to-game/g-1'],
            'add players' => ['POST', '/add-players-to-game/g-1'],
            'players' => ['GET', '/players'],
            'new player form' => ['GET', '/new-player'],
            'create player' => ['POST', '/new-player'],
            'account' => ['GET', '/account'],
            'confirm delete yatzy account' => ['GET', '/account/confirm-delete-yatzy-account'],
            'delete yatzy account' => ['POST', '/account/delete-yatzy-account'],
            'confirm delete account' => ['GET', '/account/confirm-delete-account'],
            'delete account' => ['POST', '/account/delete-account'],
        ];
    }

    #[DataProvider('signedInOnlyRoutes')]
    public function test_guests_are_sent_to_the_sign_in_page(string $method, string $uri): void
    {
        $this->call($method, $uri)->assertRedirect(route('sign-in.view'));
    }

    #[DataProvider('signedInOnlyRoutes')]
    public function test_a_guest_never_causes_a_request_to_the_api(string $method, string $uri): void
    {
        $this->call($method, $uri);

        \Illuminate\Support\Facades\Http::assertNothingSent();
    }

    public function test_the_public_and_authentication_pages_do_not_need_an_account(): void
    {
        foreach (['/', '/sign-in', '/register', '/registration-complete'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_a_signed_in_player_is_not_redirected_away_from_a_page_they_may_view(): void
    {
        $this->signedIn()->get('/players')->assertOk();
    }
}
