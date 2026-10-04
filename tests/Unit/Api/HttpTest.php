<?php

declare(strict_types=1);

namespace Tests\Unit\Api;

use App\Api\Http;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http as HttpFacade;
use Tests\TestCase;

class HttpTest extends TestCase
{
    public function test_get_returns_the_status_content_and_headers_of_a_200(): void
    {
        HttpFacade::fake(['api.test/v3/thing' => HttpFacade::response(['id' => 'abc'], 200, ['X-Total-Count' => '7'])]);

        $response = (new Http('token'))->get('/v3/thing');

        self::assertSame(200, $response['status']);
        self::assertSame(['id' => 'abc'], $response['content']);
        self::assertSame(['7'], $response['headers']['X-Total-Count']);
    }

    public function test_get_returns_the_api_message_for_a_404(): void
    {
        HttpFacade::fake(['api.test/v3/thing' => HttpFacade::response(['message' => 'Not found'], 404)]);

        $response = (new Http('token'))->get('/v3/thing');

        self::assertSame(['status' => 404, 'content' => 'Not found'], $response);
    }

    public function test_get_wraps_any_other_status_in_a_generic_message_which_includes_the_api_message(): void
    {
        HttpFacade::fake(['api.test/v3/thing' => HttpFacade::response(['message' => 'Broken'], 500)]);

        $response = (new Http('token'))->get('/v3/thing');

        self::assertSame(500, $response['status']);
        self::assertSame('We encountered an unknown error contacting the API [Broken]', $response['content']);
    }

    public function test_get_only_asks_the_api_to_skip_its_cache_when_told_to(): void
    {
        HttpFacade::fake(['*' => HttpFacade::response([], 200)]);

        $http = new Http('token');
        $http->get('/v3/cached');
        $http->get('/v3/fresh', true);

        HttpFacade::assertSent(fn (Request $request) => str_ends_with($request->url(), '/v3/cached') && $request->hasHeader('X-Skip-Cache') === false);
        HttpFacade::assertSent(fn (Request $request) => str_ends_with($request->url(), '/v3/fresh') && $request->header('X-Skip-Cache') === ['true']);
    }

    public function test_requests_ask_for_json_and_carry_the_bearer_when_there_is_one(): void
    {
        HttpFacade::fake(['*' => HttpFacade::response([], 200)]);

        (new Http('my-token'))->get('/v3/signed-in');
        (new Http())->get('/v3/anonymous');

        HttpFacade::assertSent(fn (Request $request) => str_ends_with($request->url(), '/v3/signed-in')
            && $request->header('Authorization') === ['Bearer my-token']
            && $request->header('Accept') === ['application/json']
            && $request->header('Content-Type') === ['application/json']);
        HttpFacade::assertSent(fn (Request $request) => str_ends_with($request->url(), '/v3/anonymous')
            && $request->hasHeader('Authorization') === false
            && $request->header('Accept') === ['application/json']);
    }

    public function test_the_dev_api_url_is_only_used_in_dev_mode(): void
    {
        HttpFacade::fake(['*' => HttpFacade::response([], 200)]);

        config(['app.config.api_url' => 'http://live.api.test', 'app.config.api_url_dev' => 'http://dev.api.test']);

        config(['app.config.dev' => false]);
        (new Http())->get('/v3/thing');

        config(['app.config.dev' => true]);
        (new Http())->get('/v3/thing');

        HttpFacade::assertSent(fn (Request $request) => $request->url() === 'http://live.api.test/v3/thing');
        HttpFacade::assertSent(fn (Request $request) => $request->url() === 'http://dev.api.test/v3/thing');
    }

    public function test_delete_returns_no_content_for_a_204_and_the_message_for_anything_else(): void
    {
        HttpFacade::fake([
            'api.test/v3/gone' => HttpFacade::response(null, 204),
            'api.test/v3/locked' => HttpFacade::response(['message' => 'Forbidden'], 403),
        ]);

        $http = new Http('token');

        self::assertSame(['status' => 204, 'content' => null], $http->delete('/v3/gone'));
        self::assertSame(['status' => 403, 'content' => 'Forbidden'], $http->delete('/v3/locked'));
    }

    public function test_patch_sends_the_payload_and_maps_the_status(): void
    {
        HttpFacade::fake([
            'api.test/v3/ok' => HttpFacade::response(null, 204),
            'api.test/v3/invalid' => HttpFacade::response(['message' => 'Validation error', 'fields' => ['name' => ['errors' => ['Required']]]], 422),
            'api.test/v3/missing' => HttpFacade::response(['message' => 'Not found'], 404),
            'api.test/v3/odd' => HttpFacade::response(['message' => 'Teapot'], 418),
        ]);

        $http = new Http('token');

        self::assertSame(['status' => 204, 'content' => null], $http->patch('/v3/ok', ['value' => 'a']));
        self::assertSame(
            ['status' => 422, 'content' => 'Validation error', 'fields' => ['name' => ['errors' => ['Required']]]],
            $http->patch('/v3/invalid', [])
        );
        self::assertSame(['status' => 404, 'content' => 'Not found'], $http->patch('/v3/missing', []));
        self::assertSame(['status' => 418, 'content' => 'We encountered an unknown error contacting the API'], $http->patch('/v3/odd', []));

        HttpFacade::assertSent(fn (Request $request) => $request->method() === 'PATCH'
            && str_ends_with($request->url(), '/v3/ok')
            && $request['value'] === 'a');
    }

    public function test_post_sends_the_payload_and_maps_the_status(): void
    {
        HttpFacade::fake([
            'api.test/v3/created' => HttpFacade::response(['id' => 'new'], 201),
            'api.test/v3/accepted' => HttpFacade::response(null, 204),
            'api.test/v3/denied' => HttpFacade::response(['message' => 'Unauthorised'], 401),
            'api.test/v3/invalid' => HttpFacade::response(['message' => 'Validation error', 'fields' => ['email' => ['errors' => ['Required']]]], 422),
            'api.test/v3/odd' => HttpFacade::response(['message' => 'Teapot'], 418),
        ]);

        $http = new Http('token');

        self::assertSame(['status' => 201, 'content' => ['id' => 'new']], $http->post('/v3/created', ['name' => 'a']));
        self::assertSame(['status' => 204, 'content' => null], $http->post('/v3/accepted', []));
        self::assertSame(['status' => 401, 'content' => 'Unauthorised'], $http->post('/v3/denied', []));
        self::assertSame(
            ['status' => 422, 'content' => 'Validation error', 'fields' => ['email' => ['errors' => ['Required']]]],
            $http->post('/v3/invalid', [])
        );
        self::assertSame(['status' => 418, 'content' => 'We encountered an unknown error contacting the API'], $http->post('/v3/odd', []));

        HttpFacade::assertSent(fn (Request $request) => $request->method() === 'POST' && str_ends_with($request->url(), '/v3/created') && $request['name'] === 'a');
    }

    public function test_post_only_sends_the_internal_api_key_to_internal_routes(): void
    {
        HttpFacade::fake(['*' => HttpFacade::response([], 201)]);

        $http = new Http();
        $http->post('/v3/public', []);
        $http->post('/v3/internal', [], internal: true);

        HttpFacade::assertSent(fn (Request $request) => str_ends_with($request->url(), '/v3/public')
            && $request->hasHeader('X-Internal-Api-Key') === false);
        HttpFacade::assertSent(fn (Request $request) => str_ends_with($request->url(), '/v3/internal')
            && $request->header('X-Internal-Api-Key') === ['testing-internal-api-key']);
    }

    public function test_the_internal_api_key_is_never_added_to_other_methods(): void
    {
        HttpFacade::fake(['*' => HttpFacade::response([], 200)]);

        $http = new Http('token');
        $http->get('/v3/thing');
        $http->patch('/v3/thing', []);
        $http->delete('/v3/thing');

        HttpFacade::assertSentCount(3);
        HttpFacade::assertNotSent(fn (Request $request) => $request->hasHeader('X-Internal-Api-Key'));
    }
}
