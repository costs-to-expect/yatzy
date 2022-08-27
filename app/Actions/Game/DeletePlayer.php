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
class DeletePlayer extends Action
{
    public function __invoke(
        Service $api,
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        string $player_id
    ): int
    {
        // Delete the score sheet if it exists for the player
        $score_sheet_response = $api->getPlayerScoreSheet(
            $resource_type_id,
            $resource_id,
            $game_id,
            $player_id
        );

        if ($score_sheet_response['status'] === 200) {

            $response = $api->deletePlayerScoreSheet(
                $resource_type_id,
                $resource_id,
                $game_id,
                $score_sheet_response['content']['key']
            );

            if ($response['status'] !== 204) {
                throw new \RuntimeException('Unable to delete score sheet for player with id ' .
                    $score_sheet_response['content']['key'] . ', error ' . $response['content']);
            }
        }

        // Delete the player from the assignments
        // We need all the players to work out which id to delete
        $assigned_players_response = $api->getAssignedGamePlayers(
            $resource_type_id,
            $resource_id,
            $game_id
        );

        if ($assigned_players_response['status'] !== 200) {
            throw new \RuntimeException('Unable to fetch the assigned game players');
        }

        foreach($assigned_players_response['content'] as $player) {

            if ($player['category']['id'] === $player_id) {
                $response = $api->deleteAssignedGamePlayer(
                    $resource_type_id,
                    $resource_id,
                    $game_id,
                    $player['id']
                );

                if ($response['status'] !== 204) {
                    throw new \RuntimeException(
                        'Unable to delete player with id ' . $player['id'] . ', error ' .
                        $response['content']
                    );
                }

                break;
            }
        }

        ShareToken::query()
            ->where('game_id', $game_id)
            ->where('player_id', $player_id)
            ->delete();

        return 204;
    }
}
