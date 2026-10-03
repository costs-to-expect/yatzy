<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ShareToken;
use App\Notifications\ApiError;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\FakesTheApi;
use Tests\TestCase;

/**
 * Scoring posts a combination and a score, the app adds it to the player's stored score sheet
 * and works out the totals. A signed-in player scores on their own score sheet, everyone else
 * scores through the public share link the owner of the game sent them, the two are separate
 * controllers with the same rules so every test runs against both.
 */
class ScoringTest extends TestCase
{
    use FakesTheApi;
    use RefreshDatabase;

    private const SHEET = 'api.test/v3/resource-types/rt-1/resources/r-1/items/g-1/data/p-1';

    /**
     * @return array<string, array{bool}>
     */
    public static function modes(): array
    {
        return [
            'signed in' => [false],
            'public share link' => [true],
        ];
    }

    private function fakeScoring(array $sheet, array $overrides = []): void
    {
        $this->fakeApi($overrides + [
            self::SHEET => $this->byMethod([
                'GET' => Http::response(['key' => 'p-1', 'value' => $sheet], 200),
                'PATCH' => Http::response(null, 204),
            ]),
            $this->items('/g-1/log') => Http::response(['id' => 'log-1'], 201),
        ]);
    }

    private function score(string $section, array $payload, bool $public): TestResponse
    {
        if ($public) {
            $share = new ShareToken();
            $share->token = 'public-token';
            $share->game_id = 'g-1';
            $share->player_id = 'p-1';
            $share->parameters = [
                'resource_type_id' => 'rt-1',
                'resource_id' => 'r-1',
                'game_id' => 'g-1',
                'player_id' => 'p-1',
                'player_name' => 'Ada',
                'owner_bearer' => 'owner-bearer',
            ];
            $share->save();

            return $this->postJson("/public/score-sheet/public-token/score-{$section}", $payload);
        }

        // The browser sends its cookies with the script's JSON request, a test request has to ask to.
        return $this->signedIn()->withCredentials()->postJson("/game/score-{$section}", $payload + ['game_id' => 'g-1', 'player_id' => 'p-1']);
    }

    /**
     * @return array<string, mixed> the score sheet saved by the last PATCH
     */
    private function savedSheet(): array
    {
        $saved = $this->sentTo('PATCH', self::SHEET);
        self::assertCount(1, $saved, 'the score sheet should be saved exactly once');

        return json_decode($saved[0]['value'], true);
    }

    #[DataProvider('modes')]
    public function test_scoring_the_upper_section_stores_the_score_and_works_out_the_totals(bool $public): void
    {
        $this->fakeScoring($this->scoreSheet(['ones' => 3], ['chance' => 20]));

        $this->score('upper', ['dice' => 'threes', 'score' => 9], $public)
            ->assertOk()
            ->assertExactJson([
                'message' => 'Score updated',
                'score' => ['upper' => 12, 'bonus' => 0, 'lower' => 20, 'total' => 32],
                'turns' => 3,
                // The sheet as it is now, the browser carries on from it
                'sheet' => [
                    'upper-section' => ['ones' => 3, 'threes' => 9],
                    'lower-section' => ['chance' => 20],
                    'score' => ['upper' => 12, 'bonus' => 0, 'lower' => 20, 'total' => 32],
                ],
            ]);

        self::assertSame(
            [
                'upper-section' => ['ones' => 3, 'threes' => 9],
                'lower-section' => ['chance' => 20],
                'score' => ['upper' => 12, 'bonus' => 0, 'lower' => 20, 'total' => 32],
            ],
            $this->savedSheet()
        );
    }

    #[DataProvider('modes')]
    public function test_a_scratched_combination_scores_zero_and_still_takes_a_turn(bool $public): void
    {
        $this->fakeScoring($this->scoreSheet(['ones' => 3]));

        $this->score('upper', ['dice' => 'sixes', 'score' => 0], $public)
            ->assertOk()
            ->assertJsonPath('score.upper', 3)
            ->assertJsonPath('turns', 2);

        self::assertSame(['ones' => 3, 'sixes' => 0], $this->savedSheet()['upper-section']);
    }

    #[DataProvider('modes')]
    public function test_the_upper_bonus_is_fifty_points_from_a_score_of_sixty_three(bool $public): void
    {
        $this->fakeScoring($this->scoreSheet(['ones' => 3, 'twos' => 6, 'threes' => 9, 'fours' => 12, 'fives' => 15], ['chance' => 10]));

        $this->score('upper', ['dice' => 'sixes', 'score' => 18], $public)
            ->assertOk()
            ->assertJsonPath('score', ['upper' => 63, 'bonus' => 50, 'lower' => 10, 'total' => 123]);

        self::assertSame(50, $this->savedSheet()['score']['bonus']);
    }

    #[DataProvider('modes')]
    public function test_a_score_of_sixty_two_is_one_short_of_the_upper_bonus(bool $public): void
    {
        $this->fakeScoring($this->scoreSheet(['ones' => 2, 'twos' => 6, 'threes' => 9, 'fours' => 12, 'fives' => 15]));

        $this->score('upper', ['dice' => 'sixes', 'score' => 18], $public)
            ->assertOk()
            ->assertJsonPath('score', ['upper' => 62, 'bonus' => 0, 'lower' => 0, 'total' => 62]);
    }

    #[DataProvider('modes')]
    public function test_the_lower_section_is_added_to_the_upper_score_and_bonus(bool $public): void
    {
        $this->fakeScoring($this->scoreSheet(['ones' => 3, 'twos' => 6, 'threes' => 9, 'fours' => 12, 'fives' => 15, 'sixes' => 18], ['chance' => 10]));

        $this->score('lower', ['combo' => 'full_house', 'score' => 22], $public)
            ->assertOk()
            ->assertJson([
                'message' => 'Score updated',
                'score' => ['upper' => 63, 'bonus' => 50, 'lower' => 32, 'total' => 145],
                'turns' => 8,
            ])
            ->assertJsonPath('sheet.lower-section', ['chance' => 10, 'full_house' => 22]);

        self::assertSame(['chance' => 10, 'full_house' => 22], $this->savedSheet()['lower-section']);
    }

    #[DataProvider('modes')]
    public function test_a_yatzy_is_worth_fifty_and_is_a_turn_like_any_other(bool $public): void
    {
        $this->fakeScoring($this->scoreSheet(['ones' => 3]));

        $this->score('lower', ['combo' => 'yatzy', 'score' => 50], $public)
            ->assertOk()
            ->assertJsonPath('score.lower', 50)
            ->assertJsonPath('score.total', 53)
            ->assertJsonPath('turns', 2);

        self::assertSame(['yatzy' => 50], $this->savedSheet()['lower-section']);
    }

    #[DataProvider('modes')]
    public function test_the_turn_count_reaches_fifteen_when_the_last_combination_is_scored(bool $public): void
    {
        $this->fakeScoring($this->scoreSheet(
            ['ones' => 3, 'twos' => 6, 'threes' => 9, 'fours' => 12, 'fives' => 15, 'sixes' => 18],
            ['one_pair' => 10, 'two_pair' => 16, 'three_of_a_kind' => 15, 'four_of_a_kind' => 0, 'full_house' => 22, 'small_straight' => 15, 'large_straight' => 20, 'yatzy' => 50]
        ));

        $this->score('lower', ['combo' => 'chance', 'score' => 22], $public)
            ->assertOk()
            ->assertJsonPath('turns', 15);
    }

    public static function logMessages(): array
    {
        return [
            'upper section' => ['upper', ['dice' => 'threes', 'score' => 9], 'Scored 9 in their Threes', ['section' => 'upper', 'dice' => 'threes', 'score' => 9]],
            'one pair' => ['lower', ['combo' => 'one_pair', 'score' => 8], 'Scored 8 in One pair', ['section' => 'lower', 'combo' => 'one_pair', 'score' => 8]],
            'two pair' => ['lower', ['combo' => 'two_pair', 'score' => 18], 'Scored 18 in Two pair', ['section' => 'lower', 'combo' => 'two_pair', 'score' => 18]],
            'three of a kind' => ['lower', ['combo' => 'three_of_a_kind', 'score' => 12], 'Scored 12 in Three of a kind', ['section' => 'lower', 'combo' => 'three_of_a_kind', 'score' => 12]],
            'four of a kind' => ['lower', ['combo' => 'four_of_a_kind', 'score' => 16], 'Scored 16 in Four of a kind', ['section' => 'lower', 'combo' => 'four_of_a_kind', 'score' => 16]],
            'full house' => ['lower', ['combo' => 'full_house', 'score' => 22], 'Scored 22 in Full house', ['section' => 'lower', 'combo' => 'full_house', 'score' => 22]],
            'chance' => ['lower', ['combo' => 'chance', 'score' => 21], 'Scored 21 in Chance', ['section' => 'lower', 'combo' => 'chance', 'score' => 21]],
            'small straight' => ['lower', ['combo' => 'small_straight', 'score' => 15], 'Scored their Small straight, scoring 15', ['section' => 'lower', 'combo' => 'small_straight', 'score' => 15]],
            'large straight' => ['lower', ['combo' => 'large_straight', 'score' => 20], 'Scored their Large straight, scoring 20', ['section' => 'lower', 'combo' => 'large_straight', 'score' => 20]],
            'yatzy' => ['lower', ['combo' => 'yatzy', 'score' => 50], 'Scored their Yatzy, scoring 50', ['section' => 'lower', 'combo' => 'yatzy', 'score' => 50]],
        ];
    }

    #[DataProvider('logMessages')]
    public function test_every_score_is_logged_against_the_game(string $section, array $payload, string $message, array $parameters): void
    {
        $this->fakeScoring($this->scoreSheet());

        $this->score($section, $payload, false)->assertOk();

        $logged = $this->sentTo('POST', $this->items('/g-1/log'));
        self::assertCount(1, $logged);
        self::assertSame($message, $logged[0]['message']);
        self::assertSame(['player' => 'p-1'] + $parameters, json_decode($logged[0]['parameters'], true));
    }

    #[DataProvider('modes')]
    public function test_a_score_is_logged_for_the_game_and_player_of_the_share_link(bool $public): void
    {
        $this->fakeScoring($this->scoreSheet());

        $this->score('upper', ['dice' => 'ones', 'score' => 2, 'game_id' => 'g-1', 'player_id' => 'p-1'], $public)->assertOk();

        $logged = $this->sentTo('POST', $this->items('/g-1/log'));
        self::assertCount(1, $logged);
        self::assertSame('p-1', json_decode($logged[0]['parameters'], true)['player']);
    }

    #[DataProvider('modes')]
    public function test_a_failure_logging_the_score_emails_the_error_address_and_the_score_is_still_saved(bool $public): void
    {
        Notification::fake();
        $this->fakeScoring($this->scoreSheet(), [$this->items('/g-1/log') => Http::response(['message' => 'The log is down'], 503)]);

        $this->score('upper', ['dice' => 'ones', 'score' => 2], $public)
            ->assertOk()
            ->assertJsonPath('message', 'Score updated');

        self::assertSame(['ones' => 2], $this->savedSheet()['upper-section']);

        Notification::assertSentOnDemand(
            ApiError::class,
            fn (ApiError $notification, array $channels, object $notifiable) => $notifiable->routes['mail'] === 'errors@yatzy.test'
                && $notification->toMail($notifiable)->introLines[2] === 'Message: The log is down'
        );
    }

    #[DataProvider('modes')]
    public function test_a_score_sheet_that_cannot_be_read_is_reported_to_the_browser_and_nothing_is_saved(bool $public): void
    {
        $this->fakeScoring($this->scoreSheet(), [
            self::SHEET => $this->byMethod(['GET' => Http::response(['message' => 'Not found'], 404)]),
        ]);

        $this->score('upper', ['dice' => 'ones', 'score' => 2], $public)
            ->assertNotFound()
            ->assertExactJson(['message' => 'Unable to fetch your score sheet']);

        self::assertCount(0, $this->sentTo('PATCH', self::SHEET));
    }

    #[DataProvider('modes')]
    public function test_a_lower_section_score_sheet_that_cannot_be_read_is_reported_to_the_browser(bool $public): void
    {
        $this->fakeScoring($this->scoreSheet(), [
            self::SHEET => $this->byMethod(['GET' => Http::response(['message' => 'The API is down'], 503)]),
        ]);

        $this->score('lower', ['combo' => 'chance', 'score' => 20], $public)
            ->assertStatus(503)
            ->assertExactJson(['message' => 'Unable to fetch your score sheet']);
    }

    #[DataProvider('modes')]
    public function test_a_score_sheet_that_cannot_be_saved_is_reported_to_the_browser(bool $public): void
    {
        $this->fakeScoring($this->scoreSheet(), [
            self::SHEET => $this->byMethod([
                'GET' => Http::response(['key' => 'p-1', 'value' => $this->scoreSheet()], 200),
                'PATCH' => Http::response(['message' => 'The API is down'], 503),
            ]),
        ]);

        $this->score('upper', ['dice' => 'ones', 'score' => 2], $public)
            ->assertStatus(503)
            ->assertExactJson(['message' => 'Failed to update your score sheet']);
    }

    #[DataProvider('modes')]
    public function test_the_api_is_called_as_the_signed_in_player_or_as_the_owner_of_the_share_link(bool $public): void
    {
        $this->fakeScoring($this->scoreSheet());

        $this->score('upper', ['dice' => 'ones', 'score' => 2], $public)->assertOk();

        $expected = $public ? 'owner-bearer' : self::BEARER;

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/items/g-1/data/p-1') && $request->header('Authorization') === ['Bearer '.$expected]);
        Http::assertNotSent(fn (Request $request) => $request->header('Authorization') === ['Bearer '.($public ? self::BEARER : 'owner-bearer')]);
    }

    public function test_a_share_link_can_only_score_for_its_own_game_and_player(): void
    {
        $this->fakeScoring($this->scoreSheet());

        $this->score('upper', ['dice' => 'ones', 'score' => 2, 'game_id' => 'g-other', 'player_id' => 'p-other'], true)->assertOk();

        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'g-other') || str_contains($request->url(), 'p-other'));
        self::assertSame(['ones' => 2], $this->savedSheet()['upper-section']);
    }

    public function test_an_unknown_share_link_cannot_score(): void
    {
        $this->fakeScoring($this->scoreSheet());

        $this->postJson('/public/score-sheet/not-a-token/score-upper', ['dice' => 'ones', 'score' => 2])->assertNotFound();
        $this->postJson('/public/score-sheet/not-a-token/score-lower', ['combo' => 'chance', 'score' => 20])->assertNotFound();

        Http::assertNothingSent();
    }
}
