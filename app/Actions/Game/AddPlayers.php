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
class AddPlayers extends Action
{
    public function __invoke(
        Service $api,
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        array $input
    ): int
    {
        if (array_key_exists('players', $input) === false) {
            $this->message = 'Missing players';
            $this->validation_errors['players'] = ['errors' => ['Please select the additional players']];
            return 422;
        }

        foreach ($input['players'] as $player) {
            $response = $api->addPlayerToGame(
                $resource_type_id,
                $resource_id,
                $game_id,
                $player
            );

            if ($response['status'] === 201) {

                $config = Config::get('app.config');

                try {
                    $token = new ShareToken();
                    $token->token = Str::uuid();
                    $token->game_id = $game_id;
                    $token->player_id = $player;
                    $token->parameters = json_encode([
                        'resource_type_id' => $resource_type_id,
                        'resource_id' => $resource_id,
                        'game_id' => $game_id,
                        'player_id' => $player,
                        'player_name' => $response['content']['category']['name'],
                        'owner_bearer' => request()->cookie($config['cookie_bearer'])
                    ], JSON_THROW_ON_ERROR);
                    $token->save();

                } catch (\JsonException $e) {
                    abort(500, 'Failed to create share token for player, create token manually');
                }

                continue;
            }

            $this->message = $response['content'];

            if ($response['status'] === 422) {
                $this->validation_errors = $response['fields'];
                $this->message = $response['content'];
            }

            return $response['status'];
        }

        return 201;
    }
}
