<?php
declare(strict_types=1);

namespace App\Http\Controllers\Action;

use App\Actions\Game\Log;
use App\Api\Service;
use App\Http\Controllers\Controller;
use App\Models\ShareToken;
use App\Notifications\ApiError;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use JetBrains\PhpStorm\ArrayShape;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yatzy/blob/main/LICENSE
 */
class Share extends Controller
{
    public function scoreUpper(Request $request, $token)
    {
        $parameters = $this->getParameters($token);

        $api = new Service($parameters['owner_bearer']);

        $score_sheet = $this->getScoreSheet($api, $parameters);

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
            $api,
            $parameters['resource_type_id'],
            $parameters['resource_id'],
            $parameters['game_id'],
            'Scored ' . $request->input('score') . ' in their ' . ucfirst($request->input('dice')),
            [
                'player' => $parameters['player_id'],
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
            $api,
            $parameters['resource_type_id'],
            $parameters['resource_id'],
            $parameters['game_id'],
            $parameters['player_id'],
            $score_sheet
        );
    }

    public function scoreLower(Request $request, $token)
    {
        $parameters = $this->getParameters($token);

        $api = new Service($parameters['owner_bearer']);

        $score_sheet = $this->getScoreSheet($api, $parameters);

        $combo = $request->input('combo');
        $score = $request->input('score');

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
            $api,
            $parameters['resource_type_id'],
            $parameters['resource_id'],
            $parameters['game_id'],
            $message,
            [
                'player' => $parameters['player_id'],
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
            $api,
            $parameters['resource_type_id'],
            $parameters['resource_id'],
            $parameters['game_id'],
            $parameters['player_id'],
            $score_sheet
        );
    }

    #[ArrayShape([
        'resource_type_id' => "string",
        'resource_id' => "string",
        'game_id' => "string",
        'player_id' => "string",
        'player_name' => 'string',
        'owner_bearer' => "string"
    ])]
    private function getParameters($token): array
    {
        $parameters = ShareToken::query()->where('token', $token)->first();
        if ($parameters === null) {
            abort(404, 'The game page for the token does not exist');
        }

        try {
            $parameters = json_decode($parameters->parameters, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            abort(500, 'Failed to decode the parameters for the token');
        }

        if (array_key_exists('player_name', $parameters) === false) {
            $parameters['player_name'] = 'Yatzy Player';
        }

        return $parameters;
    }

    private function getScoreSheet(Service $api, array $parameters)
    {
        $score_sheet = $api->getPlayerScoreSheet(
            $parameters['resource_type_id'],
            $parameters['resource_id'],
            $parameters['game_id'],
            $parameters['player_id']
        );

        if ($score_sheet['status'] !== 200) {
            return response()->json(['message' => 'Unable to fetch your score sheet'], $score_sheet['status']);
        }

        return $score_sheet['content']['value'];
    }
}
