<?php

namespace App\Http\Controllers;

use App\Actions\Game\Score;
use App\Api\Service;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Config;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yatzy/blob/main/LICENSE
 */
class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    protected array $config;

    protected string $item_type_id;
    protected string $item_subtype_id;

    protected ?string $resource_type_id = null;
    protected ?string $resource_id = null;

    protected Service $api;

    public function __construct()
    {
        $this->config = Config::get('app.config');
        $this->item_type_id = $this->config['item_type_id'];
        $this->item_subtype_id = $this->config['item_subtype_id'];
    }

    protected function bootstrap(Request $request)
    {
        $this->api = new Service($request->cookie($this->config['cookie_bearer']));

        $resource_types = $this->api->getResourceTypes(['item-type' => $this->item_type_id]);

        if ($resource_types['status'] === 200) {

            if (count($resource_types['content']) === 1) {

                $resource_type_id = $resource_types['content'][0]['id'];
                $resources = $this->api->getResources($resource_type_id, ['item-subtype' => $this->item_subtype_id]);

                if ($resources['status'] === 200) {

                    if (count($resources['content']) === 1) {
                        $this->resource_type_id = $resource_type_id;
                        $this->resource_id = $resources['content'][0]['id'];

                        return true;
                    }

                    $create_resource_response = $this->api->createResource($resource_type_id);
                    if ($create_resource_response['status'] === 201) {
                        $this->resource_type_id = $resource_type_id;
                        $this->resource_id = $create_resource_response['content']['id'];

                        return true;
                    }
                    abort($create_resource_response['status'], $create_resource_response['content']);
                } else {
                    abort($resources['status'], $resources['content']);
                }
            } else {
                $create_resource_type_response = $this->api->createResourceType();
                if ($create_resource_type_response['status'] === 201) {

                    $this->resource_type_id = $create_resource_type_response['content']['id'];

                    $create_resource_response = $this->api->createResource($this->resource_type_id);
                    if ($create_resource_response['status'] === 201) {
                        $this->resource_id = $create_resource_response['content']['id'];

                        return true;
                    }
                }
                abort($create_resource_type_response['status'], $create_resource_type_response['content']);
            }
        } else {
            abort($resource_types['status'], $resource_types['content']);
        }
    }

    protected function score(
        Service $api,
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        string $player_id,
        array $score_sheet
    )
    {
        $action = new Score();
        $result = $action(
            $api,
            $resource_type_id,
            $resource_id,
            $game_id,
            $player_id,
            $score_sheet
        );

        if ($result === 204) {
            return response()->json([
                'message' => 'Score updated',
                'score' => $score_sheet['score'],
                'turns' => $this->numberOfTurns($score_sheet)
            ]);
        }

        return response()->json(['message' => 'Failed to update your score sheet'], $result);
    }

    protected function numberOfTurns(array $score_sheet): int
    {
        $turns = 0;
        if (array_key_exists('upper-section', $score_sheet)) {
            $turns += count($score_sheet['upper-section']);
        }
        if (array_key_exists('lower-section', $score_sheet)) {
            $turns += count($score_sheet['lower-section']);
        }

        return $turns;
    }

    protected function fetchPlayerScores(
        array $game_score_sheets,
        array $players
    ): array
    {
        $scores = [];
        foreach ($players as $player) {
            $scores[$player['category']['id']] = [
                'name' => $player['category']['name'],
                'upper' => 0,
                'lower' => 0,
                'total' => 0,
                'turns' => 0,
            ];
        }

        foreach ($game_score_sheets as $score_sheet) {
            $scores[$score_sheet['key']]['upper'] = $score_sheet['value']['score']['upper'] + $score_sheet['value']['score']['bonus'];
            $scores[$score_sheet['key']]['lower'] = $score_sheet['value']['score']['lower'];
            $scores[$score_sheet['key']]['total'] = $score_sheet['value']['score']['total'];
            $scores[$score_sheet['key']]['turns'] = $this->numberOfTurns($score_sheet['value']);
        }

        return $scores;
    }

    protected function playerBonusMessage(string $game_id, string $player_id, array $upper_section)
    {
        if (count($upper_section) === 0) {
            $message = 'Looking good, you haven\'t messed up yet!';
            return $this->playerBonusView($game_id, $player_id, $message);
        }

        $total = 0;
        $dice_scored = [];
        $dice_scratched = [];
        $dice_to_score = ['ones', 'twos', 'threes', 'fours', 'fives', 'sixes'];
        $dice_values = ['ones' => 1, 'twos' => 2, 'threes' => 3, 'fours' => 4, 'fives' => 5, 'sixes' => 6];
        foreach ($upper_section as $dice => $score) {
            $total += $score;
            if ($score !== 0) {
                unset($dice_to_score[array_search($dice, $dice_to_score, true)]);
                $dice_scored[] = $dice;
            } else {
                unset($dice_to_score[array_search($dice, $dice_to_score, true)]);
                $dice_scratched[] = $dice;
            }
        }

        if ($total >= 63 && count($dice_scored) < 6) {
            $message = 'OK, OK, you have the bonus without even finishing!';
            return $this->playerBonusView($game_id, $player_id, $message);
        }

        if ($total === 63 && count($dice_scored) === 6) {
            $message = 'Damn, that was close, next time, give yourself a little breathing room!';
            return $this->playerBonusView($game_id, $player_id, $message);
        }

        if ($total > 85 && count($dice_scored) === 6) {
            $message = 'Umm!, I wasn\'t even sure you could score this much!';
            return $this->playerBonusView($game_id, $player_id, $message);
        }

        if ($total > 75 && count($dice_scored) === 6) {
            $message = 'WOW, someone is showing off!';
            return $this->playerBonusView($game_id, $player_id, $message);
        }

        if ($total > 68 && count($dice_scored) === 6) {
            $message = 'Awesome, plenty of breathing room!';
            return $this->playerBonusView($game_id, $player_id, $message);
        }

        if ($total > 63 && count($dice_scored) === 6) {
            $message = 'Awesome, made it with a little breathing room!';
            return $this->playerBonusView($game_id, $player_id, $message);
        }

        if ($total > 75 && count($dice_scratched) > 0) {
            $message = 'Um! How exactly did you manage to get your bonus!';
            return $this->playerBonusView($game_id, $player_id, $message);
        }

        if ($total >= 63 && count($dice_scratched) > 0) {
            $message = 'WOW, nothing like scoring the bonus whilst also scratching!';
            return $this->playerBonusView($game_id, $player_id, $message);
        }

        if ($total === 63 && (count($dice_scored) + count($dice_scratched)) === 6) {
            $message = 'Damn, that was close, next time, give yourself some breathing room!';
            return $this->playerBonusView($game_id, $player_id, $message);
        }

        if ($total === 62 && (count($dice_scored) + count($dice_scratched)) === 6) {
            $message = 'You were robbed! You needed one point, anyone got one spare?';
            return $this->playerBonusView($game_id, $player_id, $message);
        }

        if ($total < 62 && (count($dice_scored) + count($dice_scratched)) === 6) {
            $message = 'Oh! Time to play the tiny little violin just for you!';
            return $this->playerBonusView($game_id, $player_id, $message);
        }

        if (
            $total < 63 && (count($dice_scored) + count($dice_scratched)) < 6
        ) {
            $ones_total = $total;
            $twos_total = $total;
            $threes_total = $total;
            $fours_total = $total;
            foreach($dice_to_score as $dice) {
                $ones_total += ($dice_values[$dice] * 1);
                $twos_total += ($dice_values[$dice] * 2);
                $threes_total += ($dice_values[$dice] * 3);
                $fours_total += ($dice_values[$dice] * 4);
            }

            if ($ones_total === 63) {
                $message = 'Easy, one of everything left will do!';
                return $this->playerBonusView($game_id, $player_id, $message);
            }
            if ($ones_total >= 63) {
                if ((count($dice_scored) + count($dice_scratched)) === 5) {
                    $message = 'Easy, one of the last dice please!';
                    return $this->playerBonusView($game_id, $player_id, $message);
                }
                $message = 'You should be able to get your bonus without even trying!';
                return $this->playerBonusView($game_id, $player_id, $message);
            }

            if ($twos_total === 63) {
                $message = 'Looking good, two of everything left will do!';
                return $this->playerBonusView($game_id, $player_id, $message);
            }
            if ($twos_total >= 63) {
                if ((count($dice_scored) + count($dice_scratched)) === 5) {
                    $message = 'You can still easily get the bonus, two of the last dice please!';
                    return $this->playerBonusView($game_id, $player_id, $message);
                }
                $message = 'You can still easily get the bonus';
                return $this->playerBonusView($game_id, $player_id, $message);
            }

            if ($threes_total === 63) {
                $message = 'Looking good, you haven\'t messed up yet, three of everything left will do!';
                return $this->playerBonusView($game_id, $player_id, $message);
            }
            if ($threes_total > 63) {
                if ((count($dice_scored) + count($dice_scratched)) === 5) {
                    $message = 'You can still get the bonus, three of the last dice please!';
                    return $this->playerBonusView($game_id, $player_id, $message);
                }
                $message = 'You can still easily get the bonus';
                return $this->playerBonusView($game_id, $player_id, $message);
            }
            if ($fours_total === 63) {
                $message = 'Looking good, you haven\'t messed up yet, you can still score the bonus without a Yatzy!';
                return $this->playerBonusView($game_id, $player_id, $message);
            }
            if ($fours_total > 63) {
                if ((count($dice_scored) + count($dice_scratched)) === 5) {
                    $message = 'You can still get the bonus, four of the last dice please!';
                    return $this->playerBonusView($game_id, $player_id, $message);
                }

                $message = 'You can still get the bonus, you need four of something';
                return $this->playerBonusView($game_id, $player_id, $message);
            }
            if ($fours_total < 63) {
                $message = 'Scoring four of everything won\'t help you!';
                return $this->playerBonusView($game_id, $player_id, $message);
            }

            if ($threes_total < 63) {
                $message = 'Scoring three of everything won\'t help you!';
                return $this->playerBonusView($game_id, $player_id, $message);
            }
        }

        $message = 'No message for you! I can\'t think of anything to say, do something interesting and things might change!';
        return $this->playerBonusView($game_id, $player_id, $message);
    }

    protected function playerBonusView(string $game_id, string $player_id, string $message)
    {
        return view(
            'bonus',
            [
                'message' => $message,
                'game_id' => $game_id,
                'player_id' => $player_id,
            ]
        );
    }
}
