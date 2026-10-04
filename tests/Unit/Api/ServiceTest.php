<?php

declare(strict_types=1);

namespace Tests\Unit\Api;

use App\Api\Service;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\BuildsApiFixtures;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use BuildsApiFixtures;

    private const GAMES = 'api.test/v3/resource-types/rt-1/resources/r-1/items';

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['*' => Http::response(['id' => 'abc'], 201)]);
    }

    private function lastRequest(): Request
    {
        $recorded = Http::recorded();

        return $recorded->last()[0];
    }

    public function test_register_posts_the_new_account_to_the_internal_register_route(): void
    {
        (new Service())->register(['name' => 'Ada', 'email' => 'ada@example.test']);

        $request = $this->lastRequest();

        self::assertSame('POST', $request->method());
        self::assertSame('http://api.test/v3/auth/register?send=false', $request->url());
        self::assertSame(['name' => 'Ada', 'email' => 'ada@example.test', 'registered_via' => 'yatzy'], $request->data());
        self::assertSame(['testing-internal-api-key'], $request->header('X-Internal-Api-Key'));
    }

    public function test_every_other_auth_request_is_sent_without_the_internal_api_key(): void
    {
        $service = new Service('token');

        $service->authSignIn('ada@example.test', 'secret');
        $service->createPassword(['token' => 't', 'email' => 'e', 'password' => 'p', 'password_confirmation' => 'p']);
        $service->getAuthUser();
        $service->requestAccountDelete();

        Http::assertSentCount(4);
        Http::assertNotSent(fn (Request $request) => $request->hasHeader('X-Internal-Api-Key'));
    }

    public function test_sign_in_names_the_device_after_the_app_and_flags_local_environments(): void
    {
        $service = new Service();

        $service->authSignIn('ada@example.test', 'secret');
        self::assertSame(
            ['email' => 'ada@example.test', 'password' => 'secret', 'device_name' => 'yatzy:'],
            $this->lastRequest()->data()
        );

        $this->app['env'] = 'local';
        $service->authSignIn('ada@example.test', 'secret');
        self::assertSame('yatzy:local:', $this->lastRequest()->data()['device_name']);
    }

    public function test_create_password_posts_the_passwords_to_the_registration_link(): void
    {
        (new Service())->createPassword([
            'token' => 'a b',
            'email' => 'ada+1@example.test',
            'password' => 'correct horse battery',
            'password_confirmation' => 'correct horse battery',
        ]);

        $request = $this->lastRequest();

        self::assertSame('http://api.test/v3/auth/create-password?token=a+b&email=ada%2B1%40example.test', $request->url());
        self::assertSame(
            ['password' => 'correct horse battery', 'password_confirmation' => 'correct horse battery'],
            $request->data()
        );
    }

    public function test_the_resource_type_and_resource_are_created_with_the_configured_item_types(): void
    {
        $service = new Service('token');

        $service->createResourceType();
        self::assertSame('http://api.test/v3/resource-types', $this->lastRequest()->url());
        self::assertSame('item-type-id', $this->lastRequest()['item_type_id']);

        $service->createResource('rt-1');
        self::assertSame('http://api.test/v3/resource-types/rt-1/resources', $this->lastRequest()->url());
        self::assertSame('item-subtype-id', $this->lastRequest()['item_subtype_id']);
    }

    public function test_players_and_games_are_created_with_their_name_and_description(): void
    {
        $service = new Service('token');

        $service->createPlayer('rt-1', 'Ada', 'A player');
        self::assertSame('http://api.test/v3/resource-types/rt-1/categories', $this->lastRequest()->url());
        self::assertSame(['name' => 'Ada', 'description' => 'A player'], $this->lastRequest()->data());

        $service->createGame('rt-1', 'r-1', 'Yatzy game', 'A game');
        self::assertSame('http://'.self::GAMES, $this->lastRequest()->url());
        self::assertSame(['name' => 'Yatzy game', 'description' => 'A game'], $this->lastRequest()->data());
    }

    public function test_a_player_is_added_to_a_game_as_one_of_its_categories(): void
    {
        (new Service('token'))->addPlayerToGame('rt-1', 'r-1', 'g-1', 'p-1');

        self::assertSame('http://'.self::GAMES.'/g-1/categories', $this->lastRequest()->url());
        self::assertSame(['category_id' => 'p-1'], $this->lastRequest()->data());
    }

    public function test_a_new_score_sheet_is_an_empty_sheet_stored_against_the_player_id(): void
    {
        (new Service('token'))->addScoreSheetForPlayer('rt-1', 'r-1', 'g-1', 'p-1');

        $request = $this->lastRequest();

        self::assertSame('http://'.self::GAMES.'/g-1/data', $request->url());
        self::assertSame('p-1', $request['key']);
        self::assertSame(
            [
                'upper-section' => [],
                'lower-section' => [],
                'score' => ['upper' => 0, 'bonus' => 0, 'lower' => 0, 'total' => 0],
            ],
            json_decode($request['value'], true)
        );
    }

    public function test_a_score_sheet_is_updated_by_patching_the_json_encoded_sheet(): void
    {
        $sheet = $this->scoreSheet(['ones' => 3], ['chance' => 20]);

        (new Service('token'))->updateScoreSheetForPlayer('rt-1', 'r-1', 'g-1', 'p-1', $sheet);

        $request = $this->lastRequest();

        self::assertSame('PATCH', $request->method());
        self::assertSame('http://'.self::GAMES.'/g-1/data/p-1', $request->url());
        self::assertSame($sheet, json_decode($request['value'], true));
    }

    public function test_a_log_message_carries_its_parameters_as_json(): void
    {
        (new Service('token'))->createGameLogMessage('rt-1', 'r-1', 'g-1', 'Scored 3 in their Ones', ['dice' => 'ones']);

        $request = $this->lastRequest();

        self::assertSame('http://'.self::GAMES.'/g-1/log', $request->url());
        self::assertSame('Scored 3 in their Ones', $request['message']);
        self::assertSame(['dice' => 'ones'], json_decode($request['parameters'], true));
    }

    public function test_a_game_is_updated_by_patching_the_game(): void
    {
        (new Service('token'))->updateGame('rt-1', 'r-1', 'g-1', ['complete' => 1]);

        self::assertSame('PATCH', $this->lastRequest()->method());
        self::assertSame('http://'.self::GAMES.'/g-1', $this->lastRequest()->url());
        self::assertSame(['complete' => 1], $this->lastRequest()->data());
    }

    public function test_deletes_use_the_delete_verb_on_the_matching_resource(): void
    {
        $service = new Service('token');

        $service->deleteGame('rt-1', 'r-1', 'g-1');
        self::assertSame(['DELETE', 'http://'.self::GAMES.'/g-1'], [$this->lastRequest()->method(), $this->lastRequest()->url()]);

        $service->deleteAssignedGamePlayer('rt-1', 'r-1', 'g-1', 'p-1');
        self::assertSame(['DELETE', 'http://'.self::GAMES.'/g-1/categories/p-1'], [$this->lastRequest()->method(), $this->lastRequest()->url()]);

        $service->deletePlayerScoreSheet('rt-1', 'r-1', 'g-1', 'p-1');
        self::assertSame(['DELETE', 'http://'.self::GAMES.'/g-1/data/p-1'], [$this->lastRequest()->method(), $this->lastRequest()->url()]);
    }

    public function test_account_and_yatzy_account_deletion_are_requested_with_an_empty_post(): void
    {
        $service = new Service('token');

        $service->requestAccountDelete();
        self::assertSame(['POST', 'http://api.test/v3/auth/user/request-delete', []], [
            $this->lastRequest()->method(), $this->lastRequest()->url(), $this->lastRequest()->data(),
        ]);

        $service->requestResourceDelete('rt-1', 'r-1');
        self::assertSame(
            ['POST', 'http://api.test/v3/auth/user/permitted-resource-types/rt-1/resources/r-1/request-delete', []],
            [$this->lastRequest()->method(), $this->lastRequest()->url(), $this->lastRequest()->data()]
        );
    }

    public function test_game_collections_skip_the_api_cache_when_asked_or_when_fetching_complete_games(): void
    {
        $service = new Service('token');

        $service->getGames('rt-1', 'r-1', ['complete' => 0]);
        self::assertFalse($this->lastRequest()->hasHeader('X-Skip-Cache'));

        $service->getGames('rt-1', 'r-1', ['complete' => 1]);
        self::assertTrue($this->lastRequest()->hasHeader('X-Skip-Cache'));

        $service->getGames('rt-1', 'r-1', ['complete' => 0], skip_cache: true);
        self::assertTrue($this->lastRequest()->hasHeader('X-Skip-Cache'));
    }

    public function test_the_lookups_the_app_makes_for_every_request_always_skip_the_api_cache(): void
    {
        $service = new Service('token');

        $service->getResourceTypes(['item-type' => 'item-type-id']);
        self::assertTrue($this->lastRequest()->hasHeader('X-Skip-Cache'));

        $service->getResources('rt-1', ['item-subtype' => 'item-subtype-id']);
        self::assertTrue($this->lastRequest()->hasHeader('X-Skip-Cache'));

        $service->getPlayers('rt-1', ['collection' => true]);
        self::assertTrue($this->lastRequest()->hasHeader('X-Skip-Cache'));
    }

    public function test_single_resource_reads_are_cacheable(): void
    {
        $service = new Service('token');

        $service->getGame('rt-1', 'r-1', 'g-1', ['include-players' => 1]);
        self::assertSame('http://'.self::GAMES.'/g-1?include-players=1', $this->lastRequest()->url());
        self::assertFalse($this->lastRequest()->hasHeader('X-Skip-Cache'));

        $service->getPlayerScoreSheet('rt-1', 'r-1', 'g-1', 'p-1');
        self::assertSame('http://'.self::GAMES.'/g-1/data/p-1', $this->lastRequest()->url());
        self::assertFalse($this->lastRequest()->hasHeader('X-Skip-Cache'));

        $service->getGameScoreSheets('rt-1', 'r-1', 'g-1');
        self::assertSame('http://'.self::GAMES.'/g-1/data', $this->lastRequest()->url());

        $service->getAssignedGamePlayers('rt-1', 'r-1', 'g-1');
        self::assertSame('http://'.self::GAMES.'/g-1/categories', $this->lastRequest()->url());
    }
}
