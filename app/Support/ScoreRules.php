<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The rules of a Yatzy score sheet, in one place: which combinations there are, what each one can score, the upper
 * bonus and how the totals are worked out.
 *
 * A score sheet is the array the API stores for a player, `upper-section` and `lower-section` map a combination to the
 * points scored (a scratched combination is 0) and `score` holds the totals.
 *
 * What a combination can score comes from the dice, five of them, so a score that no roll can make is refused: one
 * pair is two dice the same, two pair is four dice, three and four of a kind are the matching dice, a full house and
 * Chance are all five dice.
 */
final class ScoreRules
{
    /** @var array<string, int> The upper combinations and the points one die of that number is worth */
    public const UPPER = ['ones' => 1, 'twos' => 2, 'threes' => 3, 'fours' => 4, 'fives' => 5, 'sixes' => 6];

    /** @var array<string, int> The lower combinations that always score the same, 1-2-3-4-5, 2-3-4-5-6 and five of a kind */
    public const FIXED = ['small_straight' => 15, 'large_straight' => 20, 'yatzy' => 50];

    /** @var list<string> The lower combinations that score what was rolled (Chance cannot be scratched) */
    public const SUMS = ['one_pair', 'two_pair', 'three_of_a_kind', 'four_of_a_kind', 'full_house', 'chance'];

    /** @var array<string, int> How many dice have to match for the combinations scored from the matching dice */
    private const MATCHING = ['one_pair' => 2, 'three_of_a_kind' => 3, 'four_of_a_kind' => 4];

    public const TURNS = 15;

    public const UPPER_BONUS_FROM = 63;

    public const UPPER_BONUS = 50;

    /** The lowest and highest total of five dice, what Chance can score */
    public const MIN_CHANCE = 5;

    public const MAX_CHANCE = 30;

    public const UPPER_SECTION = 'upper';

    public const LOWER_SECTION = 'lower';

    /**
     * The turns a player has played, every combination on the sheet is a turn
     */
    public static function turns(array $sheet): int
    {
        return count($sheet['upper-section'] ?? []) + count($sheet['lower-section'] ?? []);
    }

    /**
     * @return array{upper: int, bonus: int, lower: int, total: int}
     */
    public static function totals(array $sheet): array
    {
        $upper = (int) array_sum($sheet['upper-section'] ?? []);
        $bonus = $upper >= self::UPPER_BONUS_FROM ? self::UPPER_BONUS : 0;
        $lower = (int) array_sum($sheet['lower-section'] ?? []);

        return ['upper' => $upper, 'bonus' => $bonus, 'lower' => $lower, 'total' => $upper + $bonus + $lower];
    }

    public static function isCombination(string $section, string $combination): bool
    {
        return match ($section) {
            self::UPPER_SECTION => array_key_exists($combination, self::UPPER),
            self::LOWER_SECTION => array_key_exists($combination, self::FIXED) || in_array($combination, self::SUMS, true),
            default => false,
        };
    }

    /**
     * The scores a combination can take
     *
     * @return list<int>
     */
    public static function allowed(string $section, string $combination): array
    {
        if ($section === self::UPPER_SECTION) {
            return array_map(static fn (int $count): int => $count * self::UPPER[$combination], range(0, 5));
        }

        if (array_key_exists($combination, self::FIXED)) {
            return [0, self::FIXED[$combination]];
        }

        // What can be rolled, every sum can be scratched except Chance
        $rolled = self::rolled($combination);

        return $combination === 'chance' ? $rolled : [0, ...$rolled];
    }

    /**
     * What the score sheet script needs to know about the lower combinations, the points of one that always scores
     * the same and the totals the others can make. The script draws its rows from this, so the browser and the server
     * can never disagree about a score.
     *
     * @return array<string, array{points: int}|array{allowed: list<int>}>
     */
    public static function lowerRules(): array
    {
        $rules = [];

        foreach (self::FIXED as $combination => $points) {
            $rules[$combination] = ['points' => $points];
        }

        foreach (self::SUMS as $combination) {
            $rules[$combination] = ['allowed' => self::rolled($combination)];
        }

        return $rules;
    }

    /**
     * Why a score cannot go on the sheet, null when it can
     *
     * @param array $sheet the sheet as it is now, no Yatzy rule depends on what is already scored but a sibling game's
     *                     might, so every game's rules are asked the same way
     * @param mixed $score as sent by the browser, the JSON number or a string of digits
     */
    public static function problem(array $sheet, string $section, string $combination, mixed $score): ?string
    {
        if (self::isCombination($section, $combination) === false) {
            return 'That is not a combination on the score sheet';
        }

        $points = self::integer($score);
        if ($points === null) {
            return 'The score has to be a whole number';
        }

        if (in_array($points, self::allowed($section, $combination), true) === false) {
            return 'That score is not possible for ' . self::label($combination);
        }

        return null;
    }

    /**
     * Why a score cannot be taken off the sheet, null when it can
     */
    public static function clearProblem(array $sheet, string $section, string $combination): ?string
    {
        if (self::isCombination($section, $combination) === false) {
            return 'That is not a combination on the score sheet';
        }

        if (self::value($sheet, $section, $combination) === null) {
            return 'That combination has not been scored';
        }

        return null;
    }

    public static function value(array $sheet, string $section, string $combination): ?int
    {
        return $sheet[$section . '-section'][$combination] ?? null;
    }

    /**
     * The sheet with a score added, or replaced, and the totals worked out again
     */
    public static function with(array $sheet, string $section, string $combination, int $points): array
    {
        $sheet['upper-section'] ??= [];
        $sheet['lower-section'] ??= [];
        $sheet[$section . '-section'][$combination] = $points;

        $sheet['score'] = self::totals($sheet);

        return $sheet;
    }

    /**
     * The sheet with a score taken off and the totals worked out again
     */
    public static function without(array $sheet, string $section, string $combination): array
    {
        $sheet['upper-section'] ??= [];
        $sheet['lower-section'] ??= [];
        unset($sheet[$section . '-section'][$combination]);

        $sheet['score'] = self::totals($sheet);

        return $sheet;
    }

    public static function integer(mixed $score): ?int
    {
        if (is_int($score)) {
            return $score;
        }

        if (is_string($score) && preg_match('/^\d{1,3}$/', $score) === 1) {
            return (int) $score;
        }

        return null;
    }

    /**
     * One pair, the readable name of a combination
     */
    public static function label(string $combination): string
    {
        return ucfirst(str_replace('_', ' ', $combination));
    }

    /**
     * The totals a roll of five dice can make for a combination that scores what was rolled, lowest first
     *
     * @return list<int>
     */
    private static function rolled(string $combination): array
    {
        $faces = range(1, 6);
        $totals = [];

        if (array_key_exists($combination, self::MATCHING)) {
            foreach ($faces as $face) {
                $totals[] = self::MATCHING[$combination] * $face;
            }
        } elseif ($combination === 'two_pair') {
            // Two different pairs, the four dice are added up
            foreach ($faces as $face) {
                foreach ($faces as $other) {
                    if ($other > $face) {
                        $totals[] = 2 * $face + 2 * $other;
                    }
                }
            }
        } elseif ($combination === 'full_house') {
            // Three of one number and two of another, all five dice are added up
            foreach ($faces as $three) {
                foreach ($faces as $two) {
                    if ($two !== $three) {
                        $totals[] = 3 * $three + 2 * $two;
                    }
                }
            }
        } else {
            $totals = range(self::MIN_CHANCE, self::MAX_CHANCE);
        }

        $totals = array_values(array_unique($totals));
        sort($totals);

        return $totals;
    }
}
