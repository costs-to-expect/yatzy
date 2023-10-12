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
class Start extends Action
{
    private array $players = [];

    public function __invoke(Service $api, string $resource_type_id, string $resource_id, array $input): int
    {
        if (array_key_exists('players', $input) === false || $input['players'] === null || $input['players'] === '') {
            $this->message = 'Missing players';
            $this->validation_errors['players'] = ['errors' => ['Please enter the player names, one per line']];
            return 422;
        }

        $players = explode(PHP_EOL, $input['players']);

        if ($players === []) {
            $this->message = 'Missing players';
            $this->validation_errors['players'] = ['errors' => ['Please enter the player names, one per line']];
            return 422;
        }

        foreach ($players as $player) {
            $result = $this->createPlayer($api, $resource_type_id, $player);
            if ($result === false && $this->validation_errors !== []) {
                $this->message = 'Failed to create player';
                $this->validation_errors['players'] = ['errors' => ['Failed to create player named "' . $player . '", is the a name taken by another player?']];
                return 422;
            }
        }

        $create_game_response = $api->createGame(
            $resource_type_id,
            $resource_id,
            'Yatzy game',
            'Yatzy game create via the Yatzy app'
        );

        if ($create_game_response['status'] === 201) {

            $config = Config::get('app.config');

            $this->game_id = $create_game_response['content']['id'];

            foreach ($this->players as $player_id) {
                $response = $api->addPlayerToGame(
                    $resource_type_id,
                    $resource_id,
                    $this->game_id,
                    $player_id
                );

                try {
                    $token = new ShareToken();
                    $token->token = Str::uuid();
                    $token->game_id = $this->game_id;
                    $token->player_id = $player_id;
                    $token->parameters = json_encode([
                        'resource_type_id' => $resource_type_id,
                        'resource_id' => $resource_id,
                        'game_id' => $this->game_id,
                        'player_id' => $player_id,
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

    protected function createPlayer($api, $resource_type_id, $player): bool
    {
        $create_player_response = $api->createPlayer(
            $resource_type_id,
            $player,
            'New player - Added via the Yatzy App - Start'
        );

        if ($create_player_response['status'] === 422) {
            $this->validation_errors = $create_player_response['fields'];
            return false;
        }

        if ($create_player_response['status'] === 201) {
            $this->players[] = $create_player_response['content']['id'];
            return true;
        }

        return false;
    }
}
