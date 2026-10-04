<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ShareToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\FakesTheApi;
use Tests\TestCase;

class GamesViewTest extends TestCase
{
    use FakesTheApi;
    use RefreshDatabase;

    private function completeGame(string $id, int $ada, int $ben): array
    {
        return $this->game($id, ['p-1' => 'Ada', 'p-2' => 'Ben'], true, [
            ['player_id' => 'p-1', 'player_name' => 'Ada', 'score' => $ada],
            ['player_id' => 'p-2', 'player_name' => 'Ben', 'score' => $ben],
        ]);
    }

    private function paginationHeaders(string $previous, string $next, int $offset, int $limit, int $total): array
    {
        return [
            'X-Link-Previous' => $previous,
            'X-Link-Next' => $next,
            'X-Offset' => (string) $offset,
            'X-Limit' => (string) $limit,
            'X-Total-Count' => (string) $total,
        ];
    }

    public function test_the_games_page_lists_complete_games_with_the_final_scores(): void
    {
        $this->fakeApi([
            $this->items('?complete=1&include-players=1&offset=0&limit=10') => Http::response(
                [$this->completeGame('g-1', 211, 187), $this->completeGame('g-2', 150, 160)],
                200,
                $this->paginationHeaders('', '', 0, 10, 2)
            ),
        ]);

        $this->signedIn()->get('/games')
            ->assertOk()
            ->assertSee('Every game you have finished')
            ->assertSeeInOrder(['Ada', 'won with', '211'])
            ->assertSeeInOrder(['Ben', 'won with', '160'])
            ->assertSee(route('game.show', ['game_id' => 'g-2']), false)
            ->assertSee(route('game.score-sheet', ['game_id' => 'g-1', 'player_id' => 'p-2']), false)
            ->assertSee('1 -')
            ->assertSee('of')
            // Nowhere to page to: both buttons are there, neither is a link
            ->assertDontSee(route('games', ['offset' => 10, 'limit' => 10]), false)
            ->assertDontSee(route('games', ['offset' => 0, 'limit' => 10]), false);
    }

    public function test_the_games_page_links_to_the_previous_and_next_pages_of_games(): void
    {
        $this->fakeApi([
            $this->items('?complete=1&include-players=1&offset=10&limit=5') => Http::response(
                [$this->completeGame('g-6', 100, 90)],
                200,
                $this->paginationHeaders('/v3/previous', '/v3/next', 10, 5, 22)
            ),
        ]);

        $this->signedIn()->get('/games?offset=10&limit=5')
            ->assertOk()
            ->assertSee('href="'.e(route('games', ['offset' => 5, 'limit' => 5])).'"', false)
            ->assertSee('href="'.e(route('games', ['offset' => 15, 'limit' => 5])).'"', false)
            ->assertSee('11 -');
    }

    public function test_the_games_page_never_pages_back_before_the_first_game(): void
    {
        $this->fakeApi([
            $this->items('?complete=1&include-players=1&offset=2&limit=10') => Http::response(
                [$this->completeGame('g-3', 100, 90)],
                200,
                $this->paginationHeaders('/v3/previous', '', 2, 10, 3)
            ),
        ]);

        $this->signedIn()->get('/games?offset=2')
            ->assertOk()
            ->assertSee('href="'.e(route('games', ['offset' => 0, 'limit' => 10])).'"', false);
    }

    public function test_the_games_page_explains_when_no_games_have_been_played(): void
    {
        $this->fakeApi([
            $this->items('?complete=1&include-players=1&offset=0&limit=10') => Http::response(
                [],
                200,
                $this->paginationHeaders('', '', 0, 10, 0)
            ),
        ]);

        $this->signedIn()->get('/games')->assertOk()->assertSee('You haven&rsquo;t played any games.', false)->assertSee(route('game.create.view'), false);
    }

    public function test_an_api_failure_listing_games_is_passed_on(): void
    {
        $this->fakeApi([
            $this->items('?complete=1&include-players=1&offset=0&limit=10') => Http::response(['message' => 'The API is down'], 503),
        ]);

        $this->signedIn()->get('/games')->assertStatus(503);
    }

    public function test_the_overview_of_an_open_game_shows_scores_links_and_the_ways_to_manage_it(): void
    {
        ShareToken::issue('rt-1', 'r-1', 'g-1', 'p-1', 'Ada', 'owner-bearer');
        $token = (string) ShareToken::query()->value('token');

        $this->fakeApi([
            $this->items('/g-1?include-players=1') => Http::response($this->game('g-1', ['p-1' => 'Ada', 'p-2' => 'Ben']), 200),
            $this->items('/g-1/data') => Http::response($this->scoreSheets(['p-1' => $this->scoreSheet(['ones' => 3], ['chance' => 20])]), 200),
        ]);

        $this->signedIn()->get('/games/g-1')
            ->assertOk()
            ->assertSee('Game overview')
            ->assertSeeInOrder(['Ada', '23', 'Ben', '0'])
            ->assertSee('2 of 15 turns')
            ->assertSee(route('game.score-sheet', ['game_id' => 'g-1', 'player_id' => 'p-2']), false)
            ->assertSee('data-copy="'.route('public.score-sheet', ['token' => $token]).'"', false)
            ->assertSee(route('game.player.delete', ['game_id' => 'g-1', 'player_id' => 'p-2']), false)
            ->assertSee(route('game.add-players.view', ['game_id' => 'g-1']), false)
            ->assertSee(route('game.complete.action', ['game_id' => 'g-1']), false)
            ->assertSee(route('game.complete.play-again.action', ['game_id' => 'g-1']), false)
            ->assertSee(route('game.delete.action', ['game_id' => 'g-1']), false);
    }

    public function test_the_overview_only_offers_the_share_links_of_its_own_game(): void
    {
        ShareToken::issue('rt-1', 'r-1', 'g-other', 'p-1', 'Ada', 'owner-bearer');
        $other = (string) ShareToken::query()->value('token');

        $this->fakeApi([
            $this->items('/g-1?include-players=1') => Http::response($this->game('g-1', ['p-1' => 'Ada']), 200),
            $this->items('/g-1/data') => Http::response($this->scoreSheets([]), 200),
        ]);

        $this->signedIn()->get('/games/g-1')->assertOk()->assertDontSee($other, false);
    }

    public function test_the_overview_of_a_complete_game_does_not_offer_to_change_it(): void
    {
        $this->fakeApi([
            $this->items('/g-9?include-players=1') => Http::response($this->completeGame('g-9', 211, 187), 200),
            $this->items('/g-9/data') => Http::response($this->scoreSheets([
                'p-1' => $this->scoreSheet([], ['chance' => 211]),
                'p-2' => $this->scoreSheet([], ['chance' => 187]),
            ]), 200),
        ]);

        $this->signedIn()->get('/games/g-9')
            ->assertOk()
            ->assertSee('Ada')
            ->assertSee('won with 211')
            ->assertSeeInOrder(['Ada', '211', 'Ben', '187'])
            ->assertSee('Winner')
            ->assertSee(route('game.score-sheet', ['game_id' => 'g-9', 'player_id' => 'p-1']), false)
            ->assertDontSee(route('game.player.delete', ['game_id' => 'g-9', 'player_id' => 'p-1']), false)
            ->assertDontSee(route('game.complete.action', ['game_id' => 'g-9']), false)
            ->assertDontSee(route('game.delete.action', ['game_id' => 'g-9']), false);
    }

    public function test_the_overview_of_a_game_that_does_not_exist_is_a_404(): void
    {
        $this->fakeApi([$this->items('/g-404?include-players=1') => Http::response(['message' => 'Not found'], 404)]);

        $this->signedIn()->get('/games/g-404')->assertNotFound();
    }

    public function test_the_player_scores_are_read_as_json_with_the_progress_of_every_player(): void
    {
        $finished = $this->scoreSheet(
            ['ones' => 3, 'twos' => 6, 'threes' => 9, 'fours' => 12, 'fives' => 15, 'sixes' => 18],
            ['one_pair' => 10, 'two_pair' => 16, 'three_of_a_kind' => 15, 'four_of_a_kind' => 0, 'full_house' => 22, 'small_straight' => 15, 'large_straight' => 20, 'yatzy' => 50, 'chance' => 22]
        );

        $this->fakeApi([
            $this->items('/g-1/categories') => Http::response($this->assignedPlayers(['p-1' => 'Ada', 'p-2' => 'Ben', 'p-3' => 'Cleo']), 200),
            $this->items('/g-1/data') => Http::response($this->scoreSheets([
                'p-1' => $finished,
                'p-2' => $this->scoreSheet(['ones' => 2]),
                // Removed from the game since, no longer in the list
                'p-9' => $this->scoreSheet(['ones' => 5]),
            ]), 200),
        ]);

        $this->signedIn()->get('/game/g-1/player-scores')
            ->assertOk()
            ->assertExactJson(['players' => [
                ['id' => 'p-1', 'name' => 'Ada', 'upper' => 63, 'bonus' => 50, 'lower' => 170, 'total' => 283, 'turns' => 15],
                ['id' => 'p-2', 'name' => 'Ben', 'upper' => 2, 'bonus' => 0, 'lower' => 0, 'total' => 2, 'turns' => 1],
                // Nothing scored yet, no score sheet at all
                ['id' => 'p-3', 'name' => 'Cleo', 'upper' => 0, 'bonus' => 0, 'lower' => 0, 'total' => 0, 'turns' => 0],
            ]]);
    }

    public function test_the_player_scores_table_is_a_404_when_the_game_cannot_be_found(): void
    {
        $this->fakeApi([$this->items('/g-404/categories') => Http::response(['message' => 'Not found'], 404)]);

        $this->signedIn()->get('/game/g-404/player-scores')->assertNotFound();
    }

    public function test_a_game_without_score_sheets_cannot_show_scores(): void
    {
        $this->fakeApi([
            $this->items('/g-1/categories') => Http::response($this->assignedPlayers(['p-1' => 'Ada']), 200),
            $this->items('/g-1/data') => Http::response(['message' => 'Not found'], 404),
        ]);

        $this->signedIn()->get('/game/g-1/player-scores')->assertNotFound();
    }
}
