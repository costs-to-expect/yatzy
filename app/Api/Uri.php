<?php
declare(strict_types=1);

namespace App\Api;

use JetBrains\PhpStorm\ArrayShape;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yatzy/blob/main/LICENSE
 */
class Uri
{
    private const VERSION = 'v3';

    #[ArrayShape(['uri' => "string", 'name' => "string"])]
    public static function authSignIn(): array
    {
        return [
            'uri' => '/' . self::VERSION . '/auth/login',
            'name' => 'Sign-in'
        ];
    }

    #[ArrayShape(['uri' => "string", 'name' => "string"])]
    public static function authUser(): array
    {
        return [
            'uri' => '/' . self::VERSION . '/auth/user',
            'name' => 'User'
        ];
    }

    #[ArrayShape(['uri' => "string", 'name' => "string"])]
    public static function createPassword(string $token, string $email): array
    {
        $uri = '/' . self::VERSION . '/auth/create-password?token=' .
                urlencode($token) . '&email=' . urlencode($email);

        return [
            'uri' => $uri,
            'name' => 'Create Password'
        ];
    }

    #[ArrayShape(['uri' => "string", 'name' => "string"])]
    public static function game(string $resource_type_id, string $resource_id, string $game_id, array $parameters = []): array
    {
        $uri = '/' . self::VERSION . '/resource-types/' . $resource_type_id .
            '/resources/' . $resource_id . '/items/' . $game_id;
        if (count($parameters) > 0) {
            $uri .= '?' . http_build_query($parameters);
        }

        return [
            'uri' => $uri,
            'name' => 'Game'
        ];
    }

    #[ArrayShape(['uri' => "string", 'name' => "string"])]
    public static function gameLog(
        string $resource_type_id,
        string $resource_id,
        string $game_id
    ): array
    {
        $uri = '/' . self::VERSION . '/resource-types/' . $resource_type_id .
            '/resources/' . $resource_id . '/items/' . $game_id . '/log';

        return [
            'uri' => $uri,
            'name' => 'Game log'
        ];
    }

    #[ArrayShape(['uri' => "string", 'name' => "string"])]
    public static function assignedGamePlayer(
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        string $player_id
    ): array
    {
        $uri = '/' . self::VERSION . '/resource-types/' . $resource_type_id .
            '/resources/' . $resource_id . '/items/' . $game_id . '/categories/' .
            $player_id;

        return [
            'uri' => $uri,
            'name' => 'Game player'
        ];
    }

    #[ArrayShape(['uri' => "string", 'name' => "string"])]
    public static function assignedGamePlayers(string $resource_type_id, string $resource_id, string $game_id, array $parameters = []): array
    {
        $uri = '/' . self::VERSION . '/resource-types/' . $resource_type_id .
            '/resources/' . $resource_id . '/items/' . $game_id . '/categories';
        if (count($parameters) > 0) {
            $uri .= '?' . http_build_query($parameters);
        }

        return [
            'uri' => $uri,
            'name' => 'Game players'
        ];
    }

    #[ArrayShape(['uri' => "string", 'name' => "string"])]
    public static function gameScoreSheets(
        string $resource_type_id,
        string $resource_id,
        string $game_id
    ): array
    {
        $uri = '/' . self::VERSION . '/resource-types/' . $resource_type_id .
            '/resources/' . $resource_id . '/items/' . $game_id . '/data';

        return [
            'uri' => $uri,
            'name' => 'Game score sheets'
        ];
    }

    #[ArrayShape(['uri' => "string", 'name' => "string"])]
    public static function games(string $resource_type_id, string $resource_id, array $parameters = []): array
    {
        $uri = '/' . self::VERSION . '/resource-types/' . $resource_type_id .
            '/resources/' . $resource_id . '/items';
        if (count($parameters) > 0) {
            $uri .= '?' . http_build_query($parameters);
        }

        return [
            'uri' => $uri,
            'name' => 'Games'
        ];
    }

    #[ArrayShape(['uri' => "string", 'name' => "string"])]
    public static function players(string $resource_type_id, array $parameters = []): array
    {
        $uri = '/' . self::VERSION . '/resource-types/' . $resource_type_id .
            '/categories';
        if (count($parameters) > 0) {
            $uri .= '?' . http_build_query($parameters);
        }

        return [
            'uri' => $uri,
            'name' => 'Player list'
        ];
    }

    #[ArrayShape(['uri' => "string", 'name' => "string"])]
    public static function playerScoreSheet(
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        string $player_id
    ): array
    {
        $uri = '/' . self::VERSION . '/resource-types/' . $resource_type_id .
            '/resources/' . $resource_id . '/items/' . $game_id . '/data/' . $player_id;

        return [
            'uri' => $uri,
            'name' => 'Player score sheet'
        ];
    }

    #[ArrayShape(['uri' => "string", 'name' => "string"])]
    public static function register(): array
    {
        $uri = '/' . self::VERSION . '/auth/register?send=false';

        return [
            'uri' => $uri,
            'name' => 'Register'
        ];
    }

    #[ArrayShape(['uri' => "string", 'name' => "string"])]
    public static function resource(string $resource_type_id, string $resource_id, array $parameters = []): array
    {
        $uri = '/' . self::VERSION . '/resource-types/' . $resource_type_id . '/resources/' . $resource_id;
        if (count($parameters) > 0) {
            $uri .= '?' . http_build_query($parameters);
        }

        return [
            'uri' => $uri,
            'name' => 'Resource'
        ];
    }

    #[ArrayShape(['uri' => "string", 'name' => "string"])]
    public static function resources(string $resource_type_id, array $parameters = []): array
    {
        $uri = '/' . self::VERSION . '/resource-types/' . $resource_type_id . '/resources';
        if (count($parameters) > 0) {
            $uri .= '?' . http_build_query($parameters);
        }

        return [
            'uri' => $uri,
            'name' => 'Resources'
        ];
    }

    #[ArrayShape(['uri' => "string", 'name' => "string"])]
    public static function resourceTypes(array $parameters = []): array
    {
        $uri = '/' . self::VERSION . '/resource-types';
        if (count($parameters) > 0) {
            $uri .= '?' . http_build_query($parameters);
        }

        return [
            'uri' => $uri,
            'name' => 'Resource types'
        ];
    }
}
