<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\FakesTheApi;
use Tests\TestCase;

/**
 * Every signed-in request starts by finding the player's Yatzy resource type and resource
 * in the API, creating them the first time the player visits. The players page needs nothing
 * else, so it is the page used to exercise it.
 */
class BootstrapTest extends TestCase
{
    use FakesTheApi;

    private const RESOURCE_TYPES = 'api.test/v3/resource-types?item-type=item-type-id';

    private const RESOURCE_TYPES_CREATE = 'api.test/v3/resource-types';

    public function test_an_existing_resource_type_and_resource_are_used_as_they_are(): void
    {
        $this->signedIn()->get('/players')->assertOk();

        Http::assertNotSent(fn (Request $request) => $request->method() === 'POST');
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/v3/resource-types/rt-1/categories?collection=1'));
    }

    public function test_a_first_time_player_gets_a_resource_type_and_resource_created(): void
    {
        $this->fakeApi([
            self::RESOURCE_TYPES => Http::response([], 200),
            self::RESOURCE_TYPES_CREATE => Http::response(['id' => 'rt-new'], 201),
            'api.test/v3/resource-types/rt-new/resources' => Http::response(['id' => 'r-new'], 201),
            'api.test/v3/resource-types/rt-new/categories?collection=1' => Http::response([], 200),
        ]);

        $this->signedIn()->get('/players')->assertOk();

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === 'http://api.test/v3/resource-types'
            && $request['name'] === 'Game trackers'
            && $request['item_type_id'] === 'item-type-id');
        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === 'http://api.test/v3/resource-types/rt-new/resources'
            && $request['name'] === 'Yatzy game tracker'
            && $request['item_subtype_id'] === 'item-subtype-id');
    }

    public function test_a_player_with_a_resource_type_but_no_resource_only_gets_a_resource_created(): void
    {
        $this->fakeApi([
            'api.test/v3/resource-types/rt-1/resources?item-subtype=item-subtype-id' => Http::response([], 200),
            'api.test/v3/resource-types/rt-1/resources' => Http::response(['id' => 'r-new'], 201),
        ]);

        $this->signedIn()->get('/players')->assertOk();

        Http::assertSent(fn (Request $request) => $request->method() === 'POST' && str_ends_with($request->url(), '/v3/resource-types/rt-1/resources'));
        Http::assertNotSent(fn (Request $request) => $request->method() === 'POST' && str_ends_with($request->url(), '/v3/resource-types'));
    }

    public function test_an_api_failure_finding_the_resource_type_is_passed_on(): void
    {
        $this->fakeApi([self::RESOURCE_TYPES => Http::response(['message' => 'The API is down'], 503)]);

        $this->signedIn()->get('/players')->assertStatus(503);
    }

    public function test_an_api_failure_finding_the_resource_is_passed_on(): void
    {
        $this->fakeApi(['api.test/v3/resource-types/rt-1/resources?item-subtype=item-subtype-id' => Http::response(['message' => 'The API is down'], 503)]);

        $this->signedIn()->get('/players')->assertStatus(503);
    }

    public function test_an_api_failure_creating_the_resource_type_is_passed_on(): void
    {
        $this->fakeApi([
            self::RESOURCE_TYPES => Http::response([], 200),
            self::RESOURCE_TYPES_CREATE => Http::response(['message' => 'Not allowed'], 403),
        ]);

        $this->signedIn()->get('/players')->assertForbidden();
    }

    public function test_an_api_failure_creating_the_resource_is_passed_on(): void
    {
        $this->fakeApi([
            'api.test/v3/resource-types/rt-1/resources?item-subtype=item-subtype-id' => Http::response([], 200),
            'api.test/v3/resource-types/rt-1/resources' => Http::response(['message' => 'The API is down'], 503),
        ]);

        $this->signedIn()->get('/players')->assertStatus(503);
    }

    public function test_an_api_failure_creating_the_resource_after_creating_the_resource_type_is_passed_on(): void
    {
        $this->fakeApi([
            self::RESOURCE_TYPES => Http::response([], 200),
            self::RESOURCE_TYPES_CREATE => Http::response(['id' => 'rt-new'], 201),
            'api.test/v3/resource-types/rt-new/resources' => Http::response(['message' => 'The API is down'], 503),
        ]);

        $this->signedIn()->get('/players')->assertStatus(503);
    }
}
