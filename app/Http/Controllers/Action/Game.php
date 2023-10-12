<?php
declare(strict_types=1);

namespace App\Http\Controllers\Action;

use App\Actions\Game\AddPlayers;
use App\Actions\Game\Complete;
use App\Actions\Game\Create;
use App\Actions\Game\Delete;
use App\Actions\Game\DeletePlayer;
use App\Actions\Game\Log;
use App\Actions\Game\Start;
use App\Http\Controllers\Controller;
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
    public function newGame(Request $request)
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

    public function addPlayersToGame(Request $request)
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
            $score_bonus = 50;
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

            Notification::route('mail', $config['error_email'])
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
            'one_pair', 'two_pair', 'three_of_a_kind', 'four_of_a_kind', 'full_house', 'chance' => 'Scored ' . $score . ' in ' . ucfirst(
                    str_replace('_', ' ', $combo)
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

    public function start(Request $request)
    {
        $this->bootstrap($request);

        $action = new Start();
        $result = $action(
            $this->api,
            $this->resource_type_id,
            $this->resource_id,
            $request->only(['players'])
        );

        if ($result === 201) {
            return redirect()->route('game.show', ['game_id' => $action->getGameId()]);
        }

        if ($result === 422) {
            return redirect()->route('home')
                ->withInput()
                ->with('validation.errors',$action->getValidationErrors());
        }

        abort($result, $action->getMessage());
    }
}
