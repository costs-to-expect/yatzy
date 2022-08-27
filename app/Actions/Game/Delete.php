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
class Delete extends Action
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

        try {

            // Delete the score sheets that exist
            // Possible there are not score sheets for some players
            $score_sheets_response = $api->getGameScoreSheets(
                $resource_type_id,
                $resource_id,
                $game_id
            );

            if ($score_sheets_response['status'] !== 200) {
                throw new \RuntimeException('Unable to fetch the score sheets for the game');
            }

            foreach ($score_sheets_response['content'] as $score_sheet) {
                $response = $api->deletePlayerScoreSheet(
                    $resource_type_id,
                    $resource_id,
                    $game_id,
                    $score_sheet['key']
                );

                if ($response['status'] !== 204) {
                    throw new \RuntimeException('Unable to delete score sheet for player with id ' . $score_sheet['key'] .
                        ', error ' . $response['content']);
                }
            }

            $assigned_players_response = $api->getAssignedGamePlayers(
                $resource_type_id,
                $resource_id,
                $game_id
            );

            if ($assigned_players_response['status'] !== 200) {
                throw new \RuntimeException('Unable to fetch the assigned game players');
            }

            foreach($assigned_players_response['content'] as $player) {

                $response = $api->deleteAssignedGamePlayer(
                    $resource_type_id,
                    $resource_id,
                    $game_id,
                    $player['id']
                );

                if ($response['status'] !== 204) {
                    throw new \RuntimeException('Unable to delete player with id ' . $player['id'] . ', error ' .
                        $response['content']);
                }
            }

            $response = $api->deleteGame(
                $resource_type_id,
                $resource_id,
                $game_id
            );

            if ($response['status'] !== 204) {
                throw new \RuntimeException('Unable to delete game with id ' . $game_id);
            }

            ShareToken::query()->where('game_id', $game_id)->delete();

        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }

        return 204;
    }
}
