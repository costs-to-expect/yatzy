<?php

declare(strict_types=1);

namespace Tests\Feature\Concerns;

use Closure;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\BuildsApiFixtures;

/**
 * Scaffolding for the tests of pages that read from, and write to, the API: a signed-in
 * player and a fake of the endpoints every signed-in page uses (the user, the resource type
 * and resource the app looks up for every request, and the players).
 *
 * The fake world is a resource type rt-1 with a resource r-1, the players Ada (p-1) and
 * Ben (p-2) and no games, a test fakes whatever else it needs. Faking is additive and the
 * first match wins, so a test passes its endpoints to fakeApi() as overrides, before
 * anything else fakes the defaults (signedIn() fakes the defaults if nothing has).
 */
trait FakesTheApi
{
    use BuildsApiFixtures;

    protected const USER_ID = 'user-1';

    protected const BEARER = 'test-bearer-token';

    protected const RESOURCE_TYPE_ID = 'rt-1';

    protected const RESOURCE_ID = 'r-1';

    private bool $apiFaked = false;

    /**
     * The URL of the games (items) endpoint, relative to the host, for use as a fake key.
     */
    protected function items(string $suffix = ''): string
    {
        return 'api.test/v3/resource-types/rt-1/resources/r-1/items'.$suffix;
    }

    /**
     * @param array<string, mixed> $overrides
     */
    protected function fakeApi(array $overrides = []): void
    {
        $this->apiFaked = true;

        Http::fake($overrides + [
            'api.test/v3/auth/user' => Http::response([
                'id' => self::USER_ID,
                'name' => 'Test Player',
                'email' => 'player@example.test',
            ], 200),
            'api.test/v3/resource-types?item-type=item-type-id' => Http::response([['id' => 'rt-1']], 200),
            'api.test/v3/resource-types/rt-1/resources?item-subtype=item-subtype-id' => Http::response([['id' => 'r-1']], 200),
            'api.test/v3/resource-types/rt-1/categories?collection=1' => Http::response(
                $this->playerCollection(['p-1' => 'Ada', 'p-2' => 'Ben']),
                200
            ),
        ]);
    }

    protected function signedIn(): static
    {
        if ($this->apiFaked === false) {
            $this->fakeApi();
        }

        return $this
            ->withCookie(config('app.config.cookie_user'), self::USER_ID)
            ->withCookie(config('app.config.cookie_bearer'), self::BEARER);
    }

    /**
     * Respond differently depending on the request method, for an endpoint that is read
     * with a GET and written with a POST, PATCH or DELETE.
     *
     * @param array<string, mixed> $responses method => Http::response() (or a closure given the request)
     */
    protected function byMethod(array $responses): Closure
    {
        return static function (Request $request) use ($responses) {
            $response = $responses[$request->method()] ?? Http::response(['message' => 'Not faked for '.$request->method()], 500);

            return $response instanceof Closure ? $response($request) : $response;
        };
    }

    /**
     * The requests sent to an exact API endpoint (as passed to items() or used as a fake key).
     *
     * @return list<Request>
     */
    protected function sentTo(string $method, string $endpoint): array
    {
        $sent = [];

        foreach (Http::recorded() as [$request]) {
            if ($request->method() === $method && $request->url() === 'http://'.$endpoint) {
                $sent[] = $request;
            }
        }

        return $sent;
    }

    /**
     * The requests sent to the API so far, filtered by method and a fragment of the URL.
     *
     * @return list<Request>
     */
    protected function sent(string $method, string $urlContains): array
    {
        $sent = [];

        foreach (Http::recorded() as [$request]) {
            if ($request->method() === $method && str_contains($request->url(), $urlContains)) {
                $sent[] = $request;
            }
        }

        return $sent;
    }
}
