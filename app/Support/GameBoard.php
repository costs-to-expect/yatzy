<?php

declare(strict_types=1);

namespace App\Support;

use App\View\Components\Avatar;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * What the home page, the games and the score sheet show about the players of a game: who is ahead, how far through
 * the game each of them is, the colour each of them keeps, and the words for when a game was played.
 */
final class GameBoard
{
    /**
     * The colour of each player, from their place in the players list so they keep it in every game and nothing is stored
     *
     * @param list<array{id: string, name: string}> $players
     * @return array<string, int> player id => index into Avatar::TONES
     */
    public static function tones(array $players): array
    {
        $tones = [];

        foreach (array_values($players) as $position => $player) {
            $tones[$player['id']] = $position % count(Avatar::TONES);
        }

        return $tones;
    }

    /**
     * Who is playing, best score first. Players with the same score keep the order they were added in.
     *
     * @param list<array{id: string, name: string}> $players the players of the game
     * @param array<string, int> $totals player id => total score
     * @param array<string, int> $turns player id => turns played
     * @param array<string, int> $tones
     * @return list<array{id: string, name: string, tone: int, score: int, turns: int, progress: float, leader: bool}>
     */
    public static function standings(array $players, array $totals, array $turns, array $tones, int $turns_in_game): array
    {
        $standings = [];

        foreach ($players as $player) {
            $played = $turns[$player['id']] ?? 0;

            $standings[] = [
                'id' => $player['id'],
                'name' => $player['name'],
                'tone' => $tones[$player['id']] ?? 0,
                'score' => $totals[$player['id']] ?? 0,
                'turns' => $played,
                'progress' => $turns_in_game > 0 ? (float) min(1.0, $played / $turns_in_game) : 0.0,
                'leader' => false,
            ];
        }

        usort($standings, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        // A crown is for being ahead: someone has scored and someone else has scored less
        if (count($standings) > 1) {
            $best = $standings[0]['score'];
            $worst = $standings[count($standings) - 1]['score'];

            if ($best > 0 && $best > $worst) {
                foreach ($standings as $position => $standing) {
                    $standings[$position]['leader'] = $standing['score'] === $best;
                }
            }
        }

        return $standings;
    }

    /**
     * Ada, Ben and Cleo
     *
     * @param list<string> $names
     */
    public static function names(array $names): string
    {
        if (count($names) <= 1) {
            return $names[0] ?? '';
        }

        return implode(', ', array_slice($names, 0, -1)) . ' and ' . $names[count($names) - 1];
    }

    /**
     * Ada, Ben & Cleo, short enough for a button
     *
     * @param list<string> $names
     */
    public static function ampersands(array $names): string
    {
        if (count($names) <= 1) {
            return $names[0] ?? '';
        }

        return implode(', ', array_slice($names, 0, -1)) . ' & ' . $names[count($names) - 1];
    }

    /**
     * When the API says a game was created, null when it does not say
     *
     * @param array<string, mixed> $game
     */
    public static function startedAt(array $game): ?CarbonImmutable
    {
        foreach (['created_at', 'created'] as $key) {
            if (isset($game[$key]) && is_string($game[$key]) && $game[$key] !== '') {
                try {
                    return CarbonImmutable::parse($game[$key]);
                } catch (\Throwable) {
                    return null;
                }
            }
        }

        return null;
    }

    /**
     * Today, Yesterday, Saturday or 12 Oct, null when there is no date to talk about
     */
    public static function when(?CarbonInterface $at, ?CarbonInterface $now = null): ?string
    {
        if ($at === null) {
            return null;
        }

        $now ??= CarbonImmutable::now();
        $days = (int) $at->startOfDay()->diffInDays($now->startOfDay(), true);

        return match (true) {
            $days === 0 => 'Today',
            $days === 1 => 'Yesterday',
            $days < 7 => $at->format('l'),
            $at->year === $now->year => $at->format('j M'),
            default => $at->format('j M Y'),
        };
    }

    /**
     * 40 min, how long ago a game started, null when there is no start to talk about
     */
    public static function since(?CarbonInterface $at, ?CarbonInterface $now = null): ?string
    {
        if ($at === null) {
            return null;
        }

        $now ??= CarbonImmutable::now();

        if ($at->greaterThan($now)) {
            return null;
        }

        return $at->diffForHumans($now, ['syntax' => CarbonInterface::DIFF_ABSOLUTE, 'parts' => 1, 'short' => true]);
    }
}
