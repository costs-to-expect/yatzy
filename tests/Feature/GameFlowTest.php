<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ShareToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\FakesTheApi;
use Tests\TestCase;

class GameFlowTest extends TestCase
{
    use FakesTheApi;
    use RefreshDatabase;

    private const PLAYER_NAMES = ['p-1' => 'Ada', 'p-2' => 'Ben', 'p-3' => 'Cleo'];

    /**
     * Fakes for creating a game and adding players, a created game is g-new and a player added
     * to it is returned with a name derived from their id.
     */
    private function fakeCreatingGames(array $overrides = []): void
    {
        $this->fakeApi($overrides + [
            $this->items() => Http::response(['id' => 'g-new'], 201),
            $this->items('/g-new/categories') => fn (Request $request) => Http::response([
                'category' => ['id' => $request['category_id'], 'name' => self::PLAYER_NAMES[$request['category_id']] ?? 'Name of '.$request['category_id']],
            ], 201),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function tokenParameters(string $game_id, string $player_id): array
    {
        $token = ShareToken::query()->where('game_id', $game_id)->where('player_id', $player_id)->firstOrFail();

        return $token->parameters;
    }

    // New game

    public function test_a_game_is_created_and_each_player_added_with_a_share_token(): void
    {
        $this->fakeCreatingGames();

        $this->signedIn()
            ->post('/new-game', [
                'name' => 'Yatzy game',
                'description' => 'Yatzy game create via the Yatzy app',
                'players' => ['p-1', 'p-2'],
            ])
            ->assertRedirect(route('game.show', ['game_id' => 'g-new']));

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === 'http://api.test/v3/resource-types/rt-1/resources/r-1/items'
            && $request->data() === ['name' => 'Yatzy game', 'description' => 'Yatzy game create via the Yatzy app']);
        self::assertCount(2, $this->sent('POST', '/items/g-new/categories'));

        $this->assertDatabaseCount('share_token', 2);
        self::assertSame(
            [
                'resource_type_id' => 'rt-1',
                'resource_id' => 'r-1',
                'game_id' => 'g-new',
                'player_id' => 'p-2',
                'player_name' => 'Ben',
                'owner_bearer' => 'test-bearer-token',
            ],
            $this->tokenParameters('g-new', 'p-2')
        );
        self::assertTrue(Str::isUuid(ShareToken::query()->where('player_id', 'p-1')->value('token')));
    }

    public function test_a_game_needs_players(): void
    {
        $this->fakeCreatingGames();

        $this->signedIn()
            ->post('/new-game', ['name' => 'Yatzy game', 'description' => 'A game'])
            ->assertRedirect(route('game.create.view'))
            ->assertSessionHas('validation.errors', ['players' => ['errors' => ['Please select your players']]]);

        self::assertCount(0, $this->sent('POST', '/items'));
    }

    public function test_the_new_game_form_asks_for_the_players_to_include(): void
    {
        $this->signedIn()->get('/new-game')
            ->assertOk()
            ->assertSee('action="'.route('game.create.action').'"', false)
            ->assertSee('name="players[]"', false)
            ->assertSee('value="p-1"', false)
            ->assertSee('Ada')
            ->assertSee('Ben');
    }

    public function test_api_validation_errors_creating_a_game_return_to_the_form(): void
    {
        $this->fakeCreatingGames([$this->items() => Http::response([
            'message' => 'Validation error.',
            'fields' => ['name' => ['errors' => ['The name field is required.']]],
        ], 422)]);

        $this->signedIn()
            ->post('/new-game', ['name' => 'x', 'description' => 'A game', 'players' => ['p-1']])
            ->assertRedirect(route('game.create.view'))
            ->assertSessionHas('validation.errors', ['name' => ['errors' => ['The name field is required.']]]);
    }

    public function test_any_other_api_failure_creating_a_game_is_passed_on(): void
    {
        $this->fakeCreatingGames([$this->items() => Http::response(['message' => 'The API is down'], 503)]);

        $this->signedIn()
            ->post('/new-game', ['name' => 'Yatzy game', 'description' => 'A game', 'players' => ['p-1']])
            ->assertStatus(503);
    }

    // Start (first game, players typed in)

    public function test_starting_creates_each_player_then_the_game_and_its_share_tokens(): void
    {
        $this->fakeCreatingGames([
            'api.test/v3/resource-types/rt-1/categories' => fn (Request $request) => Http::response(['id' => 'new-'.strtolower($request['name'])], 201),
            $this->items('/g-new/categories') => fn (Request $request) => Http::response([
                'category' => ['id' => $request['category_id'], 'name' => ucfirst(substr($request['category_id'], 4))],
            ], 201),
        ]);

        $this->signedIn()
            ->post('/start', ['players' => "Ada\nBen"])
            ->assertRedirect(route('game.show', ['game_id' => 'g-new']));

        $created = array_map(fn (Request $request) => $request['name'], $this->sent('POST', '/resource-types/rt-1/categories'));
        self::assertSame(['Ada', 'Ben'], $created);

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === 'http://api.test/v3/resource-types/rt-1/resources/r-1/items'
            && $request['name'] === 'Yatzy game');

        $this->assertDatabaseCount('share_token', 2);
        self::assertSame('Ada', $this->tokenParameters('g-new', 'new-ada')['player_name']);
        self::assertSame('Ben', $this->tokenParameters('g-new', 'new-ben')['player_name']);
    }

    /**
     * Browsers submit the new lines of a textarea as CRLF, and people leave blank lines.
     */
    public function test_starting_copes_with_windows_line_endings_and_blank_lines(): void
    {
        $this->fakeCreatingGames([
            'api.test/v3/resource-types/rt-1/categories' => fn (Request $request) => Http::response(['id' => 'new-'.strtolower($request['name'])], 201),
        ]);

        $this->signedIn()
            ->post('/start', ['players' => "Ada\r\n\r\n  Ben  \r\nCleo"])
            ->assertRedirect(route('game.show', ['game_id' => 'g-new']));

        $created = array_map(fn (Request $request) => $request['name'], $this->sent('POST', '/resource-types/rt-1/categories'));
        self::assertSame(['Ada', 'Ben', 'Cleo'], $created);
    }

    public function test_starting_needs_the_names_of_the_players(): void
    {
        $this->fakeCreatingGames();

        $this->signedIn()
            ->post('/start', ['players' => ''])
            ->assertRedirect(route('home'))
            ->assertSessionHas('validation.errors', ['players' => ['errors' => ['Please enter the player names, one per line']]]);

        self::assertCount(0, $this->sent('POST', '/categories'));
    }

    public function test_starting_reports_a_player_name_the_api_rejects(): void
    {
        $this->fakeCreatingGames([
            'api.test/v3/resource-types/rt-1/categories' => Http::response([
                'message' => 'Validation error.',
                'fields' => ['name' => ['errors' => ['The name has already been taken.']]],
            ], 422),
        ]);

        $this->signedIn()
            ->post('/start', ['players' => 'Ada'])
            ->assertRedirect(route('home'))
            ->assertSessionHas('validation.errors', fn (array $errors) => str_contains($errors['players']['errors'][0], 'Failed to create player named "Ada"'));

        self::assertCount(0, $this->sent('POST', '/items'));
    }

    // Adding players to an open game

    public function test_the_add_players_page_offers_the_players_not_yet_in_the_game(): void
    {
        $this->fakeApi([
            $this->items('/g-1') => Http::response($this->game('g-1', ['p-1' => 'Ada']), 200),
            $this->items('/g-1/categories') => Http::response($this->assignedPlayers(['p-1' => 'Ada']), 200),
        ]);

        $this->signedIn()->get('/add-players-to-game/g-1')
            ->assertOk()
            ->assertSee('Playing now')
            ->assertSee('value="p-2"', false)
            ->assertDontSee('value="p-1"', false);
    }

    public function test_players_are_added_to_an_open_game_with_share_tokens(): void
    {
        $this->fakeApi([
            $this->items('/g-1/categories') => fn (Request $request) => Http::response([
                'category' => ['id' => $request['category_id'], 'name' => self::PLAYER_NAMES[$request['category_id']]],
            ], 201),
        ]);

        $this->signedIn()
            ->post('/add-players-to-game/g-1', ['players' => ['p-2', 'p-3']])
            ->assertRedirect(route('home'));

        self::assertSame(['p-2', 'p-3'], array_map(fn (Request $request) => $request['category_id'], $this->sent('POST', '/items/g-1/categories')));
        self::assertSame('Cleo', $this->tokenParameters('g-1', 'p-3')['player_name']);
        self::assertSame('test-bearer-token', $this->tokenParameters('g-1', 'p-3')['owner_bearer']);
    }

    public function test_adding_players_needs_a_selection(): void
    {
        $this->fakeApi();

        $this->signedIn()
            ->post('/add-players-to-game/g-1', [])
            ->assertRedirect(route('game.add-players.view', ['game_id' => 'g-1']))
            ->assertSessionHas('validation.errors', ['players' => ['errors' => ['Please select the additional players']]]);
    }

    public function test_adding_a_player_the_api_rejects_returns_to_the_form_or_passes_on_the_failure(): void
    {
        $this->fakeApi([$this->items('/g-1/categories') => Http::response([
            'message' => 'Validation error.',
            'fields' => ['category_id' => ['errors' => ['The player is already assigned.']]],
        ], 422)]);

        $this->signedIn()
            ->post('/add-players-to-game/g-1', ['players' => ['p-1']])
            ->assertRedirect(route('game.add-players.view', ['game_id' => 'g-1']))
            ->assertSessionHas('validation.errors', ['category_id' => ['errors' => ['The player is already assigned.']]]);

        $this->fakeApi([$this->items('/g-2/categories') => Http::response(['message' => 'Not allowed'], 403)]);

        $this->signedIn()
            ->post('/add-players-to-game/g-2', ['players' => ['p-1']])
            ->assertForbidden();
    }

    // Removing a player

    public function test_a_player_is_removed_with_their_score_sheet_assignment_and_share_token(): void
    {
        foreach (['p-1', 'p-2'] as $player_id) {
            $token = new ShareToken();
            $token->token = 'token-'.$player_id;
            $token->game_id = 'g-1';
            $token->player_id = $player_id;
            $token->parameters = '{}';
            $token->save();
        }

        $this->fakeApi([
            $this->items('/g-1/data/p-1') => $this->byMethod([
                'GET' => Http::response(['key' => 'p-1', 'value' => $this->scoreSheet()], 200),
                'DELETE' => Http::response(null, 204),
            ]),
            $this->items('/g-1/categories') => Http::response($this->assignedPlayers(['p-1' => 'Ada', 'p-2' => 'Ben']), 200),
            $this->items('/g-1/categories/ga-p-1') => Http::response(null, 204),
        ]);

        $this->signedIn()
            ->post('/game/g-1/player/p-1/delete')
            ->assertRedirect(route('home'));

        self::assertCount(1, $this->sent('DELETE', '/items/g-1/data/p-1'));
        self::assertCount(1, $this->sent('DELETE', '/items/g-1/categories/ga-p-1'));
        self::assertCount(0, $this->sent('DELETE', '/items/g-1/categories/ga-p-2'));

        $this->assertDatabaseMissing('share_token', ['token' => 'token-p-1']);
        $this->assertDatabaseHas('share_token', ['token' => 'token-p-2']);
    }

    public function test_a_player_without_a_score_sheet_is_still_removed(): void
    {
        $this->fakeApi([
            $this->items('/g-1/data/p-2') => Http::response(['message' => 'Not found'], 404),
            $this->items('/g-1/categories') => Http::response($this->assignedPlayers(['p-2' => 'Ben']), 200),
            $this->items('/g-1/categories/ga-p-2') => Http::response(null, 204),
        ]);

        $this->signedIn()->post('/game/g-1/player/p-2/delete')->assertRedirect(route('home'));

        self::assertCount(0, $this->sent('DELETE', '/data/'));
        self::assertCount(1, $this->sent('DELETE', '/items/g-1/categories/ga-p-2'));
    }

    public function test_a_failure_removing_a_player_is_a_server_error(): void
    {
        $this->fakeApi([
            $this->items('/g-1/data/p-1') => Http::response(['message' => 'Not found'], 404),
            $this->items('/g-1/categories') => Http::response($this->assignedPlayers(['p-1' => 'Ada']), 200),
            $this->items('/g-1/categories/ga-p-1') => Http::response(['message' => 'Locked'], 403),
        ]);

        $this->signedIn()->post('/game/g-1/player/p-1/delete')->assertStatus(500);
    }

    // Deleting a game

    public function test_a_game_is_deleted_with_its_score_sheets_players_and_share_tokens(): void
    {
        $token = new ShareToken();
        $token->token = 'token-1';
        $token->game_id = 'g-1';
        $token->player_id = 'p-1';
        $token->parameters = '{}';
        $token->save();

        $this->fakeApi([
            $this->items('/g-1?include-players=1') => Http::response($this->game('g-1', ['p-1' => 'Ada', 'p-2' => 'Ben']), 200),
            $this->items('/g-1/data') => Http::response($this->scoreSheets(['p-1' => $this->scoreSheet(), 'p-2' => $this->scoreSheet()]), 200),
            $this->items('/g-1/data/p-1') => Http::response(null, 204),
            $this->items('/g-1/data/p-2') => Http::response(null, 204),
            $this->items('/g-1/categories') => Http::response($this->assignedPlayers(['p-1' => 'Ada', 'p-2' => 'Ben']), 200),
            $this->items('/g-1/categories/ga-p-1') => Http::response(null, 204),
            $this->items('/g-1/categories/ga-p-2') => Http::response(null, 204),
            $this->items('/g-1') => Http::response(null, 204),
        ]);

        $this->signedIn()
            ->post('/game/g-1/delete')
            ->assertRedirect(route('home'));

        foreach (['/data/p-1', '/data/p-2', '/categories/ga-p-1', '/categories/ga-p-2', ''] as $suffix) {
            self::assertCount(1, $this->sentTo('DELETE', $this->items('/g-1'.$suffix)), "DELETE /items/g-1{$suffix}");
        }

        $this->assertDatabaseCount('share_token', 0);
    }

    public function test_deleting_a_game_that_cannot_be_found_is_a_404(): void
    {
        $this->fakeApi([$this->items('/g-1?include-players=1') => Http::response(['message' => 'Not found'], 404)]);

        $this->signedIn()->post('/game/g-1/delete')->assertNotFound();
    }

    public function test_a_failure_deleting_a_game_is_a_server_error_and_keeps_the_share_tokens(): void
    {
        $token = new ShareToken();
        $token->token = 'token-1';
        $token->game_id = 'g-1';
        $token->player_id = 'p-1';
        $token->parameters = '{}';
        $token->save();

        $this->fakeApi([
            $this->items('/g-1?include-players=1') => Http::response($this->game('g-1', ['p-1' => 'Ada']), 200),
            $this->items('/g-1/data') => Http::response([], 200),
            $this->items('/g-1/categories') => Http::response([], 200),
            $this->items('/g-1') => Http::response(['message' => 'Locked'], 403),
        ]);

        $this->signedIn()->post('/game/g-1/delete')->assertStatus(500);

        $this->assertDatabaseCount('share_token', 1);
    }

    // Completing a game

    private function fakeCompletingGame(array $scores, array $overrides = []): void
    {
        $players = [];
        $sheets = [];
        foreach ($scores as $player_id => [$name, $total]) {
            $players[$player_id] = $name;
            $sheet = $this->scoreSheet([], []);
            $sheet['score']['total'] = $total;
            $sheets[$player_id] = $sheet;
        }

        $this->fakeApi($overrides + [
            $this->items('/g-1?include-players=1') => Http::response($this->game('g-1', $players), 200),
            $this->items('/g-1/categories') => Http::response($this->assignedPlayers($players), 200),
            $this->items('/g-1/data') => Http::response($this->scoreSheets($sheets), 200),
            $this->items('/g-1') => Http::response(null, 204),
        ]);
    }

    public function test_completing_a_game_stores_the_scores_and_winner_and_removes_the_share_tokens(): void
    {
        foreach (['p-1', 'p-2'] as $player_id) {
            $token = new ShareToken();
            $token->token = 'token-'.$player_id;
            $token->game_id = 'g-1';
            $token->player_id = $player_id;
            $token->parameters = '{}';
            $token->save();
        }
        $other = new ShareToken();
        $other->token = 'token-other-game';
        $other->game_id = 'g-2';
        $other->player_id = 'p-1';
        $other->parameters = '{}';
        $other->save();

        $this->fakeCompletingGame(['p-1' => ['Ada', 140], 'p-2' => ['Ben', 212]]);

        $this->signedIn()
            ->post('/game/g-1/complete')
            ->assertRedirect(route('home'));

        $patches = $this->sent('PATCH', '/items/g-1');
        self::assertCount(1, $patches);

        $winner = ['player_id' => 'p-2', 'player_name' => 'Ben', 'score' => 212];
        $payload = $patches[0]->data();
        self::assertSame(1, $payload['complete']);
        self::assertSame('p-2', $payload['winner_id']);
        self::assertSame(212, $payload['score']);
        self::assertSame(
            [
                'scores' => [$winner, ['player_id' => 'p-1', 'player_name' => 'Ada', 'score' => 140]],
                'winner' => $winner,
            ],
            json_decode($payload['game'], true)
        );

        $this->assertDatabaseMissing('share_token', ['game_id' => 'g-1']);
        $this->assertDatabaseHas('share_token', ['token' => 'token-other-game']);
    }

    /**
     * 197 beats 181 beats 92, a comparator that only ever answers "after" or "equal" ranks
     * 181 first for this order of scores.
     */
    public function test_the_winner_is_the_highest_score_whatever_order_the_scores_arrive_in(): void
    {
        $this->fakeCompletingGame(['p-1' => ['Ada', 92], 'p-2' => ['Ben', 181], 'p-3' => ['Cleo', 197]]);

        $this->signedIn()->post('/game/g-1/complete')->assertRedirect(route('home'));

        $payload = $this->sent('PATCH', '/items/g-1')[0]->data();

        self::assertSame('p-3', $payload['winner_id']);
        self::assertSame(197, $payload['score']);
        self::assertSame(['p-3', 'p-2', 'p-1'], array_column(json_decode($payload['game'], true)['scores'], 'player_id'));
    }

    public function test_a_player_without_a_score_sheet_completes_the_game_on_zero(): void
    {
        $this->fakeCompletingGame(['p-1' => ['Ada', 120]], [
            $this->items('/g-1/categories') => Http::response($this->assignedPlayers(['p-1' => 'Ada', 'p-2' => 'Ben']), 200),
        ]);

        $this->signedIn()->post('/game/g-1/complete')->assertRedirect(route('home'));

        $scores = json_decode($this->sent('PATCH', '/items/g-1')[0]->data()['game'], true)['scores'];

        self::assertSame([['p-1', 120], ['p-2', 0]], array_map(fn (array $score) => [$score['player_id'], $score['score']], $scores));
    }

    public function test_a_failure_completing_a_game_is_a_server_error(): void
    {
        $this->fakeCompletingGame(['p-1' => ['Ada', 120]], [
            $this->items('/g-1') => Http::response(['message' => 'The API is down'], 503),
        ]);

        $this->signedIn()->post('/game/g-1/complete')->assertStatus(500);
    }

    public function test_complete_and_play_again_starts_a_new_game_with_the_same_players(): void
    {
        $this->fakeCompletingGame(['p-1' => ['Ada', 120], 'p-2' => ['Ben', 90]], [
            $this->items() => Http::response(['id' => 'g-new'], 201),
            $this->items('/g-new/categories') => fn (Request $request) => Http::response([
                'category' => ['id' => $request['category_id'], 'name' => self::PLAYER_NAMES[$request['category_id']]],
            ], 201),
        ]);

        $this->signedIn()
            ->post('/game/g-1/complete-and-play-again')
            ->assertRedirect(route('game.show', ['game_id' => 'g-new']));

        self::assertCount(1, $this->sent('PATCH', '/items/g-1'));
        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === 'http://api.test/v3/resource-types/rt-1/resources/r-1/items'
            && $request['name'] === 'Yatzy game');
        self::assertSame(['p-1', 'p-2'], array_map(fn (Request $request) => $request['category_id'], $this->sent('POST', '/items/g-new/categories')));
        $this->assertDatabaseHas('share_token', ['game_id' => 'g-new', 'player_id' => 'p-2']);
        $this->assertDatabaseMissing('share_token', ['game_id' => 'g-1']);
    }
}
