<?php
declare(strict_types=1);

namespace App\Http\Controllers\View;

use App\Http\Controllers\Controller;
use App\Models\ShareToken;
use Illuminate\Http\Request;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yatzy/blob/main/LICENSE
 */
class Index extends Controller
{
    public function home(Request $request)
    {
        $this->bootstrap($request);

        $open_games_response = $this->api->getGames(
            $this->resource_type_id,
            $this->resource_id,
            ['complete' => 0, 'include-players' => 1]
        );

        $open_games = [];
        if ($open_games_response['status'] === 200 && count($open_games_response['content']) > 0) {
            $open_games = $open_games_response['content'];
        }

        $closed_games_response = $this->api->getGames(
            $this->resource_type_id,
            $this->resource_id,
            ['complete' => 1, 'limit' => 5, 'include-players' => 1]
        );

        $closed_games = [];
        if ($closed_games_response['status'] === 200 && count($closed_games_response['content']) > 0) {
            $closed_games = $closed_games_response['content'];
        }

        $players_response = $this->api->getPlayers($this->resource_type_id, ['collection'=> true]);

        $players = [];
        if ($players_response['status'] === 200 && count($players_response['content']) > 0) {
            foreach ($players_response['content'] as $player) {
                $players[] = [
                    'id' => $player['id'],
                    'name' => $player['name']
                ];
            }
        }

        $game_scores = [];
        foreach ($open_games as $game) {
            $game_score_sheets_response = $this->api->getGameScoreSheets(
                $this->resource_type_id,
                $this->resource_id,
                $game['id']
            );

            if ($game_score_sheets_response['status'] === 200) {
                foreach ($game_score_sheets_response['content'] as $score_sheet) {
                    $game_scores[$game['id']][$score_sheet['key']] = $score_sheet['value']['score']['total'];
                }
            }
        }

        return view(
            'home',
            [
                'resource_type_id' => $this->resource_type_id,
                'resource_id' => $this->resource_id,

                'open_games' => $open_games,
                'closed_games' => $closed_games,
                'players' => $players,

                'share_tokens' => (new ShareToken())->getShareTokens(),

                'game_scores' => $game_scores
            ]
        );
    }

    public function landing()
    {
        return view('landing');
    }
}
