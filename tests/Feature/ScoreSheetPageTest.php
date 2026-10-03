<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\ScoreRules;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\FakesTheApi;
use Tests\TestCase;

class ScoreSheetPageTest extends TestCase
{
    use FakesTheApi;

    private function fakeSheet(array $sheet, bool $complete = false, array $overrides = []): void
    {
        $this->fakeApi($overrides + [
            $this->items('/g-1?include-players=1') => Http::response($this->game('g-1', ['p-1' => 'Ada', 'p-2' => 'Ben'], $complete), 200),
            $this->items('/g-1/data/p-1') => Http::response(['key' => 'p-1', 'value' => $sheet], 200),
        ]);
    }

    /**
     * The settings the score sheet script reads, written into the page as JSON
     *
     * @return array<string, mixed>
     */
    private function sheetConfig(string $html): array
    {
        self::assertSame(1, preg_match('#<script type="application/json" id="sheet-config">(.*?)</script>#s', $html, $matches), 'the page carries its settings');

        return json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_the_score_sheet_belongs_to_the_player_and_game(): void
    {
        $this->fakeSheet($this->scoreSheet());

        $response = $this->signedIn()->get('/game/g-1/player/p-1/score-sheet')
            ->assertOk()
            ->assertSee('Player: Ada')
            ->assertSee('<title>Yatzy Game Scorer: Ada</title>', false);

        $config = $this->sheetConfig($response->getContent());

        self::assertSame(['id' => 'p-1', 'name' => 'Ada'], $config['player']);
        // Sent with every score, the server uses these, never the share link's
        self::assertSame(['game_id' => 'g-1', 'player_id' => 'p-1'], $config['ids']);
        self::assertSame(
            [
                'upper' => route('game.score-upper.action'),
                'lower' => route('game.score-lower.action'),
                'clear' => route('game.score-clear.action'),
                'players' => route('game.player-scores', ['game_id' => 'g-1']),
                'back' => route('home'),
                'complete' => route('game.complete.action', ['game_id' => 'g-1']),
            ],
            $config['urls']
        );
        self::assertSame(15, $config['turns']);
    }

    public function test_the_page_gives_the_script_the_rules_of_the_lower_section(): void
    {
        $this->fakeSheet($this->scoreSheet());

        $config = $this->sheetConfig($this->signedIn()->get('/game/g-1/player/p-1/score-sheet')->assertOk()->getContent());

        // What the script offers is what the server accepts, one copy of the rules
        self::assertSame(ScoreRules::lowerRules(), $config['rules']);
        self::assertSame(['points' => 20], $config['rules']['large_straight']);
        self::assertSame([3, 6, 9, 12, 15, 18], $config['rules']['three_of_a_kind']['allowed']);
    }

    public function test_the_page_carries_the_stored_score_sheet_for_the_script_to_draw(): void
    {
        $sheet = $this->scoreSheet(['ones' => 3, 'twos' => 0], ['three_of_a_kind' => 12, 'yatzy' => 50]);
        $this->fakeSheet($sheet);

        $config = $this->sheetConfig($this->signedIn()->get('/game/g-1/player/p-1/score-sheet')->assertOk()->getContent());

        self::assertSame($sheet, $config['sheet']);
        self::assertFalse($config['complete']);
    }

    public function test_every_player_has_the_colour_of_their_place_in_the_players_list(): void
    {
        $this->fakeSheet($this->scoreSheet());

        $config = $this->sheetConfig($this->signedIn()->get('/game/g-1/player/p-1/score-sheet')->assertOk()->getContent());

        self::assertSame(['p-1' => 0, 'p-2' => 1], $config['tones']);
    }

    public function test_the_totals_are_those_of_the_stored_score_sheet(): void
    {
        $sheet = $this->scoreSheet(
            ['ones' => 3, 'twos' => 6, 'threes' => 9, 'fours' => 12, 'fives' => 15, 'sixes' => 18],
            ['full_house' => 22]
        );

        $this->fakeSheet($sheet);

        $html = $this->signedIn()->get('/game/g-1/player/p-1/score-sheet')->assertOk()->getContent();

        self::assertMatchesRegularExpression('/id="upper"[^>]*>63</', $html);
        self::assertMatchesRegularExpression('/id="bonus"[^>]*>50</', $html);
        self::assertMatchesRegularExpression('/id="lower"[^>]*>22</', $html);
        self::assertMatchesRegularExpression('/id="total"[^>]*>135</', $html);
        self::assertStringContainsString('&middot; 7 of 15 turns', $html);
    }

    public function test_corrections_are_only_offered_when_they_are_switched_on(): void
    {
        $this->fakeSheet($this->scoreSheet());

        config(['app.config.score_corrections' => false]);
        $off = $this->signedIn()->get('/game/g-1/player/p-1/score-sheet')->assertOk();
        self::assertFalse($this->sheetConfig($off->getContent())['corrections']);
        $off->assertDontSee('Undo');

        config(['app.config.score_corrections' => true]);
        $on = $this->signedIn()->get('/game/g-1/player/p-1/score-sheet')->assertOk();
        self::assertTrue($this->sheetConfig($on->getContent())['corrections']);
        $on->assertSee('Undo');
    }

    public function test_the_scoring_scripts_are_loaded_for_an_open_game(): void
    {
        $this->fakeSheet($this->scoreSheet(), complete: false);

        $this->signedIn()->get('/game/g-1/player/p-1/score-sheet')
            ->assertSee('js/ui.js', false)
            ->assertSee('js/score-sheet.js', false)
            ->assertSee('id="complete"', false)
            ->assertSee(route('game.complete.action', ['game_id' => 'g-1']), false)
            ->assertDontSee('This game is finished');
    }

    public function test_a_complete_game_is_a_read_only_score_sheet(): void
    {
        $this->fakeSheet($this->scoreSheet(['ones' => 3]), complete: true);

        $response = $this->signedIn()->get('/game/g-1/player/p-1/score-sheet')
            ->assertOk()
            ->assertSee('This game is finished');

        // The script still draws the sheet, it does not let anyone score on it
        self::assertTrue($this->sheetConfig($response->getContent())['complete']);
        $response->assertSee('js/score-sheet.js', false);
    }

    public function test_a_player_without_a_score_sheet_is_given_an_empty_one(): void
    {
        $this->fakeApi([
            $this->items('/g-1?include-players=1') => Http::response($this->game('g-1', ['p-1' => 'Ada']), 200),
            $this->items('/g-1/data/p-1') => Http::response(['message' => 'Not found'], 404),
            $this->items('/g-1/data') => Http::response(['id' => 'ds-1'], 201),
        ]);

        $this->signedIn()->get('/game/g-1/player/p-1/score-sheet')
            ->assertRedirect(route('game.score-sheet', ['game_id' => 'g-1', 'player_id' => 'p-1']));

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === 'http://api.test/v3/resource-types/rt-1/resources/r-1/items/g-1/data'
            && $request['key'] === 'p-1'
            && json_decode($request['value'], true) === $this->scoreSheet());
    }

    public function test_a_failure_creating_the_score_sheet_is_passed_on(): void
    {
        $this->fakeApi([
            $this->items('/g-1?include-players=1') => Http::response($this->game('g-1', ['p-1' => 'Ada']), 200),
            $this->items('/g-1/data/p-1') => Http::response(['message' => 'Not found'], 404),
            $this->items('/g-1/data') => Http::response(['message' => 'The API is down'], 503),
        ]);

        $this->signedIn()->get('/game/g-1/player/p-1/score-sheet')->assertStatus(503);
    }

    public function test_a_game_that_cannot_be_found_is_passed_on(): void
    {
        $this->fakeApi([$this->items('/g-404?include-players=1') => Http::response(['message' => 'Not found'], 404)]);

        $this->signedIn()->get('/game/g-404/player/p-1/score-sheet')->assertNotFound();
    }

    public function test_a_failure_reading_the_score_sheet_is_passed_on(): void
    {
        $this->fakeApi([
            $this->items('/g-1?include-players=1') => Http::response($this->game('g-1', ['p-1' => 'Ada']), 200),
            $this->items('/g-1/data/p-1') => Http::response(['message' => 'The API is down'], 503),
        ]);

        $this->signedIn()->get('/game/g-1/player/p-1/score-sheet')->assertStatus(503);
    }
}
