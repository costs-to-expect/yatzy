<?php
declare(strict_types=1);

namespace App\Http\Controllers\Action;

use App\Api\Service;
use App\Http\Controllers\Controller;
use App\Models\ShareToken;
use App\Support\ScoreRules;
use Illuminate\Http\Request;

/**
 * Scoring through the public link of a player, the link is a token the app maps back to the game, the player and the
 * owner's bearer token. It can only ever score for the player it was made for.
 *
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yatzy/blob/main/LICENSE
 */
class Share extends Controller
{
    public function scoreUpper(Request $request, $token)
    {
        return $this->scoreFor($token, ScoreRules::UPPER_SECTION, $request->input('dice'), $request);
    }

    public function scoreLower(Request $request, $token)
    {
        return $this->scoreFor($token, ScoreRules::LOWER_SECTION, $request->input('combo'), $request);
    }

    public function scoreClear(Request $request, $token)
    {
        return $this->scoreFor($token, (string) $request->input('section'), $request->input('combo'), $request, true);
    }

    private function scoreFor(string $token, string $section, mixed $combination, Request $request, bool $clear = false)
    {
        $parameters = ShareToken::parametersFor($token);

        // The game and the player are the link's, never whatever the browser sent
        return $this->changeScore(
            new Service($parameters['owner_bearer']),
            $parameters['resource_type_id'],
            $parameters['resource_id'],
            $parameters['game_id'],
            $parameters['player_id'],
            $section,
            $combination,
            $clear ? null : $request->input('score'),
            $request->boolean('replace'),
            $clear
        );
    }
}
