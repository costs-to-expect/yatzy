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
class Log extends Action
{
    public function __invoke(
        Service $api,
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        string $message,
        array $parameters = []
    ): int
    {
        $create_message_response = $api->createGameLogMessage(
            $resource_type_id,
            $resource_id,
            $game_id,
            $message,
            $parameters
        );

        if ($create_message_response['status'] === 201) {
            return 201;
        }

        $this->message = $create_message_response['content'];

        return $create_message_response['status'];
    }
}
