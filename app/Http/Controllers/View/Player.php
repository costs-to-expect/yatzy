<?php
declare(strict_types=1);

namespace App\Http\Controllers\View;

use App\Actions\Player\Create;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yatzy/blob/main/LICENSE
 */
class Player extends Controller
{
    public function index(Request $request)
    {
        $this->bootstrap($request);

        $players_response = $this->api->getPlayers($this->resource_type_id, ['collection' => true]);

        $players = [];
        if ($players_response['status'] === 200 && count($players_response['content']) > 0) {
            foreach ($players_response['content'] as $player) {
                $players[] = [
                    'id' => $player['id'],
                    'name' => $player['name']
                ];
            }
        }

        return view(
            'players',
            [
                'players' => $players,
            ]
        );
    }

    public function newPlayer(Request $request)
    {
        $this->bootstrap($request);

        return view(
            'new-player',
            [
                'resource_type_id' => $this->resource_type_id,
                'resource_id' => $this->resource_id,

                'errors' => session()->get('validation.errors')
            ]
        );
    }

    public function newPlayerProcess(Request $request)
    {
        $this->bootstrap($request);

        $action = new Create();
        $result = $action($this->api, $this->resource_type_id, $request->only(['name', 'description']));

        if ($result === 201) {
            return redirect()->route('players');
        }

        if ($result === 422) {
            return redirect()->route('player.create.view')
                ->withInput()
                ->with('validation.errors',$action->getValidationErrors());
        }

        abort($result, $action->getMessage());
    }
}
