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
class Game extends Controller
{
    public function index(Request $request)
    {
        $this->bootstrap($request);

        $offset = (int) $request->query('offset', 0);
        $limit = (int) $request->query('limit', 10);

        $games_response = $this->api->getGames(
            $this->resource_type_id,
            $this->resource_id,
            [
                'complete' => 1,
                'include-players' => 1,
                'offset' => $offset,
                'limit' => $limit
            ]
        );

        $pagination = [
            'previous' => ($games_response['headers']['X-Link-Previous'][0] !== ''),
            'next' => ($games_response['headers']['X-Link-Next'][0] !== ''),
            'offset' => $games_response['headers']['X-Offset'][0],
            'limit' => $games_response['headers']['X-Limit'][0],
            'total' => $games_response['headers']['X-Total-Count'][0],
        ];

        $games = [];
        if ($games_response['status'] === 200 && count($games_response['content']) > 0) {
            $games = $games_response['content'];
        }

        return view(
            'games',
            [
                'pagination' => $pagination,
                'games' => $games,
            ]
        );
    }

    public function show(Request $request, $game_id)
    {
        $this->bootstrap($request);

        $game = $this->api->getGame(
            $this->resource_type_id,
            $this->resource_id,
            $game_id,
            ['include-players' => 1]
        );

        if ($game['status'] !== 200) {
            abort(404, 'Unable to find the game');
        }

        $game_scores = [];
        $game_score_sheets_response = $this->api->getGameScoreSheets(
            $this->resource_type_id,
            $this->resource_id,
            $game['content']['id']
        );

        if ($game_score_sheets_response['status'] === 200) {
            foreach ($game_score_sheets_response['content'] as $score_sheet) {
                $game_scores[$game['content']['id']][$score_sheet['key']] = $score_sheet['value']['score']['total'];
            }
        }

        return view(
            'game',
            [
                'game' => $game['content'],
                'game_scores' => $game_scores,
                'share_tokens' => (new ShareToken())->getShareTokens(),
            ]
        );

    }

    public function newGame(Request $request)
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
            'new-game',
            [
                'resource_type_id' => $this->resource_type_id,
                'resource_id' => $this->resource_id,

                'players' => $players,

                'errors' => session()->get('validation.errors')
            ]
        );
    }

    public function addPlayersToGame(Request $request, string $game_id)
    {
        $this->bootstrap($request);

        $game_response = $this->api->getGame(
            $this->resource_type_id,
            $this->resource_id,
            $game_id
        );

        if ($game_response['status'] !== 200) {
            abort($game_response['status'], $game_response['content']);
        }

        $players = [];
        $game_players = [];


        $all_players_response = $this->api->getPlayers($this->resource_type_id, ['collection' => true]);

        $all_players = [];
        if ($all_players_response['status'] === 200 && count($all_players_response['content']) > 0) {
            foreach ($all_players_response['content'] as $player) {
                $all_players[] = [
                    'id' => $player['id'],
                    'name' => $player['name']
                ];
            }
        }

        $game_players_response = $this->api->getAssignedGamePlayers(
            $this->resource_type_id,
            $this->resource_id,
            $game_id
        );

        $assigned_game_players = [];
        if ($game_players_response['status'] === 200 && count($game_players_response['content']) > 0) {
            foreach ($game_players_response['content'] as $player) {
                $assigned_game_players[] = $player['category']['id'];
            }
        }

        foreach ($all_players as $player) {
            if (!in_array($player['id'], $assigned_game_players, true)) {
                $players[] = $player;
            } else {
                $game_players[] = $player['name'];
            }
        }

        return view(
            'add-players-to-game',
            [
                'resource_type_id' => $this->resource_type_id,
                'resource_id' => $this->resource_id,
                'game_id' => $game_id,

                'players' => $players,
                'game_players' => $game_players,

                'errors' => session()->get('validation.errors')
            ]
        );
    }

    public function playerBonus(Request $request, string $game_id, string $player_id)
    {
        $this->bootstrap($request);

        $game_response = $this->api->getGame(
            $this->resource_type_id,
            $this->resource_id,
            $game_id
        );

        if ($game_response['status'] !== 200) {
            abort(404, 'Game not found');
        }

        $player_score_sheet_response = $this->api->getPlayerScoreSheet(
            $this->resource_type_id,
            $this->resource_id,
            $game_id,
            $player_id
        );

        if ($player_score_sheet_response['status'] !== 200) {
            abort(404, 'Player score sheet not found');
        }

        $upper_section = $player_score_sheet_response['content']['value']['upper-section'];

        return $this->playerBonusMessage($game_id, $player_id, $upper_section);
    }

    public function playerScores(Request $request, string $game_id)
    {
        $this->bootstrap($request);

        $players_response = $this->api->getAssignedGamePlayers(
            $this->resource_type_id,
            $this->resource_id,
            $game_id
        );
        if ($players_response['status'] !== 200) {
            abort(404, 'Unable to find the game players');
        }

        $game_score_sheets_response = $this->api->getGameScoreSheets(
            $this->resource_type_id,
            $this->resource_id,
            $game_id
        );

        if ($game_score_sheets_response['status'] !== 200) {
            abort(404, 'Unable to fetch the game scores');
        }

        $scores = $this->fetchPlayerScores(
            $game_score_sheets_response['content'],
            $players_response['content']
        );

        return view(
            'player-scores',
            ['scores' => $scores]
        );
    }

    public function scoreSheet(Request $request, string $game_id, string $player_id)
    {
        $this->bootstrap($request);

        $game_response = $this->api->getGame(
            $this->resource_type_id,
            $this->resource_id,
            $game_id,
            ['include-players' => 1]
        );

        if ($game_response['status'] !== 200) {
            abort($game_response['status'], $game_response['content']);
        }

        $game = $game_response['content'];

        $player_name = '';
        foreach ($game['players']['collection'] as $__player) {
            if ($__player['id'] === $player_id) {
                $player_name = $__player['name'];
                break;
            }
        }

        $player_score_sheet = $this->api->getPlayerScoreSheet(
            $this->resource_type_id,
            $this->resource_id,
            $game_id,
            $player_id
        );

        if ($player_score_sheet['status'] === 404) {
            $add_score_sheet_response = $this->api->addScoreSheetForPlayer(
                $this->resource_type_id,
                $this->resource_id,
                $game_id,
                $player_id
            );

            if ($add_score_sheet_response['status'] === 201) {
                return redirect()->route('game.score-sheet', ['game_id' => $game_id, 'player_id' => $player_id]);
            }

            abort($add_score_sheet_response['status'], $add_score_sheet_response['content']);
        }

        if ($player_score_sheet['status'] !== 200) {
            abort($player_score_sheet['status'], $player_score_sheet['content']);
        }

        return view(
            'score-sheet',
            [
                'resource_type_id' => $this->resource_type_id,
                'resource_id' => $this->resource_id,
                'game_id' => $game_id,
                'player_id' => $player_id,

                'player_name' => $player_name,

                'score_sheet' => $player_score_sheet['content']['value'],
                'turns' => $this->numberOfTurns($player_score_sheet['content']['value']),
                'complete' => $game['complete']
            ]
        );
    }
}
