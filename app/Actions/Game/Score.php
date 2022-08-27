<?php
declare(strict_types=1);

namespace App\Actions\Game;

use App\Actions\Action;
use App\Api\Service;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yatzy/blob/main/LICENSE
 */
class Score extends Action
{
    public function __invoke(
        Service $api,
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        string $player_id,
        array $score_sheet
    ): int
    {
        $update_score_sheet_response = $api->updateScoreSheetForPlayer(
            $resource_type_id,
            $resource_id,
            $game_id,
            $player_id,
            $score_sheet
        );

        if ($update_score_sheet_response['status'] === 204) {
            return 204;
        }

        return $update_score_sheet_response['status'];
    }
}
