<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\GameBoard;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class GameBoardTest extends TestCase
{
    private function players(string ...$names): array
    {
        $players = [];
        foreach ($names as $position => $name) {
            $players[] = ['id' => 'p-'.($position + 1), 'name' => $name];
        }

        return $players;
    }

    public function test_a_player_keeps_the_colour_of_their_place_in_the_players_list_and_the_colours_start_again(): void
    {
        $tones = GameBoard::tones($this->players('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'));

        self::assertSame(['p-1' => 0, 'p-2' => 1, 'p-3' => 2, 'p-4' => 3, 'p-5' => 4, 'p-6' => 5, 'p-7' => 0, 'p-8' => 1], $tones);
        self::assertSame([], GameBoard::tones([]));
    }

    public function test_the_standings_are_best_score_first_and_a_tie_keeps_the_order_the_players_were_added_in(): void
    {
        $standings = GameBoard::standings(
            $this->players('Ada', 'Ben', 'Cleo'),
            ['p-1' => 50, 'p-2' => 80, 'p-3' => 50],
            ['p-1' => 4, 'p-2' => 5, 'p-3' => 3],
            ['p-1' => 0, 'p-2' => 1, 'p-3' => 2],
            15
        );

        self::assertSame(['Ben', 'Ada', 'Cleo'], array_column($standings, 'name'));
        self::assertSame([80, 50, 50], array_column($standings, 'score'));
        self::assertSame([5, 4, 3], array_column($standings, 'turns'));
        self::assertSame([1, 0, 2], array_column($standings, 'tone'));
        self::assertEqualsWithDelta(5 / 15, $standings[0]['progress'], 0.0001);
    }

    public function test_a_player_with_no_score_sheet_yet_is_on_zero_with_no_turns(): void
    {
        $standings = GameBoard::standings($this->players('Ada', 'Ben'), ['p-1' => 20], ['p-1' => 1], [], 15);

        self::assertSame(['id' => 'p-2', 'name' => 'Ben', 'tone' => 0, 'score' => 0, 'turns' => 0, 'progress' => 0.0, 'leader' => false], $standings[1]);
    }

    public function test_the_leader_is_crowned_only_when_someone_has_scored_and_someone_else_has_scored_less(): void
    {
        $leaders = fn (array $totals) => array_column(
            GameBoard::standings($this->players('Ada', 'Ben', 'Cleo'), $totals, [], [], 15),
            'leader'
        );

        self::assertSame([true, false, false], $leaders(['p-1' => 30, 'p-2' => 20, 'p-3' => 0]));
        // Nobody has scored yet
        self::assertSame([false, false, false], $leaders([]));
        // All level
        self::assertSame([false, false, false], $leaders(['p-1' => 20, 'p-2' => 20, 'p-3' => 20]));
        // Two share the lead, both are crowned
        self::assertSame([true, true, false], $leaders(['p-1' => 40, 'p-2' => 40, 'p-3' => 10]));
    }

    public function test_a_player_on_their_own_is_never_crowned(): void
    {
        $standings = GameBoard::standings($this->players('Ada'), ['p-1' => 100], ['p-1' => 5], [], 15);

        self::assertFalse($standings[0]['leader']);
    }

    public function test_the_progress_never_goes_past_a_full_ring_and_a_game_without_turns_has_none(): void
    {
        self::assertSame(1.0, GameBoard::standings($this->players('Ada'), [], ['p-1' => 20], [], 15)[0]['progress']);
        self::assertSame(0.0, GameBoard::standings($this->players('Ada'), [], ['p-1' => 5], [], 0)[0]['progress']);
    }

    public function test_names_are_listed_in_words_and_with_ampersands(): void
    {
        self::assertSame('', GameBoard::names([]));
        self::assertSame('Ada', GameBoard::names(['Ada']));
        self::assertSame('Ada and Ben', GameBoard::names(['Ada', 'Ben']));
        self::assertSame('Ada, Ben and Cleo', GameBoard::names(['Ada', 'Ben', 'Cleo']));

        self::assertSame('', GameBoard::ampersands([]));
        self::assertSame('Ada', GameBoard::ampersands(['Ada']));
        self::assertSame('Ada & Ben', GameBoard::ampersands(['Ada', 'Ben']));
        self::assertSame('Ada, Ben & Cleo', GameBoard::ampersands(['Ada', 'Ben', 'Cleo']));
    }

    public function test_the_start_of_a_game_is_read_from_created_at_or_created_and_is_null_when_the_api_says_nothing(): void
    {
        self::assertSame('2026-10-02 18:30:00', GameBoard::startedAt(['created_at' => '2026-10-02 18:30:00'])?->format('Y-m-d H:i:s'));
        self::assertSame('2026-10-02 18:30:00', GameBoard::startedAt(['created' => '2026-10-02 18:30:00'])?->format('Y-m-d H:i:s'));
        self::assertNull(GameBoard::startedAt([]));
        self::assertNull(GameBoard::startedAt(['created_at' => '']));
        self::assertNull(GameBoard::startedAt(['created_at' => 12345]));
    }

    public function test_a_start_the_api_gets_wrong_is_left_out_rather_than_breaking_the_page(): void
    {
        // PHP 8.2, the version the app runs on, only warns about a date it cannot read and Laravel turns every warning
        // into an exception, PHP 8.3 and later throw. Either way the answer is that there is no start to talk about.
        set_error_handler(static function (int $level, string $message): never {
            throw new \ErrorException($message, 0, $level);
        });

        try {
            self::assertNull(GameBoard::startedAt(['created_at' => 'not a date']));
        } finally {
            restore_error_handler();
        }
    }

    public function test_when_a_game_was_played_is_in_words(): void
    {
        $now = CarbonImmutable::parse('2026-10-03 21:00:00');

        self::assertNull(GameBoard::when(null, $now));
        self::assertSame('Today', GameBoard::when(CarbonImmutable::parse('2026-10-03 00:05:00'), $now));
        self::assertSame('Today', GameBoard::when(CarbonImmutable::parse('2026-10-03 20:59:00'), $now));
        self::assertSame('Yesterday', GameBoard::when(CarbonImmutable::parse('2026-10-02 23:59:00'), $now));
        self::assertSame('Wednesday', GameBoard::when(CarbonImmutable::parse('2026-09-30 19:00:00'), $now));
        self::assertSame('Sunday', GameBoard::when(CarbonImmutable::parse('2026-09-27 19:00:00'), $now));
        self::assertSame('26 Sep', GameBoard::when(CarbonImmutable::parse('2026-09-26 19:00:00'), $now));
        self::assertSame('3 Oct 2025', GameBoard::when(CarbonImmutable::parse('2025-10-03 19:00:00'), $now));
    }

    public function test_how_long_a_game_has_been_running_is_short_and_never_negative(): void
    {
        $now = CarbonImmutable::parse('2026-10-03 21:00:00');

        self::assertNull(GameBoard::since(null, $now));
        self::assertSame('40m', GameBoard::since($now->subMinutes(40), $now));
        self::assertSame('1h', GameBoard::since($now->subMinutes(75), $now));
        self::assertSame('2d', GameBoard::since($now->subDays(2), $now));
        // A clock that is a little out must not say "-3m"
        self::assertNull(GameBoard::since($now->addMinutes(3), $now));
    }
}
