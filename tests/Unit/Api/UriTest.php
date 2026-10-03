<?php

declare(strict_types=1);

namespace Tests\Unit\Api;

use App\Api\Uri;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UriTest extends TestCase
{
    /**
     * @return array<string, array{Closure, string, string}> the call, the expected uri, the expected name
     */
    public static function uris(): array
    {
        $games = '/v3/resource-types/rt-1/resources/r-1/items';

        return [
            'sign-in' => [fn () => Uri::authSignIn(), '/v3/auth/login', 'Sign-in'],
            'authenticated user' => [fn () => Uri::authUser(), '/v3/auth/user', 'User'],
            'create password encodes the token and email' => [
                fn () => Uri::createPassword('abc 123', 'a+b@example.test'),
                '/v3/auth/create-password?token=abc+123&email=a%2Bb%40example.test',
                'Create Password',
            ],
            'register asks the API not to send the email' => [fn () => Uri::register(), '/v3/auth/register?send=false', 'Register'],
            'request account delete' => [fn () => Uri::requestAccountDelete(), '/v3/auth/user/request-delete', 'Request account deletion'],
            'request resource delete' => [
                fn () => Uri::requestResourceDelete('rt-1', 'r-1'),
                '/v3/auth/user/permitted-resource-types/rt-1/resources/r-1/request-delete',
                'Request resource deletion',
            ],
            'resource types' => [fn () => Uri::resourceTypes(), '/v3/resource-types', 'Resource types'],
            'resource types with parameters' => [
                fn () => Uri::resourceTypes(['item-type' => 'abc']),
                '/v3/resource-types?item-type=abc',
                'Resource types',
            ],
            'resources' => [fn () => Uri::resources('rt-1'), '/v3/resource-types/rt-1/resources', 'Resources'],
            'resources with parameters' => [
                fn () => Uri::resources('rt-1', ['item-subtype' => 'xyz']),
                '/v3/resource-types/rt-1/resources?item-subtype=xyz',
                'Resources',
            ],
            'resource' => [fn () => Uri::resource('rt-1', 'r-1'), '/v3/resource-types/rt-1/resources/r-1', 'Resource'],
            'players' => [fn () => Uri::players('rt-1'), '/v3/resource-types/rt-1/categories', 'Player list'],
            'players with parameters' => [
                fn () => Uri::players('rt-1', ['collection' => true]),
                '/v3/resource-types/rt-1/categories?collection=1',
                'Player list',
            ],
            'games' => [fn () => Uri::games('rt-1', 'r-1'), $games, 'Games'],
            'games with parameters' => [
                fn () => Uri::games('rt-1', 'r-1', ['complete' => 0, 'include-players' => 1]),
                $games.'?complete=0&include-players=1',
                'Games',
            ],
            'game' => [fn () => Uri::game('rt-1', 'r-1', 'g-1'), $games.'/g-1', 'Game'],
            'game with parameters' => [
                fn () => Uri::game('rt-1', 'r-1', 'g-1', ['include-players' => 1]),
                $games.'/g-1?include-players=1',
                'Game',
            ],
            'game log' => [fn () => Uri::gameLog('rt-1', 'r-1', 'g-1'), $games.'/g-1/log', 'Game log'],
            'assigned game players' => [
                fn () => Uri::assignedGamePlayers('rt-1', 'r-1', 'g-1'),
                $games.'/g-1/categories',
                'Game players',
            ],
            'assigned game player' => [
                fn () => Uri::assignedGamePlayer('rt-1', 'r-1', 'g-1', 'p-1'),
                $games.'/g-1/categories/p-1',
                'Game player',
            ],
            'game score sheets' => [
                fn () => Uri::gameScoreSheets('rt-1', 'r-1', 'g-1'),
                $games.'/g-1/data',
                'Game score sheets',
            ],
            'player score sheet' => [
                fn () => Uri::playerScoreSheet('rt-1', 'r-1', 'g-1', 'p-1'),
                $games.'/g-1/data/p-1',
                'Player score sheet',
            ],
        ];
    }

    #[DataProvider('uris')]
    public function test_each_uri_is_built_for_the_v3_api(Closure $call, string $expected_uri, string $expected_name): void
    {
        $uri = $call();

        self::assertSame($expected_uri, $uri['uri']);
        self::assertSame($expected_name, $uri['name']);
    }
}
