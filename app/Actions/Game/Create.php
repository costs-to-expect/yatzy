<?php
declare(strict_types=1);

namespace App\Actions\Game;

use App\Actions\Action;
use App\Api\Service;
use App\Models\ShareToken;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yatzy/blob/main/LICENSE
 */
class Create extends Action
{
    public function __invoke(Service $api, string $resource_type_id, string $resource_id, array $input): int
    {
        if (array_key_exists('players', $input) === false) {
            $this->message = 'Missing players';
            $this->validation_errors['players'] = ['errors' => ['Please select your players']];
            return 422;
        }

        $create_game_response = $api->createGame(
            $resource_type_id,
            $resource_id,
            $input['name'],
            $input['description']
        );

        if ($create_game_response['status'] === 201) {

            $config = Config::get('app.config');

            $this->game_id = $create_game_response['content']['id'];

            foreach ($input['players'] as $player) {
                $response = $api->addPlayerToGame(
                    $resource_type_id,
                    $resource_id,
                    $this->game_id,
                    $player
                );

                try {
                    $token = new ShareToken();
                    $token->token = Str::uuid();
                    $token->game_id = $this->game_id;
                    $token->player_id = $player;
                    $token->parameters = json_encode([
                        'resource_type_id' => $resource_type_id,
                        'resource_id' => $resource_id,
                        'game_id' => $this->game_id,
                        'player_id' => $player,
                        'player_name' => $response['content']['category']['name'],
                        'owner_bearer' => request()->cookie($config['cookie_bearer'])
                    ], JSON_THROW_ON_ERROR);
                    $token->save();
                } catch (\Exception) {
                    abort(500, 'Failed to create share token for player, create token manually');
                }
            }

            return 201;
        }

        $this->message = $create_game_response['content'];

        if ($create_game_response['status'] === 422) {
            $this->validation_errors = $create_game_response['fields'];
            return $create_game_response['status'];
        }

        return $create_game_response['status'];
    }
}
