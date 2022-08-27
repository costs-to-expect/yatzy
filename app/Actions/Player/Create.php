<?php
declare(strict_types=1);

namespace App\Actions\Player;

use App\Actions\Action;
use App\Api\Service;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yatzy/blob/main/LICENSE
 */
class Create extends Action
{
    public function __invoke(Service $api, string $resource_type_id, array $input): int
    {
        $create_player_response = $api->createPlayer(
            $resource_type_id,
            $input['name'],
            $input['description']
        );

        if ($create_player_response['status'] === 201) {
            return $create_player_response['status'];
        }

        $this->message = $create_player_response['content'];

        if ($create_player_response['status'] === 422) {
            $this->validation_errors = $create_player_response['fields'];
            return $create_player_response['status'];
        }

        return $create_player_response['status'];
    }
}
