<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Builders for the data shapes the Costs to Expect API returns for Yatzy, shared by the
 * tests that fake the API. The score sheet builder applies the same rules as the app: the
 * upper section scores a 35 point bonus from 63, the total is upper + bonus + lower.
 */
trait BuildsApiFixtures
{
    /**
     * @param array<string, int> $upper e.g. ['ones' => 3, 'twos' => 0]
     * @param array<string, int> $lower e.g. ['full_house' => 25]
     */
    protected function scoreSheet(array $upper = [], array $lower = []): array
    {
        $upper_total = array_sum($upper);
        $bonus = $upper_total >= 63 ? 35 : 0;
        $lower_total = array_sum($lower);

        return [
            'upper-section' => $upper,
            'lower-section' => $lower,
            'score' => [
                'upper' => $upper_total,
                'bonus' => $bonus,
                'lower' => $lower_total,
                'total' => $upper_total + $bonus + $lower_total,
            ],
        ];
    }

    /**
     * A game as returned when requesting items with include-players.
     *
     * @param array<string, string> $players player id => name
     * @param list<array{player_id: string, player_name: string, score: int}> $scores only for a complete game
     */
    protected function game(string $id, array $players, bool $complete = false, array $scores = []): array
    {
        $collection = [];
        foreach ($players as $player_id => $name) {
            $collection[] = ['id' => $player_id, 'name' => $name];
        }

        $game = [
            'id' => $id,
            'name' => 'Yatzy game',
            'description' => 'Yatzy game create via the Yatzy app',
            'complete' => $complete ? 1 : 0,
            'players' => ['collection' => $collection],
        ];

        if ($complete) {
            $game['game'] = ['scores' => $scores, 'winner' => $scores[0] ?? null];
        }

        return $game;
    }

    /**
     * The players assigned to a game, as returned by the game's categories collection. Each
     * assignment has its own id (ga-p-1 for player p-1), it is the assignment that is deleted
     * to remove a player from a game.
     *
     * @param array<string, string> $players player id => name
     */
    protected function assignedPlayers(array $players): array
    {
        $assigned = [];
        foreach ($players as $player_id => $name) {
            $assigned[] = ['id' => 'ga-'.$player_id, 'category' => ['id' => $player_id, 'name' => $name]];
        }

        return $assigned;
    }

    /**
     * The players collection, as returned by the resource type's categories.
     *
     * @param array<string, string> $players player id => name
     */
    protected function playerCollection(array $players): array
    {
        $collection = [];
        foreach ($players as $player_id => $name) {
            $collection[] = ['id' => $player_id, 'name' => $name];
        }

        return $collection;
    }

    /**
     * A game's score sheets, one per player.
     *
     * @param array<string, array> $sheets player id => score sheet
     */
    protected function scoreSheets(array $sheets): array
    {
        $collection = [];
        foreach ($sheets as $player_id => $sheet) {
            $collection[] = ['key' => $player_id, 'value' => $sheet];
        }

        return $collection;
    }
}
