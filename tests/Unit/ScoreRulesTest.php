<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\ScoreRules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ScoreRulesTest extends TestCase
{
    private function sheet(array $upper = [], array $lower = []): array
    {
        return ['upper-section' => $upper, 'lower-section' => $lower, 'score' => ScoreRules::totals(['upper-section' => $upper, 'lower-section' => $lower])];
    }

    public function test_the_totals_add_the_upper_bonus_from_sixty_three(): void
    {
        self::assertSame(['upper' => 62, 'bonus' => 0, 'lower' => 22, 'total' => 84], ScoreRules::totals($this->sheet(
            ['ones' => 2, 'twos' => 6, 'threes' => 9, 'fours' => 12, 'fives' => 15, 'sixes' => 18],
            ['full_house' => 22]
        )));
        self::assertSame(['upper' => 63, 'bonus' => 50, 'lower' => 0, 'total' => 113], ScoreRules::totals($this->sheet(
            ['ones' => 3, 'twos' => 6, 'threes' => 9, 'fours' => 12, 'fives' => 15, 'sixes' => 18]
        )));
    }

    public function test_the_totals_of_an_empty_sheet_are_zero(): void
    {
        self::assertSame(['upper' => 0, 'bonus' => 0, 'lower' => 0, 'total' => 0], ScoreRules::totals([]));
    }

    public function test_every_combination_on_the_sheet_is_a_turn(): void
    {
        self::assertSame(0, ScoreRules::turns([]));
        self::assertSame(3, ScoreRules::turns($this->sheet(['ones' => 3], ['yatzy' => 50, 'chance' => 0])));

        $finished = $this->sheet(
            ['ones' => 3, 'twos' => 6, 'threes' => 9, 'fours' => 12, 'fives' => 15, 'sixes' => 18],
            ['one_pair' => 10, 'two_pair' => 16, 'three_of_a_kind' => 15, 'four_of_a_kind' => 0, 'full_house' => 22, 'small_straight' => 15, 'large_straight' => 20, 'yatzy' => 50, 'chance' => 22]
        );
        self::assertSame(ScoreRules::TURNS, ScoreRules::turns($finished));
        self::assertSame(15, ScoreRules::TURNS);
    }

    /**
     * @return array<string, array{string, string, mixed, bool}>
     */
    public static function scores(): array
    {
        return [
            'no ones' => ['upper', 'ones', 0, true],
            'five sixes' => ['upper', 'sixes', 30, true],
            'six sixes' => ['upper', 'sixes', 36, false],
            'a score that is not a multiple' => ['upper', 'threes', 10, false],
            'an unknown dice' => ['upper', 'sevens', 7, false],
            'a string of digits' => ['upper', 'fours', '12', true],
            'a decimal' => ['upper', 'fours', 12.0, false],
            'text' => ['upper', 'fours', 'twelve', false],
            'null' => ['upper', 'fours', null, false],
            'negative' => ['upper', 'fours', -4, false],

            'one pair' => ['lower', 'one_pair', 8, true],
            'one pair of sixes' => ['lower', 'one_pair', 12, true],
            'one pair scratched' => ['lower', 'one_pair', 0, true],
            'one pair that is odd' => ['lower', 'one_pair', 7, false],
            'one pair above two sixes' => ['lower', 'one_pair', 14, false],
            'two pair' => ['lower', 'two_pair', 16, true],
            'two pair at the bottom, ones and twos' => ['lower', 'two_pair', 6, true],
            'two pair at the top, fives and sixes' => ['lower', 'two_pair', 22, true],
            'two pair below ones and twos' => ['lower', 'two_pair', 4, false],
            'two pair above fives and sixes' => ['lower', 'two_pair', 24, false],
            'two pair that is odd' => ['lower', 'two_pair', 15, false],
            'three of a kind' => ['lower', 'three_of_a_kind', 12, true],
            'three of a kind scratched' => ['lower', 'three_of_a_kind', 0, true],
            'three of a kind that is not three of a number' => ['lower', 'three_of_a_kind', 10, false],
            'three of a kind above three sixes' => ['lower', 'three_of_a_kind', 21, false],
            'four of a kind' => ['lower', 'four_of_a_kind', 20, true],
            'four of a kind that is not four of a number' => ['lower', 'four_of_a_kind', 22, false],
            'four of a kind above four sixes' => ['lower', 'four_of_a_kind', 28, false],
            'full house' => ['lower', 'full_house', 22, true],
            'full house at the bottom, three ones and two twos' => ['lower', 'full_house', 7, true],
            'full house at the top, three sixes and two fives' => ['lower', 'full_house', 28, true],
            'full house scratched' => ['lower', 'full_house', 0, true],
            'full house no five dice can make' => ['lower', 'full_house', 10, false],
            'another full house no five dice can make' => ['lower', 'full_house', 25, false],
            'full house below the lowest' => ['lower', 'full_house', 6, false],
            'full house above the highest' => ['lower', 'full_house', 29, false],
            'chance' => ['lower', 'chance', 5, true],
            'chance at the top' => ['lower', 'chance', 30, true],
            'chance cannot be scratched' => ['lower', 'chance', 0, false],
            'chance below five dice' => ['lower', 'chance', 4, false],
            'chance above five dice' => ['lower', 'chance', 31, false],
            'small straight' => ['lower', 'small_straight', 15, true],
            'small straight scratched' => ['lower', 'small_straight', 0, true],
            'small straight for the large straight' => ['lower', 'small_straight', 20, false],
            'large straight' => ['lower', 'large_straight', 20, true],
            'large straight scratched' => ['lower', 'large_straight', 0, true],
            'large straight for the small straight' => ['lower', 'large_straight', 15, false],
            'large straight for what it used to be worth' => ['lower', 'large_straight', 30, false],
            'yatzy' => ['lower', 'yatzy', 50, true],
            'yatzy scratched' => ['lower', 'yatzy', 0, true],
            'yatzy for a hundred' => ['lower', 'yatzy', 100, false],

            'a bonus for a second Yatzy, there is none' => ['lower', 'yatzy_bonus_one', 100, false],
            'the upper section is not a lower combination' => ['lower', 'ones', 3, false],
            'the lower section is not an upper combination' => ['upper', 'chance', 20, false],
            'an unknown section' => ['middle', 'ones', 3, false],
        ];
    }

    #[DataProvider('scores')]
    public function test_a_score_is_only_allowed_when_the_combination_can_produce_it(string $section, string $combination, mixed $score, bool $allowed): void
    {
        $problem = ScoreRules::problem($this->sheet(), $section, $combination, $score);

        self::assertSame($allowed, $problem === null, (string) $problem);
    }

    /**
     * Every roll of five dice, 6 x 6 x 6 x 6 x 6 of them, and what each combination could score from it. The scores the
     * rules allow have to be exactly the ones some roll can make, no more (a score no dice can make would be accepted)
     * and no fewer (a roll that could not be scored).
     */
    public function test_the_allowed_scores_are_exactly_what_the_dice_can_make(): void
    {
        $made = ['one_pair' => [], 'two_pair' => [], 'three_of_a_kind' => [], 'four_of_a_kind' => [], 'full_house' => [], 'chance' => []];

        for ($roll = 0; $roll < 6 ** 5; $roll++) {
            $dice = [];
            for ($die = 0, $rest = $roll; $die < 5; $die++, $rest = intdiv($rest, 6)) {
                $dice[] = $rest % 6 + 1;
            }

            $counts = array_count_values($dice);
            $pairs = array_keys(array_filter($counts, static fn (int $count): bool => $count >= 2));

            foreach ($pairs as $face) {
                $made['one_pair'][$face * 2] = true;
            }

            foreach ($pairs as $one) {
                foreach ($pairs as $other) {
                    if ($one < $other) {
                        $made['two_pair'][$one * 2 + $other * 2] = true;
                    }
                }
            }

            foreach ($counts as $face => $count) {
                if ($count >= 3) {
                    $made['three_of_a_kind'][$face * 3] = true;
                }
                if ($count >= 4) {
                    $made['four_of_a_kind'][$face * 4] = true;
                }
            }

            if (count($counts) === 2 && in_array(3, $counts, true)) {
                $made['full_house'][array_sum($dice)] = true;
            }

            $made['chance'][array_sum($dice)] = true;
        }

        foreach ($made as $combination => $totals) {
            $expected = array_keys($totals);
            sort($expected);

            $allowed = ScoreRules::allowed('lower', $combination);
            if ($combination !== 'chance') {
                // A combination that cannot be made is scratched for nothing
                self::assertSame(0, array_shift($allowed), "{$combination} can be scratched");
            }

            self::assertSame($expected, $allowed, "{$combination} allows what the dice can make");
        }
    }

    public function test_the_upper_combinations_take_none_to_five_of_the_dice(): void
    {
        self::assertSame([0, 4, 8, 12, 16, 20], ScoreRules::allowed('upper', 'fours'));
        self::assertSame([0, 1, 2, 3, 4, 5], ScoreRules::allowed('upper', 'ones'));
    }

    public function test_the_straights_and_the_yatzy_score_the_same_every_time(): void
    {
        self::assertSame([0, 15], ScoreRules::allowed('lower', 'small_straight'));
        self::assertSame([0, 20], ScoreRules::allowed('lower', 'large_straight'));
        self::assertSame([0, 50], ScoreRules::allowed('lower', 'yatzy'));
    }

    public function test_the_script_is_given_the_rules_of_the_lower_section(): void
    {
        $rules = ScoreRules::lowerRules();

        self::assertSame(['small_straight', 'large_straight', 'yatzy', 'one_pair', 'two_pair', 'three_of_a_kind', 'four_of_a_kind', 'full_house', 'chance'], array_keys($rules));
        self::assertSame(['points' => 20], $rules['large_straight']);
        self::assertSame(['points' => 50], $rules['yatzy']);
        self::assertSame(['allowed' => [3, 6, 9, 12, 15, 18]], $rules['three_of_a_kind']);
        // What can be scratched is the script's to offer, scratching is not one of the totals
        self::assertNotContains(0, $rules['one_pair']['allowed']);
        self::assertSame(range(5, 30), $rules['chance']['allowed']);
    }

    public function test_only_a_scored_combination_can_be_cleared(): void
    {
        $sheet = $this->sheet(['ones' => 0]);

        self::assertNull(ScoreRules::clearProblem($sheet, 'upper', 'ones'));
        self::assertSame('That combination has not been scored', ScoreRules::clearProblem($sheet, 'upper', 'twos'));
        self::assertSame('That is not a combination on the score sheet', ScoreRules::clearProblem($sheet, 'upper', 'chance'));
        self::assertNull(ScoreRules::clearProblem($this->sheet([], ['yatzy' => 50]), 'lower', 'yatzy'));
    }

    public function test_adding_and_removing_a_score_works_the_totals_out_again(): void
    {
        $sheet = ScoreRules::with($this->sheet(['ones' => 3]), 'lower', 'chance', 20);
        self::assertSame(['upper' => 3, 'bonus' => 0, 'lower' => 20, 'total' => 23], $sheet['score']);

        $sheet = ScoreRules::with($sheet, 'lower', 'chance', 11);
        self::assertSame(['chance' => 11], $sheet['lower-section']);
        self::assertSame(14, $sheet['score']['total']);

        $sheet = ScoreRules::without($sheet, 'upper', 'ones');
        self::assertSame([], $sheet['upper-section']);
        self::assertSame(11, $sheet['score']['total']);
    }

    public function test_every_combination_has_a_readable_label(): void
    {
        self::assertSame('Three of a kind', ScoreRules::label('three_of_a_kind'));
        self::assertSame('One pair', ScoreRules::label('one_pair'));
        self::assertSame('Ones', ScoreRules::label('ones'));
    }
}
