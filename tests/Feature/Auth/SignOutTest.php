<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\FakesTheApi;
use Tests\TestCase;

class SignOutTest extends TestCase
{
    use FakesTheApi;

    public function test_signing_out_revokes_the_token_forgets_the_cookies_and_returns_to_the_landing_page(): void
    {
        $this->fakeApi(['api.test/v3/auth/logout' => Http::response(['message' => 'Account signed out'], 200)]);

        $this->signedIn()->get('/sign-out')
            ->assertRedirect(route('landing'))
            ->assertCookieExpired('yatzy_bearer')
            ->assertCookieExpired('yatzy_user');

        Http::assertSent(fn (Request $request) => $request->url() === 'http://api.test/v3/auth/logout'
            && $request->header('Authorization') === ['Bearer test-bearer-token']);
    }

    public function test_a_failure_revoking_the_token_still_signs_the_player_out(): void
    {
        $this->fakeApi(['api.test/v3/auth/logout' => Http::response(['message' => 'The API is down'], 503)]);

        $this->signedIn()->get('/sign-out')
            ->assertRedirect(route('landing'))
            ->assertCookieExpired('yatzy_bearer')
            ->assertCookieExpired('yatzy_user');
    }

    public function test_a_guest_signing_out_makes_no_api_request(): void
    {
        $this->get('/sign-out')->assertRedirect(route('landing'));

        Http::assertNothingSent();
    }
}
