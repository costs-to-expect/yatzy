<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ShareToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\FakesTheApi;
use Tests\TestCase;

/**
 * Only one player in a game needs an account. The owner shares a public link for every other
 * player, the link is a token the app maps back to the game, the player and the owner's bearer
 * token, so anyone with the link can score for that player and nobody else.
 */
class PublicShareTest extends TestCase
{
    use FakesTheApi;
    use RefreshDatabase;

    private function shareToken(array $parameters = [], string $token = 'public-token'): void
    {
        $share = new ShareToken();
        $share->token = $token;
        $share->game_id = 'g-1';
        $share->player_id = 'p-1';
        $share->parameters = $parameters + [
            'resource_type_id' => 'rt-1',
            'resource_id' => 'r-1',
            'game_id' => 'g-1',
            'player_id' => 'p-1',
            'player_name' => 'Ada',
            'owner_bearer' => 'owner-bearer',
        ];
        $share->save();
    }

    private function fakeSharedGame(array $sheet, bool $complete = false, array $overrides = []): void
    {
        $this->fakeApi($overrides + [
            $this->items('/g-1') => Http::response($this->game('g-1', ['p-1' => 'Ada', 'p-2' => 'Ben'], $complete), 200),
            $this->items('/g-1/data/p-1') => Http::response(['key' => 'p-1', 'value' => $sheet], 200),
        ]);
    }

    public function test_the_public_score_sheet_is_the_score_sheet_of_the_player_the_link_was_made_for(): void
    {
        $this->shareToken();
        $this->fakeSharedGame($this->scoreSheet(['ones' => 3], ['full_house' => 25]));

        $response = $this->get('/public/score-sheet/public-token')
            ->assertOk()
            ->assertSee('Hey Ada, play Yatzy with us!')
            ->assertSee('Player: Ada');

        self::assertMatchesRegularExpression('/id="total"[^>]*>28</', $response->getContent());

        // The player is never told who the owner is: the page holds the link's addresses, not the owner's ids or bearer
        preg_match('#<script type="application/json" id="sheet-config">(.*?)</script>#s', $response->getContent(), $matches);
        $config = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(['id' => 'p-1', 'name' => 'Ada'], $config['player']);
        self::assertSame([], $config['ids']);
        self::assertSame(
            [
                'upper' => route('public.score-upper.action', ['token' => 'public-token']),
                'lower' => route('public.score-lower.action', ['token' => 'public-token']),
                'clear' => route('public.score-clear.action', ['token' => 'public-token']),
                'players' => route('public.player-scores', ['token' => 'public-token']),
            ],
            $config['urls']
        );
        self::assertStringNotContainsString('owner-bearer', $response->getContent());
        self::assertStringNotContainsString('rt-1', $response->getContent());
    }

    public function test_the_public_score_sheet_is_kept_out_of_search_engines_and_has_no_account_navigation(): void
    {
        $this->shareToken();
        $this->fakeSharedGame($this->scoreSheet());

        $this->get('/public/score-sheet/public-token')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,follow">', false)
            ->assertDontSee(route('home'), false)
            ->assertDontSee(route('sign-out'), false)
            ->assertDontSee(route('account'), false)
            ->assertDontSee('data-tab-bar', false)
            // The player can't finish the game, that is for the owner
            ->assertDontSee('id="complete"', false);
    }

    public function test_the_public_score_sheet_reads_the_game_as_its_owner(): void
    {
        $this->shareToken();
        $this->fakeSharedGame($this->scoreSheet());

        $this->get('/public/score-sheet/public-token')->assertOk();

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/items/g-1/data/p-1')
            && $request->header('Authorization') === ['Bearer owner-bearer']);
        Http::assertNotSent(fn (Request $request) => $request->header('Authorization') !== ['Bearer owner-bearer']);
    }

    public function test_a_link_without_a_player_name_calls_the_player_a_yatzy_player(): void
    {
        $share = new ShareToken();
        $share->token = 'public-token';
        $share->game_id = 'g-1';
        $share->player_id = 'p-1';
        $share->parameters = [
            'resource_type_id' => 'rt-1',
            'resource_id' => 'r-1',
            'game_id' => 'g-1',
            'player_id' => 'p-1',
            'owner_bearer' => 'owner-bearer',
        ];
        $share->save();
        $this->fakeSharedGame($this->scoreSheet());

        $this->get('/public/score-sheet/public-token')
            ->assertOk()
            ->assertSee('Hey Yatzy Player, play Yatzy with us!');
    }

    public function test_an_open_game_loads_the_scoring_scripts(): void
    {
        $this->shareToken();
        $this->fakeSharedGame($this->scoreSheet());

        $this->get('/public/score-sheet/public-token')
            ->assertOk()
            ->assertSee('js/ui.js', false)
            ->assertSee('js/score-sheet.js', false);
    }

    public function test_a_player_without_a_score_sheet_is_given_an_empty_one(): void
    {
        $this->shareToken();
        $this->fakeApi([
            $this->items('/g-1') => Http::response($this->game('g-1', ['p-1' => 'Ada']), 200),
            $this->items('/g-1/data/p-1') => Http::response(['message' => 'Not found'], 404),
            $this->items('/g-1/data') => Http::response(['id' => 'ds-1'], 201),
        ]);

        $this->get('/public/score-sheet/public-token')
            ->assertRedirect(route('public.score-sheet', ['token' => 'public-token']));

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && str_ends_with($request->url(), '/items/g-1/data')
            && $request['key'] === 'p-1'
            && $request->header('Authorization') === ['Bearer owner-bearer']);
    }

    public function test_a_game_that_no_longer_exists_is_a_404(): void
    {
        $this->shareToken();
        $this->fakeApi([$this->items('/g-1') => Http::response(['message' => 'Not found'], 404)]);

        $this->get('/public/score-sheet/public-token')->assertNotFound();
    }

    public function test_an_unknown_link_is_a_404_and_makes_no_api_requests(): void
    {
        $this->fakeApi();

        $this->get('/public/score-sheet/not-a-token')->assertNotFound();
        $this->get('/public/game/not-a-token/player-scores')->assertNotFound();
        $this->get('/public/game/not-a-token/bonus')->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_a_link_with_corrupt_parameters_is_a_server_error(): void
    {
        DB::table('share_token')->insert([
            'token' => 'public-token',
            'game_id' => 'g-1',
            'player_id' => 'p-1',
            'parameters' => '{not json',
        ]);

        $this->get('/public/score-sheet/public-token')->assertStatus(500);
    }

    public function test_the_public_player_scores_are_read_as_json_as_the_owner(): void
    {
        $this->shareToken();
        $this->fakeApi([
            $this->items('/g-1/categories') => Http::response($this->assignedPlayers(['p-1' => 'Ada', 'p-2' => 'Ben']), 200),
            $this->items('/g-1/data') => Http::response($this->scoreSheets([
                'p-1' => $this->scoreSheet(['ones' => 3], ['chance' => 20]),
            ]), 200),
        ]);

        $this->get('/public/game/public-token/player-scores')
            ->assertOk()
            ->assertExactJson(['players' => [
                ['id' => 'p-1', 'name' => 'Ada', 'upper' => 3, 'bonus' => 0, 'lower' => 20, 'total' => 23, 'turns' => 2],
                ['id' => 'p-2', 'name' => 'Ben', 'upper' => 0, 'bonus' => 0, 'lower' => 0, 'total' => 0, 'turns' => 0],
            ]]);

        Http::assertSent(fn (Request $request) => $request->header('Authorization') === ['Bearer owner-bearer']);
    }

    public function test_a_failure_reading_the_public_player_scores_is_a_404(): void
    {
        $this->shareToken();
        $this->fakeApi([$this->items('/g-1/categories') => Http::response(['message' => 'Not found'], 404)]);

        $this->get('/public/game/public-token/player-scores')->assertNotFound();
    }

    /**
     * The links work for the life of the game, completing the game removes them for good.
     */
    public function test_completing_the_game_removes_the_public_links(): void
    {
        $this->shareToken();
        $this->fakeApi([
            $this->items('/g-1?include-players=1') => Http::response($this->game('g-1', ['p-1' => 'Ada']), 200),
            $this->items('/g-1/categories') => Http::response($this->assignedPlayers(['p-1' => 'Ada']), 200),
            $this->items('/g-1/data') => Http::response($this->scoreSheets(['p-1' => $this->scoreSheet([], ['chance' => 20])]), 200),
            $this->items('/g-1') => $this->byMethod([
                'GET' => Http::response($this->game('g-1', ['p-1' => 'Ada']), 200),
                'PATCH' => Http::response(null, 204),
            ]),
            $this->items('/g-1/data/p-1') => Http::response(['key' => 'p-1', 'value' => $this->scoreSheet([], ['chance' => 20])], 200),
        ]);

        $this->get('/public/score-sheet/public-token')->assertOk();

        $this->signedIn()->post('/game/g-1/complete')->assertRedirect(route('home'));

        $this->get('/public/score-sheet/public-token')->assertNotFound();
    }
}
