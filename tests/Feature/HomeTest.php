<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ShareToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\FakesTheApi;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use FakesTheApi;
    use RefreshDatabase;

    /**
     * @param list<array> $open
     * @param list<array> $closed
     * @param array<string, array> $sheets game id => score sheets
     */
    private function fakeHome(array $open = [], array $closed = [], array $sheets = [], ?array $players = null, array $overrides = []): void
    {
        $fakes = [
            $this->items('?complete=0&include-players=1') => Http::response($open, 200),
            $this->items('?complete=1&limit=5&include-players=1') => Http::response($closed, 200),
        ];

        foreach ($sheets as $game_id => $game_sheets) {
            $fakes[$this->items("/{$game_id}/data")] = Http::response($game_sheets, 200);
        }

        if ($players !== null) {
            $fakes['api.test/v3/resource-types/rt-1/categories?collection=1'] = Http::response($players, 200);
        }

        $this->fakeApi($overrides + $fakes);
    }

    private function shareToken(string $token, string $game_id, string $player_id): void
    {
        $share = new ShareToken();
        $share->token = $token;
        $share->game_id = $game_id;
        $share->player_id = $player_id;
        $share->parameters = ['game_id' => $game_id, 'player_id' => $player_id];
        $share->save();
    }

    public function test_a_new_player_is_invited_to_enter_their_players_and_start_a_game(): void
    {
        $this->fakeHome(players: []);

        $this->signedIn()->get('/home')
            ->assertOk()
            ->assertSee('Let&rsquo;s get started!', false)
            ->assertSee('action="'.route('start').'"', false)
            ->assertSee('name="players"', false)
            ->assertSee('Start the first game')
            // Nothing to pick or to look back on yet
            ->assertDontSee('Next game')
            ->assertDontSee('Last games')
            ->assertDontSee("Who&rsquo;s scoring?", false);
    }

    public function test_a_failed_start_shows_the_getting_started_form_again_with_what_was_typed_and_the_error(): void
    {
        $this->fakeHome();

        $this->signedIn()
            ->withSession(['validation.errors' => ['players' => ['errors' => ['Please enter the player names, one per line']]], '_old_input' => ['players' => "Ada\nBen"]])
            ->get('/home')
            ->assertOk()
            ->assertSee('Let&rsquo;s get started!', false)
            ->assertSee('Please enter the player names, one per line')
            ->assertSee('aria-invalid="true"', false);
    }

    public function test_a_player_with_players_and_no_game_is_offered_the_next_game_not_the_getting_started_form(): void
    {
        $this->fakeHome();

        $response = $this->signedIn()->get('/home')
            ->assertOk()
            ->assertSee('Ready for the first game?')
            ->assertSee('Next game')
            ->assertSee('action="'.route('game.create.action').'"', false)
            ->assertSee('name="name" value="Yatzy game"', false)
            ->assertSee('name="description" value="Yatzy game create via the Yatzy app"', false)
            ->assertSee('value="p-1"', false)
            ->assertSee('value="p-2"', false)
            ->assertSee(route('player.create.view'), false)
            ->assertDontSee('Let&rsquo;s get started!', false)
            ->assertDontSee('Play again with')
            ->assertSee('No finished games yet.')
            // Nobody has played yet, so nobody is chosen and the game cannot be started
            ->assertSee('Choose the players to start');

        self::assertDoesNotMatchRegularExpression('/<input type="checkbox"[^>]*\schecked/', $response->getContent());
    }

    public function test_open_games_show_each_players_score_and_turns_best_score_first(): void
    {
        $this->fakeHome(
            open: [$this->game('g-1', ['p-1' => 'Ada', 'p-2' => 'Ben'])],
            sheets: ['g-1' => $this->scoreSheets([
                'p-1' => $this->scoreSheet(['ones' => 3], ['full_house' => 22]),
                'p-2' => $this->scoreSheet(['twos' => 6], ['chance' => 25, 'yatzy' => 50]),
            ])]
        );

        $response = $this->signedIn()->get('/home')
            ->assertOk()
            ->assertSee('Game in progress')
            ->assertSee("Who&rsquo;s scoring?", false)
            // Ben is ahead, so he is first, with the crown
            ->assertSeeInOrder(['Ben', '81', 'Ada', '25'])
            ->assertSee('3 of 15 turns')
            ->assertSee('2 of 15 turns')
            ->assertSee('Ben is ahead by 56.')
            ->assertSee(route('game.score-sheet', ['game_id' => 'g-1', 'player_id' => 'p-1']), false)
            ->assertSee(route('game.score-sheet', ['game_id' => 'g-1', 'player_id' => 'p-2']), false);

        self::assertSame(1, substr_count($response->getContent(), 'Leading</span>'));
    }

    public function test_the_ways_to_manage_an_open_game_are_a_tap_away(): void
    {
        $this->fakeHome(
            open: [$this->game('g-1', ['p-1' => 'Ada', 'p-2' => 'Ben'])],
            sheets: ['g-1' => []]
        );

        $this->signedIn()->get('/home')
            ->assertOk()
            ->assertSee(route('game.add-players.view', ['game_id' => 'g-1']), false)
            ->assertSee(route('game.complete.action', ['game_id' => 'g-1']), false)
            ->assertSee(route('game.complete.play-again.action', ['game_id' => 'g-1']), false)
            ->assertSee(route('game.delete.action', ['game_id' => 'g-1']), false)
            ->assertSee(route('game.player.delete', ['game_id' => 'g-1', 'player_id' => 'p-1']), false)
            ->assertSee(route('game.player.delete', ['game_id' => 'g-1', 'player_id' => 'p-2']), false)
            ->assertSee('data-dialog-open="share-dialog"', false)
            ->assertSee('data-dialog-open="finish-dialog"', false)
            ->assertSee('data-dialog-open="delete-dialog"', false);
    }

    public function test_nobody_is_crowned_until_someone_is_ahead(): void
    {
        $this->fakeHome(
            open: [$this->game('g-1', ['p-1' => 'Ada', 'p-2' => 'Ben'])],
            sheets: ['g-1' => $this->scoreSheets([
                'p-1' => $this->scoreSheet(['ones' => 3]),
                'p-2' => $this->scoreSheet(['ones' => 3]),
            ])]
        );

        $this->signedIn()->get('/home')->assertOk()->assertDontSee('Leading')->assertDontSee('is ahead by');

        $this->fakeHome(open: [$this->game('g-2', ['p-1' => 'Ada', 'p-2' => 'Ben'])], sheets: ['g-2' => []]);

        $this->signedIn()->get('/home')->assertOk()->assertDontSee('Leading');
    }

    public function test_a_game_that_is_finished_for_a_player_says_so(): void
    {
        $finished = $this->scoreSheet(
            ['ones' => 3, 'twos' => 6, 'threes' => 9, 'fours' => 12, 'fives' => 15, 'sixes' => 18],
            ['one_pair' => 10, 'two_pair' => 16, 'three_of_a_kind' => 15, 'four_of_a_kind' => 0, 'full_house' => 22, 'small_straight' => 15, 'large_straight' => 20, 'yatzy' => 50, 'chance' => 22]
        );
        $this->fakeHome(
            open: [$this->game('g-1', ['p-1' => 'Ada', 'p-2' => 'Ben'])],
            sheets: ['g-1' => $this->scoreSheets(['p-1' => $finished, 'p-2' => $this->scoreSheet(['ones' => 1])])]
        );

        $this->signedIn()->get('/home')
            ->assertOk()
            ->assertSee('All 15 turns played')
            ->assertSee('1 of 15 turns')
            // Finishing the game warns that Ben has not scored all his turns
            ->assertSee('Not everyone has scored all 15 turns yet.');
    }

    public function test_several_open_games_are_switched_between_with_the_game_chosen_in_the_address(): void
    {
        $this->fakeHome(
            open: [
                $this->game('g-1', ['p-1' => 'Ada', 'p-2' => 'Ben']),
                $this->game('g-2', ['p-2' => 'Ben', 'p-3' => 'Cleo']),
            ],
            sheets: [
                'g-1' => $this->scoreSheets(['p-1' => $this->scoreSheet(['ones' => 3])]),
                'g-2' => $this->scoreSheets(['p-3' => $this->scoreSheet(['twos' => 6])]),
            ],
            players: $this->playerCollection(['p-1' => 'Ada', 'p-2' => 'Ben', 'p-3' => 'Cleo'])
        );

        // The first game, until another is chosen
        $this->signedIn()->get('/home')
            ->assertOk()
            ->assertSee('Open games')
            ->assertSee(route('home', ['game' => 'g-2']), false)
            ->assertSee(route('game.score-sheet', ['game_id' => 'g-1', 'player_id' => 'p-1']), false)
            ->assertDontSee(route('game.score-sheet', ['game_id' => 'g-2', 'player_id' => 'p-3']), false);

        $this->signedIn()->get('/home?game=g-2')
            ->assertOk()
            ->assertSee(route('game.score-sheet', ['game_id' => 'g-2', 'player_id' => 'p-3']), false)
            ->assertDontSee(route('game.score-sheet', ['game_id' => 'g-1', 'player_id' => 'p-1']), false);

        // A game that is not open falls back to the first
        $this->signedIn()->get('/home?game=nope')
            ->assertOk()
            ->assertSee(route('game.score-sheet', ['game_id' => 'g-1', 'player_id' => 'p-1']), false);
    }

    public function test_open_games_on_different_days_are_told_apart_by_the_day(): void
    {
        $earlier = $this->game('g-1', ['p-1' => 'Ada']);
        $earlier['created_at'] = now()->subDays(3)->setTime(19, 5)->toDateTimeString();
        $later = $this->game('g-2', ['p-2' => 'Ben']);
        $later['created_at'] = now()->toDateTimeString();
        $this->fakeHome(open: [$earlier, $later], sheets: ['g-1' => [], 'g-2' => []]);

        // Different days, so the day is enough
        $response = $this->signedIn()->get('/home')->assertOk();
        $response->assertSee(now()->subDays(3)->format('l'));
        $response->assertSee('Today');
    }

    public function test_open_games_are_numbered_when_the_api_does_not_say_when_they_started(): void
    {
        $this->fakeHome(open: [$this->game('g-1', ['p-1' => 'Ada']), $this->game('g-2', ['p-2' => 'Ben'])], sheets: ['g-1' => [], 'g-2' => []]);

        $this->signedIn()->get('/home')->assertOk()->assertSee('Game 1')->assertSee('Game 2');
    }

    public function test_two_games_that_started_on_the_same_day_are_told_apart_by_the_time(): void
    {
        $first = $this->game('g-1', ['p-1' => 'Ada']);
        $first['created_at'] = now()->startOfDay()->setTime(10, 15)->toDateTimeString();
        $second = $this->game('g-2', ['p-2' => 'Ben']);
        $second['created_at'] = now()->startOfDay()->setTime(10, 45)->toDateTimeString();
        $this->fakeHome(open: [$first, $second], sheets: ['g-1' => [], 'g-2' => []]);

        $this->signedIn()->get('/home')->assertOk()->assertSee('Today, 10:15')->assertSee('Today, 10:45');
    }

    public function test_a_single_open_game_has_nothing_to_switch_between(): void
    {
        $this->fakeHome(open: [$this->game('g-1', ['p-1' => 'Ada'])], sheets: ['g-1' => []]);

        $this->signedIn()->get('/home')->assertOk()->assertDontSee('Open games');
    }

    public function test_how_long_a_game_has_been_running_is_shown_when_the_api_says_when_it_started(): void
    {
        $started = $this->game('g-1', ['p-1' => 'Ada']);
        $started['created_at'] = now()->subMinutes(40)->toDateTimeString();
        $this->fakeHome(open: [$started], sheets: ['g-1' => []]);

        $this->signedIn()->get('/home')->assertOk()->assertSee('Game in progress')->assertSee('40m');
    }

    public function test_the_time_is_left_out_when_the_api_does_not_say_when_a_game_started(): void
    {
        $this->fakeHome(open: [$this->game('g-1', ['p-1' => 'Ada'])], sheets: ['g-1' => []]);

        $response = $this->signedIn()->get('/home')->assertOk()->assertSee('Game in progress');

        self::assertDoesNotMatchRegularExpression('/Game in progress\s*&middot;/', $response->getContent());
    }

    public function test_a_public_score_sheet_link_is_only_offered_to_players_with_a_share_token(): void
    {
        $this->shareToken('token-for-ada', 'g-1', 'p-1');
        $this->fakeHome(open: [$this->game('g-1', ['p-1' => 'Ada', 'p-2' => 'Ben'])], sheets: ['g-1' => []]);

        $response = $this->signedIn()->get('/home')
            ->assertOk()
            ->assertSee('data-copy="'.route('public.score-sheet', ['token' => 'token-for-ada']).'"', false)
            ->assertSee('No link');

        // Ben has no token, so there is a single public link on the page.
        self::assertSame(1, substr_count($response->getContent(), '/public/score-sheet/'));
    }

    public function test_only_the_share_links_of_the_open_games_are_read(): void
    {
        $this->shareToken('token-for-ada', 'g-1', 'p-1');
        $this->shareToken('token-of-another-game', 'g-9', 'p-1');
        $this->fakeHome(open: [$this->game('g-1', ['p-1' => 'Ada'])], sheets: ['g-1' => []]);

        $this->signedIn()->get('/home')->assertOk()->assertDontSee('token-of-another-game');
    }

    public function test_recent_games_show_who_won_and_what_everyone_else_scored(): void
    {
        $this->fakeHome(closed: [
            $this->game('g-9', ['p-1' => 'Ada', 'p-2' => 'Ben'], true, [
                ['player_id' => 'p-1', 'player_name' => 'Ada', 'score' => 211],
                ['player_id' => 'p-2', 'player_name' => 'Ben', 'score' => 187],
            ]),
        ]);

        $this->signedIn()->get('/home')
            ->assertOk()
            ->assertSee('Last games')
            ->assertSeeInOrder(['Ada', 'won with', '211'])
            ->assertSee('Ben 187')
            ->assertSee(route('game.show', ['game_id' => 'g-9']), false)
            ->assertSee(route('games'), false);
    }

    public function test_a_recent_game_with_no_scores_is_left_out(): void
    {
        $this->fakeHome(closed: [$this->game('g-9', ['p-1' => 'Ada'], true, [])]);

        $this->signedIn()->get('/home')->assertOk()->assertSee('No finished games yet.');
    }

    public function test_with_no_game_running_the_page_offers_to_play_again_with_the_players_of_the_last_game(): void
    {
        $this->fakeHome(closed: [
            $this->game('g-9', ['p-1' => 'Ada', 'p-2' => 'Ben'], true, [
                ['player_id' => 'p-2', 'player_name' => 'Ben', 'score' => 211],
                ['player_id' => 'p-1', 'player_name' => 'Ada', 'score' => 187],
            ]),
        ]);

        $response = $this->signedIn()->get('/home')
            ->assertOk()
            ->assertSee('Ready for another game?')
            ->assertSee('Last played with Ben and Ada.')
            ->assertSee('Play again with Ben &amp; Ada', false);

        // The same players, started in one tap, and chosen for the next game as well
        self::assertSame(2, substr_count($response->getContent(), '<input type="hidden" name="players[]" value="p-'));
        self::assertMatchesRegularExpression('/name="players\[\]" value="p-1"[^>]*checked/', $response->getContent());
        self::assertMatchesRegularExpression('/name="players\[\]" value="p-2"[^>]*checked/', $response->getContent());
        $response->assertSee('Start game with 2 players');
    }

    public function test_when_the_last_game_was_played_is_shown_when_the_api_says_when_it_started(): void
    {
        $game = $this->game('g-9', ['p-1' => 'Ada'], true, [['player_id' => 'p-1', 'player_name' => 'Ada', 'score' => 100]]);
        $game['created_at'] = now()->subDay()->toDateTimeString();
        $this->fakeHome(closed: [$game]);

        $this->signedIn()->get('/home')->assertOk()->assertSee('Last played yesterday with Ada.');
    }

    public function test_every_api_request_is_made_with_the_players_bearer_token(): void
    {
        $this->fakeHome(open: [$this->game('g-1', ['p-1' => 'Ada'])], sheets: ['g-1' => []]);

        $this->signedIn()->get('/home')->assertOk();

        Http::assertSent(fn (Request $request) => $request->header('Authorization') === ['Bearer test-bearer-token']);
        Http::assertNotSent(fn (Request $request) => $request->header('Authorization') !== ['Bearer test-bearer-token']);
        Http::assertNotSent(fn (Request $request) => $request->hasHeader('X-Internal-Api-Key'));
    }

    public function test_the_lookups_for_the_players_resource_type_and_resource_always_skip_the_api_cache(): void
    {
        $this->fakeHome();

        $this->signedIn()->get('/home')->assertOk();

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/v3/resource-types?item-type=item-type-id')
            && $request->hasHeader('X-Skip-Cache'));
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/resources?item-subtype=item-subtype-id')
            && $request->hasHeader('X-Skip-Cache'));
    }

    public function test_a_token_the_api_has_stopped_accepting_is_the_apis_401_not_a_broken_page(): void
    {
        $this->fakeHome(overrides: ['api.test/v3/resource-types?item-type=item-type-id' => Http::response(['message' => 'Unauthenticated'], 401)]);

        $this->signedIn()->get('/home')->assertUnauthorized();
    }

    public function test_failed_game_lookups_show_as_empty_lists_rather_than_an_error(): void
    {
        $this->fakeApi([
            $this->items('?complete=0&include-players=1') => Http::response(['message' => 'Broken'], 500),
            $this->items('?complete=1&limit=5&include-players=1') => Http::response(['message' => 'Broken'], 500),
        ]);

        $this->signedIn()->get('/home')
            ->assertOk()
            ->assertDontSee('Game in progress')
            ->assertSee('Ready for the first game?')
            ->assertSee('No finished games yet.');
    }
}
