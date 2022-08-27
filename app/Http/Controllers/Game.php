<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Game\AddPlayers;
use App\Actions\Game\Complete;
use App\Actions\Game\Create;
use App\Actions\Game\Delete;
use App\Actions\Game\DeletePlayer;
use App\Actions\Game\Log;
use App\Models\ShareToken;
use App\Notifications\ApiError;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;

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

    public function newGameProcess(Request $request)
    {
        $this->bootstrap($request);

        $action = new Create();
        $result = $action(
            $this->api,
            $this->resource_type_id,
            $this->resource_id,
            $request->only(['name', 'description', 'players'])
        );

        if ($result === 201) {
            return redirect()->route('game.show', ['game_id' => $action->getGameId()]);
        }

        if ($result === 422) {
            return redirect()->route('game.create.view')
                ->withInput()
                ->with('validation.errors',$action->getValidationErrors());
        }

        abort($result, $action->getMessage());
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

    public function addPlayersToGameProcess(Request $request)
    {
        $this->bootstrap($request);

        $game_id = $request->route('game_id');

        $action = new AddPlayers();
        $result = $action(
            $this->api,
            $this->resource_type_id,
            $this->resource_id,
            $game_id,
            $request->only(['players'])
        );

        if ($result === 201) {
            return redirect()->route('home');
        }

        if ($result === 422) {
            return redirect()->route('game.add-players.view', ['game_id' => $game_id])
                ->withInput()
                ->with('validation.errors',$action->getValidationErrors());
        }

        abort($result, $action->getMessage());
    }

    public function complete(Request $request, string $game_id)
    {
        $this->bootstrap($request);

        $action = new Complete();
        try {
            $result = $action(
                $this->api,
                $this->resource_type_id,
                $this->resource_id,
                $game_id
            );

            if ($result === 204) {
                return redirect()->route('home');
            }
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }

        abort(500, 'Unable to complete the game, returned status code: ' . $result['status']);
    }

    public function completeAndPlayAgain(Request $request, string $game_id)
    {
        $this->bootstrap($request);

        $action = new Complete();
        try {
            $result = $action(
                $this->api,
                $this->resource_type_id,
                $this->resource_id,
                $game_id
            );

            if ($result === 204) {

                $game_response = $this->api->getGame(
                    $this->resource_type_id,
                    $this->resource_id,
                    $game_id,
                    ['include-players' => 1]
                );

                if ($game_response['status'] !== 200) {
                   abort(404, 'Unable to find the game');
                }

                $players = [];

                foreach($game_response['content']['players']['collection'] as $player) {
                    $players[] = $player['id'];
                }

                $create_action = new Create();
                $result = $create_action(
                    $this->api,
                    $this->resource_type_id,
                    $this->resource_id,
                    [
                        'name' => 'Yatzy game',
                        'description' => 'Yatzy game create via the Yatzy app',
                        'players' => $players
                    ]
                );

                if ($result === 201) {
                    return redirect()->route('game.show', ['game_id' => $create_action->getGameId()]);
                }

                abort($result, $create_action->getMessage());

            }
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }

        abort(500, 'Unable to complete the game, returned status code: ' . $result['status']);
    }

    public function deleteGame(Request $request, string $game_id)
    {
        $this->bootstrap($request);

        $action = new Delete();
        try {
            $result = $action(
                $this->api,
                $this->resource_type_id,
                $this->resource_id,
                $game_id
            );

            if ($result === 204) {
                return redirect()->route('home');
            }
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }

        abort(500, 'Unable to delete the game, unknown error');
    }

    public function deleteGamePlayer(Request $request, string $game_id, string $player_id)
    {
        $this->bootstrap($request);

        $action = new DeletePlayer();
        try {
            $result = $action(
                $this->api,
                $this->resource_type_id,
                $this->resource_id,
                $game_id,
                $player_id
            );

            if ($result === 204) {
                return redirect()->route('home');
            }
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }

        abort(500, 'Unable to delete the player from the game, unknown error');
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

    public function scoreUpper(Request $request)
    {
        $this->bootstrap($request);

        $score_sheet = $this->api->getPlayerScoreSheet(
            $this->resource_type_id,
            $this->resource_id,
            $request->input('game_id'),
            $request->input('player_id')
        );

        if ($score_sheet['status'] !== 200) {
            return response()->json(['message' => 'Unable to fetch your score sheet'], $score_sheet['status']);
        }

        $score_sheet = $score_sheet['content']['value'];

        $score_sheet['upper-section'][$request->input('dice')] = $request->input('score');
        $score_upper = 0;
        $score_bonus = 0;
        foreach ($score_sheet['upper-section'] as $value) {
            $score_upper += $value;
        }
        if ($score_upper >= 63) {
            $score_bonus = 35;
        }

        $score_sheet['score']['upper'] = $score_upper;
        $score_sheet['score']['bonus'] = $score_bonus;
        $score_sheet['score']['total'] = $score_sheet['score']['lower'] + $score_upper + $score_bonus;

        $log_action = new Log();
        $log_action_result = $log_action(
            $this->api,
            $this->resource_type_id,
            $this->resource_id,
            $request->input('game_id'),
            'Scored ' . $request->input('score') . ' in their ' . ucfirst($request->input('dice')),
            [
                'player' => $request->input('player_id'),
                'section' => 'upper',
                'dice' => $request->input('dice'),
                'score' => $request->input('score'),
            ]
        );

        if ($log_action_result !== 201) {
            $config = Config::get('app.config');

            Notification::route('mail', $config[['error_email']])
                ->notify(new ApiError(
                    'Unable to log the score for the upper section',
                    $log_action->getMessage()
                ));
        }

        return $this->score(
            $this->api,
            $this->resource_type_id,
            $this->resource_id,
            $request->input('game_id'),
            $request->input('player_id'),
            $score_sheet
        );
    }

    public function scoreLower(Request $request)
    {
        $this->bootstrap($request);

        $score_sheet = $this->api->getPlayerScoreSheet(
            $this->resource_type_id,
            $this->resource_id,
            $request->input('game_id'),
            $request->input('player_id')
        );

        $combo = $request->input('combo');
        $score = $request->input('score');

        if ($score_sheet['status'] !== 200) {
            return response()->json(['message' => 'Unable to fetch your score sheet'], $score_sheet['status']);
        }

        $score_sheet = $score_sheet['content']['value'];

        $score_sheet['lower-section'][$combo] = $score;
        $score_lower = 0;
        foreach ($score_sheet['lower-section'] as $value) {
            $score_lower += $value;
        }

        $score_sheet['score']['lower'] = $score_lower;
        $score_sheet['score']['total'] = $score_sheet['score']['upper'] + $score_sheet['score']['bonus'] + $score_lower;

        $message = match ($combo) {
            'three_of_a_kind', 'four_of_a_kind', 'chance' => 'Scored ' . $score . ' in ' . ucfirst(
                    str_replace('_', '', $combo)
                ),
            default => 'Scored their ' . ucfirst(str_replace('_', ' ', $combo)) . ', scoring ' . $score,
        };

        $log_action = new Log();
        $log_action_result = $log_action(
            $this->api,
            $this->resource_type_id,
            $this->resource_id,
            $request->input('game_id'),
            $message,
            [
                'player' => $request->input('player_id'),
                'section' => 'lower',
                'combo' => $request->input('combo'),
                'score' => $request->input('score'),
            ]
        );

        if ($log_action_result !== 201) {
            $config = Config::get('app.config');

            Notification::route('mail', $config['error_email'])
                ->notify(new ApiError(
                    'Unable to log the score for the lower section',
                    $log_action->getMessage()
                ));
        }

        return $this->score(
            $this->api,
            $this->resource_type_id,
            $this->resource_id,
            $request->input('game_id'),
            $request->input('player_id'),
            $score_sheet
        );
    }
}
