<?php
declare(strict_types=1);

namespace App\Api;

use Illuminate\Support\Facades\Config;
use JetBrains\PhpStorm\ArrayShape;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yatzy/blob/main/LICENSE
 */
class Service
{
    private Http $http;

    private string $item_type_id;
    private string $item_subtype_id;

    public function __construct(string $bearer = null)
    {
        $this->http = new Http($bearer);

        $this->item_type_id = Config::get('app.config.item_type_id');
        $this->item_subtype_id = Config::get('app.config.item_subtype_id');
    }

    #[ArrayShape(['status' => "integer", 'content' => "array", 'fields' => "array"])]
    public function addPlayerToGame(
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        string $player_id
    ): array
    {
        $uri = Uri::assignedGamePlayers($resource_type_id, $resource_id, $game_id);

        return $this->http->post(
            $uri['uri'],
            [
                'category_id' => $player_id
            ]
        );
    }

    /**
     * @throws \JsonException
     */
    #[ArrayShape(['status' => "integer", 'content' => "array", 'fields' => "array"])]
    public function addScoreSheetForPlayer(
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        string $player_id
    ): array
    {
        $uri = Uri::gameScoreSheets(
            $resource_type_id,
            $resource_id,
            $game_id
        );

        $score_sheet = [
            'upper-section' => [],
            'lower-section' => [],
            'score' => [
                'upper' => 0,
                'bonus' => 0,
                'lower' => 0,
                'total' => 0,
            ]
        ];

        return $this->http->post(
            $uri['uri'],
            [
                'key' => $player_id,
                'value' => json_encode($score_sheet, JSON_THROW_ON_ERROR)
            ]
        );
    }

    #[ArrayShape(['status' => "integer", 'content' => "array"])]
    public function authSignIn(string $email, string $password): array
    {
        $uri = Uri::authSignIn();

        return $this->http->post(
            $uri['uri'],
            [
                'email' => $email,
                'password' => $password,
                'device_name' => (app()->environment('local') ? 'yatzy:local:' : 'yatzy:')
            ]
        );
    }

    #[ArrayShape(['status' => "integer", 'content' => "array"])]
    public function getAuthUser(): array
    {
        $uri = Uri::authUser();

        return $this->http->get($uri['uri']);
    }

    #[ArrayShape(['status' => "integer", 'content' => "array", 'fields' => "array"])]
    public function createGame(
        string $resource_type_id,
        string $resource_id,
        string $name,
        string $description,
    ): array
    {
        $uri = Uri::games($resource_type_id, $resource_id);

        return $this->http->post(
            $uri['uri'],
            [
                'name' => $name,
                'description' => $description
            ]
        );
    }

    #[ArrayShape(['status' => "integer", 'content' => "array", 'fields' => "array"])]
    public function createGameLogMessage(
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        string $message,
        array $parameters = []
    ): array
    {
        $uri = Uri::gameLog($resource_type_id, $resource_id, $game_id);

        return $this->http->post(
            $uri['uri'],
            [
                'message' => $message,
                'parameters' => json_encode($parameters, JSON_THROW_ON_ERROR)
            ]
        );
    }

    #[ArrayShape(['status' => "integer", 'content' => "array", 'fields' => "array"])]
    public function createPlayer(string $resource_type_id, string $name, string $description): array
    {
        $uri = Uri::players($resource_type_id);

        return $this->http->post(
            $uri['uri'],
            [
                'name' => $name,
                'description' => $description
            ]
        );
    }

    #[ArrayShape(['status' => "integer", 'content' => "array", 'fields' => "array"])]
    public function createPassword(array $payload): array
    {
        $uri = Uri::createPassword($payload['token'], $payload['email']);

        return $this->http->post(
            $uri['uri'],
            [
                'password' => $payload['password'],
                'password_confirmation' => $payload['password_confirmation']
            ]
        );
    }

    #[ArrayShape(['status' => "integer", 'content' => "array"])]
    public function createResource(string $resource_type_id): array
    {
        $uri = Uri::resources($resource_type_id);

        return $this->http->post(
            $uri['uri'],
            [
                'name' => 'Yatzy game tracker',
                'description' => 'Yatzy game tracker for Yatzy app user',
                'item_subtype_id' => $this->item_subtype_id
            ]
        );
    }

    #[ArrayShape(['status' => "integer", 'content' => "array"])]
    public function createResourceType(): array
    {
        $uri = Uri::resourceTypes();

        return $this->http->post(
            $uri['uri'],
            [
                'name' => 'Game trackers',
                'description' => 'Game trackers for Yatzy app user',
                'item_type_id' => $this->item_type_id
            ]
        );
    }

    #[ArrayShape(['status' => "integer", 'content' => "array", 'fields' => "array"])]
    public function deleteAssignedGamePlayer(
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        string $player_id
    ): array
    {
        $uri = Uri::assignedGamePlayer(
            $resource_type_id,
            $resource_id,
            $game_id,
            $player_id
        );

        return $this->http->delete($uri['uri']);
    }

    #[ArrayShape(['status' => "integer", 'content' => "array"])]
    public function deleteGame(
        string $resource_type_id,
        string $resource_id,
        string $game_id
    ): array
    {
        $uri = Uri::game($resource_type_id, $resource_id, $game_id);

        return $this->http->delete($uri['uri']);
    }

    #[ArrayShape(['status' => "integer", 'content' => "array"])]
    public function deletePlayerScoreSheet(
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        string $player_id
    ): array
    {
        $uri = Uri::playerScoreSheet($resource_type_id, $resource_id, $game_id, $player_id);

        return $this->http->delete($uri['uri']);
    }

    #[ArrayShape(['status' => "integer", 'content' => "array"])]
    public function getGame(
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        array $parameters = []
    ): array
    {
        $uri = Uri::game($resource_type_id, $resource_id, $game_id, $parameters);

        return $this->http->get($uri['uri']);
    }

    #[ArrayShape(['status' => "integer", 'content' => "array"])]
    public function getAssignedGamePlayers(
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        array $parameters = []
    ): array
    {
        $uri = Uri::assignedGamePlayers($resource_type_id, $resource_id, $game_id, $parameters);

        return $this->http->get($uri['uri']);
    }

    #[ArrayShape(['status' => "integer", 'content' => "array", 'headers' => "array"])]
    public function getGames(
        string $resource_type_id,
        string $resource_id,
        array $parameters = [],
        bool $skip_cache = false,
    ): array
    {
        if ($skip_cache === true || (array_key_exists('complete', $parameters) && $parameters['complete'] === 1)) {
            $skip_cache = true;
        }

        $uri = Uri::games($resource_type_id, $resource_id, $parameters);

        return $this->http->get($uri['uri'], $skip_cache);
    }

    #[ArrayShape(['status' => "integer", 'content' => "array"])]
    public function getGameScoreSheets(
        string $resource_type_id,
        string $resource_id,
        string $game_id
    ): array
    {
        $uri = Uri::gameScoreSheets($resource_type_id, $resource_id, $game_id);

        return $this->http->get($uri['uri']);
    }

    #[ArrayShape(['status' => "integer", 'content' => "array"])]
    public function getPlayers(
        string $resource_type_id,
        array $parameters = []
    ): array
    {
        $uri = Uri::players($resource_type_id, $parameters);

        return $this->http->get($uri['uri'], true);
    }

    #[ArrayShape(['status' => "integer", 'content' => "array"])]
    public function getPlayerScoreSheet(
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        string $player_id
    ): array
    {
        $uri = Uri::playerScoreSheet($resource_type_id, $resource_id, $game_id, $player_id);

        return $this->http->get($uri['uri']);
    }

    #[ArrayShape(['status' => "integer", 'content' => "array"])]
    public function getResources(string $resource_type_id, array $parameters = []): array
    {
        $uri = Uri::resources($resource_type_id, $parameters);

        return $this->http->get($uri['uri'], true);
    }

    #[ArrayShape(['status' => "integer", 'content' => "array"])]
    public function getResourceTypes(array $parameters = []): array
    {
        $uri = Uri::resourceTypes($parameters);

        return $this->http->get($uri['uri'], true);
    }

    #[ArrayShape(['status' => "integer", 'content' => "array", 'fields' => "array"])]
    public function register(array $payload): array
    {
        $uri = Uri::register();

        return $this->http->post(
            $uri['uri'],
            [
                'name' => $payload['name'],
                'email' => $payload['email'],
                'registered_via' => 'yatzy',
            ]
        );
    }

    #[ArrayShape(['status' => "integer", 'content' => "array", 'fields' => "array"])]
    public function requestResourceDelete(
        string $resource_type_id,
        string $resource_id
    ): array
    {
        $uri = Uri::requestResourceDelete($resource_type_id, $resource_id);

        return $this->http->post($uri['uri'], []);
    }

    #[ArrayShape(['status' => "integer", 'content' => "array", 'fields' => "array"])]
    public function requestAccountDelete(): array
    {
        $uri = Uri::requestAccountDelete();

        return $this->http->post($uri['uri'], []);
    }

    #[ArrayShape(['status' => "integer", 'content' => "array", 'fields' => "array"])]
    public function updateGame(
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        array $payload,
    ): array
    {
        $uri = Uri::game($resource_type_id, $resource_id, $game_id);

        return $this->http->patch($uri['uri'], $payload);
    }

    #[ArrayShape(['status' => "integer", 'content' => "array", 'fields' => "array"])]
    public function updateScoreSheetForPlayer(
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        string $player_id,
        array $score_sheet
    ): array
    {
        $uri = Uri::playerScoreSheet($resource_type_id, $resource_id, $game_id, $player_id);

        return $this->http->patch(
            $uri['uri'],
            [
                'value' => json_encode($score_sheet, JSON_THROW_ON_ERROR)
            ]
        );
    }
}
