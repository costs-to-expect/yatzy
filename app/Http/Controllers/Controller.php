<?php

namespace App\Http\Controllers;

use App\Actions\Game\ChangeScore;
use App\Api\Service;
use App\Support\GameBoard;
use App\Support\ScoreRules;
use Illuminate\Http\JsonResponse;
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
                    abort($create_resource_response['status'], $create_resource_response['content']);
                }
                abort($create_resource_type_response['status'], $create_resource_type_response['content']);
            }
        } else {
            abort($resource_types['status'], $resource_types['content']);
        }
    }

    /**
     * Scores, changes or clears one combination on a player's score sheet and answers the browser with JSON, the
     * signed-in player and the public share link both use it so a score is checked in one place (ChangeScore)
     */
    protected function changeScore(
        Service $api,
        mixed $resource_type_id,
        mixed $resource_id,
        mixed $game_id,
        mixed $player_id,
        string $section,
        mixed $combination,
        mixed $score,
        bool $replace = false,
        bool $clear = false
    ): JsonResponse
    {
        foreach ([$resource_type_id, $resource_id, $game_id, $player_id] as $id) {
            if (is_string($id) === false || $id === '') {
                return response()->json(['message' => 'The game and the player are needed to score'], 422);
            }
        }

        $action = new ChangeScore();
        $status = $action(
            $api,
            $resource_type_id,
            $resource_id,
            $game_id,
            $player_id,
            $section,
            $combination,
            $score,
            $replace,
            $clear
        );

        if ($status === 200) {
            $sheet = $action->getSheet();

            return response()->json([
                'message' => $action->getMessage(),
                'score' => $sheet['score'] ?? ScoreRules::totals($sheet),
                'turns' => ScoreRules::turns($sheet),
                'sheet' => $sheet,
            ]);
        }

        // A score that is not allowed comes back with the sheet as it is, so the browser can catch up
        if ($action->failedToSave() === false && $action->getSheet() !== []) {
            return response()->json(['message' => $action->getMessage(), 'sheet' => $action->getSheet()], $status);
        }

        return response()->json(['message' => $action->getMessage()], $status);
    }

    /**
     * What the score sheet script needs to draw the sheet and to save to it. It is written into the page as JSON, the
     * script keeps no other state, and the signed-in page and the public page differ only in their addresses.
     *
     * @param array<string, string> $urls where to save, where to read everyone's scores and where to go back to
     * @param array<string, string> $ids sent with every save by the signed-in page, the public link knows its own
     */
    protected function sheetConfig(
        Service $api,
        string $resource_type_id,
        array $score_sheet,
        string $player_id,
        string $player_name,
        bool $complete,
        array $urls,
        array $ids = []
    ): array
    {
        $players_response = $api->getPlayers($resource_type_id, ['collection' => true]);

        $players = [];
        if ($players_response['status'] === 200) {
            foreach ($players_response['content'] as $player) {
                $players[] = ['id' => $player['id'], 'name' => $player['name']];
            }
        }

        return [
            'player' => ['id' => $player_id, 'name' => $player_name],
            'sheet' => $score_sheet,
            'complete' => $complete,
            'corrections' => (bool) config('app.config.score_corrections'),
            'turns' => (int) config('app.game.turns'),
            'rules' => ScoreRules::lowerRules(),
            'tones' => (object) GameBoard::tones($players),
            'urls' => $urls,
            'ids' => (object) $ids,
        ];
    }

    protected function numberOfTurns(array $score_sheet): int
    {
        return ScoreRules::turns($score_sheet);
    }

    /**
     * Everyone's scores for the "Everyone" panel, a player with no score sheet yet is on zero
     *
     * @param array $game_score_sheets the game's score sheets, keyed by player
     * @param array $players the players assigned to the game
     * @return list<array{id: string, name: string, upper: int, bonus: int, lower: int, total: int, turns: int}>
     */
    protected function fetchPlayerScores(
        array $game_score_sheets,
        array $players
    ): array
    {
        $scores = [];
        foreach ($players as $player) {
            $scores[$player['category']['id']] = [
                'id' => $player['category']['id'],
                'name' => $player['category']['name'],
                'upper' => 0,
                'bonus' => 0,
                'lower' => 0,
                'total' => 0,
                'turns' => 0,
            ];
        }

        foreach ($game_score_sheets as $score_sheet) {
            // A score sheet for a player who has since been removed from the game
            if (array_key_exists($score_sheet['key'], $scores) === false) {
                continue;
            }

            $totals = $score_sheet['value']['score'];
            $scores[$score_sheet['key']]['upper'] = $totals['upper'];
            $scores[$score_sheet['key']]['bonus'] = $totals['bonus'];
            $scores[$score_sheet['key']]['lower'] = $totals['lower'];
            $scores[$score_sheet['key']]['total'] = $totals['total'];
            $scores[$score_sheet['key']]['turns'] = $this->numberOfTurns($score_sheet['value']);
        }

        return array_values($scores);
    }
}
