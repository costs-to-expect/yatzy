<?php
declare(strict_types=1);

namespace App\Actions\Game;

use App\Actions\Action;
use App\Api\Service;
use App\Models\ShareToken;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yatzy/blob/main/LICENSE
 */
class Complete extends Action
{
    public function __invoke(
        Service $api,
        string $resource_type_id,
        string $resource_id,
        string $game_id
    ): int
    {
        $game_response = $api->getGame(
            $resource_type_id,
            $resource_id,
            $game_id,
            ['include-players' => true]
        );
        if ($game_response['status'] !== 200) {
            abort(404, 'Unable to find the game');
        }

        $assigned_players_response = $api->getAssignedGamePlayers($resource_type_id, $resource_id, $game_id);
        if ($assigned_players_response['status'] !== 200) {
            abort(404, 'Unable to find the game players');
        }

        $scores = [];
        foreach ($assigned_players_response['content'] as $player) {
            $scores[$player['category']['id']] = [
                'player_id' => $player['category']['id'],
                'player_name' => $player['category']['name'],
                'score' => 0
            ];
        }

        $game_score_sheets_response = $api->getGameScoreSheets(
            $resource_type_id,
            $resource_id,
            $game_id
        );

        if ($game_score_sheets_response['status'] !== 200) {
            abort(404, 'Unable to fetch the game scores');
        }

        foreach ($game_score_sheets_response['content'] as $score_sheet) {
            $scores[$score_sheet['key']]['score'] = $score_sheet['value']['score']['total'];
        }

        usort($scores, static function($a, $b) {
            return $a['score'] < $b['score'] ? 1 : 0;
        });

        $winner = $scores[array_key_first($scores)];

        ShareToken::query()->where('game_id', $game_id)->delete();

        $update_game_response = $api->updateGame(
            $resource_type_id,
            $resource_id,
            $game_id,
            [
                'game' => json_encode(['scores' => $scores,'winner' => $winner], JSON_THROW_ON_ERROR),
                'winner_id' => $winner['player_id'],
                'score' => $winner['score'],
                'complete' => 1
            ]
        );

        if ($update_game_response['status'] === 204) {
            return 204;
        }

        return $update_game_response['status'];
    }
}
