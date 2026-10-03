<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ShareToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\FakesTheApi;
use Tests\TestCase;

/**
 * The server decides what can go on a score sheet. The browser can be wrong, out of date or not a browser at all (a
 * public link is open to anyone who holds it), so a score has to be a combination that exists, a score that combination
 * can produce, and an empty slot. Changing and clearing a score are off unless SCORE_CORRECTIONS is switched on.
 */
class ScoreValidationTest extends TestCase
{
    use FakesTheApi;
    use RefreshDatabase;

    private const SHEET = 'api.test/v3/resource-types/rt-1/resources/r-1/items/g-1/data/p-1';

    /**
     * @return array<string, array{bool}>
     */
    public static function modes(): array
    {
        return ['signed in' => [false], 'public share link' => [true]];
    }

    private function fakeScoring(array $sheet): void
    {
        $this->fakeApi([
            self::SHEET => $this->byMethod([
                'GET' => Http::response(['key' => 'p-1', 'value' => $sheet], 200),
                'PATCH' => Http::response(null, 204),
            ]),
            $this->items('/g-1/log') => Http::response(['id' => 'log-1'], 201),
        ]);
    }

    private function score(string $action, array $payload, bool $public): TestResponse
    {
        if ($public) {
            ShareToken::issue('rt-1', 'r-1', 'g-1', 'p-1', 'Ada', 'owner-bearer');
            $token = (string) ShareToken::query()->value('token');

            return $this->postJson("/public/score-sheet/{$token}/score-{$action}", $payload);
        }

        return $this->signedIn()->withCredentials()->postJson("/game/score-{$action}", $payload + ['game_id' => 'g-1', 'player_id' => 'p-1']);
    }

    private function saves(): int
    {
        return count($this->sentTo('PATCH', self::SHEET));
    }

    /**
     * @return array<string, array{string, array<string, mixed>}>
     */
    public static function impossibleScores(): array
    {
        return [
            'an upper combination that does not exist' => ['upper', ['dice' => 'sevens', 'score' => 7]],
            'no combination at all' => ['upper', ['score' => 3]],
            'a combination that is not text' => ['upper', ['dice' => ['ones'], 'score' => 3]],
            'a score that is not a multiple of the dice' => ['upper', ['dice' => 'threes', 'score' => 10]],
            'more than five of the dice' => ['upper', ['dice' => 'sixes', 'score' => 36]],
            'a negative score' => ['upper', ['dice' => 'ones', 'score' => -1]],
            'text for a score' => ['upper', ['dice' => 'ones', 'score' => 'lots']],
            'no score' => ['upper', ['dice' => 'ones']],
            'a lower combination that does not exist' => ['lower', ['combo' => 'full_hose', 'score' => 22]],
            'a bonus for a second Yatzy, there is none' => ['lower', ['combo' => 'yatzy_bonus_one', 'score' => 100]],
            'an upper combination in the lower section' => ['lower', ['combo' => 'ones', 'score' => 3]],
            'a pair that is not two of the same dice' => ['lower', ['combo' => 'one_pair', 'score' => 7]],
            'a pair above two sixes' => ['lower', ['combo' => 'one_pair', 'score' => 14]],
            'two pair below two pairs of ones and twos' => ['lower', ['combo' => 'two_pair', 'score' => 4]],
            'three of a kind that is not three of the same dice' => ['lower', ['combo' => 'three_of_a_kind', 'score' => 10]],
            'three of a kind above three sixes' => ['lower', ['combo' => 'three_of_a_kind', 'score' => 21]],
            'four of a kind that is not four of the same dice' => ['lower', ['combo' => 'four_of_a_kind', 'score' => 22]],
            'a full house no five dice can make' => ['lower', ['combo' => 'full_house', 'score' => 25]],
            'a full house below the lowest' => ['lower', ['combo' => 'full_house', 'score' => 5]],
            'a small straight worth the large straight' => ['lower', ['combo' => 'small_straight', 'score' => 20]],
            'a large straight worth what it was before' => ['lower', ['combo' => 'large_straight', 'score' => 30]],
            'a yatzy worth a hundred' => ['lower', ['combo' => 'yatzy', 'score' => 100]],
            'chance scratched' => ['lower', ['combo' => 'chance', 'score' => 0]],
            'chance above thirty' => ['lower', ['combo' => 'chance', 'score' => 31]],
            'chance below five' => ['lower', ['combo' => 'chance', 'score' => 4]],
        ];
    }

    #[DataProvider('impossibleScores')]
    public function test_a_score_that_is_not_possible_is_refused_and_nothing_is_saved(string $action, array $payload): void
    {
        $this->fakeScoring($this->scoreSheet(['ones' => 3]));

        $response = $this->score($action, $payload, false);

        $response->assertStatus(422)->assertJsonStructure(['message']);
        self::assertSame(0, $this->saves());
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/log'));
    }

    #[DataProvider('modes')]
    public function test_an_impossible_score_is_refused_through_both_entrances(bool $public): void
    {
        $this->fakeScoring($this->scoreSheet());

        $this->score('upper', ['dice' => 'sixes', 'score' => 37], $public)->assertStatus(422);
        $this->score('lower', ['combo' => 'large_straight', 'score' => 30], $public)->assertStatus(422);
        $this->score('lower', ['combo' => 'full_house', 'score' => 25], $public)->assertStatus(422);

        self::assertSame(0, $this->saves());
    }

    #[DataProvider('modes')]
    public function test_a_refused_score_comes_back_with_the_sheet_as_it_is(bool $public): void
    {
        $this->fakeScoring($this->scoreSheet(['ones' => 3]));

        $this->score('upper', ['dice' => 'sixes', 'score' => 37], $public)
            ->assertStatus(422)
            ->assertJsonPath('sheet.upper-section', ['ones' => 3]);
    }

    public function test_scores_may_be_sent_as_a_string_of_digits(): void
    {
        $this->fakeScoring($this->scoreSheet());

        $this->score('upper', ['dice' => 'fours', 'score' => '12'], false)->assertOk();

        self::assertSame(['fours' => 12], json_decode($this->sentTo('PATCH', self::SHEET)[0]['value'], true)['upper-section']);
    }

    #[DataProvider('modes')]
    public function test_a_combination_that_is_already_scored_is_not_overwritten(bool $public): void
    {
        $this->fakeScoring($this->scoreSheet(['ones' => 3], ['chance' => 20]));

        $this->score('upper', ['dice' => 'ones', 'score' => 1], $public)
            ->assertStatus(409)
            ->assertJsonPath('message', 'That combination has already been scored')
            ->assertJsonPath('sheet.upper-section.ones', 3);
        $this->score('lower', ['combo' => 'chance', 'score' => 12], $public)->assertStatus(409);

        self::assertSame(0, $this->saves());
    }

    #[DataProvider('modes')]
    public function test_the_same_score_again_is_a_success_that_saves_nothing(bool $public): void
    {
        // A retry of a save that reached us but whose answer got lost
        $this->fakeScoring($this->scoreSheet(['ones' => 3]));

        $this->score('upper', ['dice' => 'ones', 'score' => 3], $public)
            ->assertOk()
            ->assertJsonPath('score.total', 3)
            ->assertJsonPath('turns', 1);

        self::assertSame(0, $this->saves());
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/log'));
    }

    #[DataProvider('modes')]
    public function test_every_combination_takes_the_scores_its_dice_can_make(bool $public): void
    {
        $this->fakeScoring($this->scoreSheet());

        foreach ([['one_pair', 12], ['two_pair', 22], ['three_of_a_kind', 18], ['four_of_a_kind', 24], ['full_house', 28], ['chance', 30], ['small_straight', 15], ['large_straight', 20], ['yatzy', 50], ['one_pair', 0], ['full_house', 0]] as [$combo, $score]) {
            $this->score('lower', ['combo' => $combo, 'score' => $score], $public)->assertOk();
        }
    }

    public function test_game_and_player_ids_are_needed_to_score_when_signed_in(): void
    {
        $this->fakeScoring($this->scoreSheet());

        $this->signedIn()->withCredentials()->postJson('/game/score-upper', ['dice' => 'ones', 'score' => 2])
            ->assertStatus(422)
            ->assertJsonPath('message', 'The game and the player are needed to score');
        $this->signedIn()->withCredentials()->postJson('/game/score-upper', ['dice' => 'ones', 'score' => 2, 'game_id' => ['g-1'], 'player_id' => 'p-1'])
            ->assertStatus(422);

        self::assertSame(0, $this->saves());
    }

    // Corrections: change and clear a score

    #[DataProvider('modes')]
    public function test_changing_a_score_is_not_allowed_unless_corrections_are_switched_on(bool $public): void
    {
        config(['app.config.score_corrections' => false]);
        $this->fakeScoring($this->scoreSheet(['ones' => 3]));

        $this->score('upper', ['dice' => 'ones', 'score' => 2, 'replace' => true], $public)
            ->assertForbidden()
            ->assertJsonPath('message', 'Scores cannot be changed');
        $this->score('clear', ['section' => 'upper', 'combo' => 'ones'], $public)
            ->assertForbidden()
            ->assertJsonPath('message', 'Scores cannot be cleared');

        self::assertSame(0, $this->saves());
    }

    #[DataProvider('modes')]
    public function test_a_score_can_be_changed_when_corrections_are_on_and_the_change_is_asked_for(bool $public): void
    {
        config(['app.config.score_corrections' => true]);
        $this->fakeScoring($this->scoreSheet(['ones' => 3], ['chance' => 20]));

        $this->score('upper', ['dice' => 'ones', 'score' => 2, 'replace' => true], $public)
            ->assertOk()
            ->assertJsonPath('score', ['upper' => 2, 'bonus' => 0, 'lower' => 20, 'total' => 22])
            ->assertJsonPath('turns', 2);

        self::assertSame(['ones' => 2], json_decode($this->sentTo('PATCH', self::SHEET)[0]['value'], true)['upper-section']);

        $logged = $this->sentTo('POST', $this->items('/g-1/log'));
        self::assertSame('Changed their Ones from 3 to 2', $logged[0]['message']);
        self::assertSame(3, json_decode($logged[0]['parameters'], true)['previous']);
    }

    public function test_a_change_that_was_not_asked_for_is_still_a_conflict_with_corrections_on(): void
    {
        // Two devices scoring the same combination must not quietly overwrite each other
        config(['app.config.score_corrections' => true]);
        $this->fakeScoring($this->scoreSheet(['ones' => 3]));

        $this->score('upper', ['dice' => 'ones', 'score' => 2], false)->assertStatus(409);

        self::assertSame(0, $this->saves());
    }

    #[DataProvider('modes')]
    public function test_a_score_can_be_cleared_when_corrections_are_on(bool $public): void
    {
        config(['app.config.score_corrections' => true]);
        $this->fakeScoring($this->scoreSheet(['ones' => 3, 'twos' => 6], ['chance' => 20]));

        $this->score('clear', ['section' => 'upper', 'combo' => 'ones'], $public)
            ->assertOk()
            ->assertJsonPath('message', 'Score cleared')
            ->assertJsonPath('score', ['upper' => 6, 'bonus' => 0, 'lower' => 20, 'total' => 26])
            ->assertJsonPath('turns', 2)
            ->assertJsonPath('sheet.upper-section', ['twos' => 6]);

        self::assertSame(['twos' => 6], json_decode($this->sentTo('PATCH', self::SHEET)[0]['value'], true)['upper-section']);

        $logged = $this->sentTo('POST', $this->items('/g-1/log'));
        self::assertSame('Cleared their Ones', $logged[0]['message']);
    }

    public function test_a_score_that_was_never_scored_cannot_be_cleared(): void
    {
        config(['app.config.score_corrections' => true]);
        $this->fakeScoring($this->scoreSheet(['ones' => 3]));

        $this->score('clear', ['section' => 'upper', 'combo' => 'twos'], false)->assertStatus(422);
        $this->score('clear', ['section' => 'middle', 'combo' => 'ones'], false)->assertStatus(422);
        $this->score('clear', ['section' => 'lower', 'combo' => ['x']], false)->assertStatus(422);

        self::assertSame(0, $this->saves());
    }

    public function test_a_lower_score_can_be_cleared_when_corrections_are_on(): void
    {
        config(['app.config.score_corrections' => true]);
        $this->fakeScoring($this->scoreSheet([], ['yatzy' => 50, 'chance' => 20]));

        $this->score('clear', ['section' => 'lower', 'combo' => 'yatzy'], false)
            ->assertOk()
            ->assertJsonPath('score', ['upper' => 0, 'bonus' => 0, 'lower' => 20, 'total' => 20])
            ->assertJsonPath('sheet.lower-section', ['chance' => 20]);
    }

    public function test_a_share_link_clears_for_its_own_player_only(): void
    {
        config(['app.config.score_corrections' => true]);
        $this->fakeScoring($this->scoreSheet(['ones' => 3]));

        $this->score('clear', ['section' => 'upper', 'combo' => 'ones', 'game_id' => 'g-other', 'player_id' => 'p-other'], true)->assertOk();

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'g-other') || str_contains($request->url(), 'p-other'));
    }

    public function test_clearing_needs_a_signed_in_player_or_a_share_link(): void
    {
        $this->postJson('/game/score-clear', ['section' => 'upper', 'combo' => 'ones'])->assertUnauthorized();
        $this->postJson('/public/score-sheet/not-a-token/score-clear', ['section' => 'upper', 'combo' => 'ones'])->assertNotFound();
    }
}
