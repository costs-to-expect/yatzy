<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Auth\Guard\Api\Guard;
use App\Auth\Guard\Api\User;
use App\Auth\Guard\Api\UserProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The guard has no local users. A player is signed in when the browser has the bearer and user
 * cookies set when they signed in, the user is whoever the API says owns the bearer.
 */
class GuardTest extends TestCase
{
    private array $config = [
        'cookie_bearer' => 'yatzy_bearer',
        'cookie_user' => 'yatzy_user',
    ];

    private function makeGuard(Request $request): Guard
    {
        return new Guard(new UserProvider($this->config, $request), $this->config, $request);
    }

    private function request(array $cookies = []): Request
    {
        return Request::create('/', 'GET', [], $cookies);
    }

    private function signedInRequest(): Request
    {
        return $this->request(['yatzy_bearer' => 'the-bearer', 'yatzy_user' => 'user-1']);
    }

    public function test_a_player_is_signed_in_only_when_both_cookies_are_present(): void
    {
        $guard = $this->makeGuard($this->signedInRequest());
        self::assertTrue($guard->check());
        self::assertFalse($guard->guest());

        $bearer_only = $this->makeGuard($this->request(['yatzy_bearer' => 'the-bearer']));
        self::assertFalse($bearer_only->check());
        self::assertTrue($bearer_only->guest());

        $user_only = $this->makeGuard($this->request(['yatzy_user' => 'user-1']));
        self::assertFalse($user_only->check());

        self::assertFalse($this->makeGuard($this->request())->check());
    }

    public function test_the_id_is_the_user_cookie_only_for_a_signed_in_player(): void
    {
        self::assertSame('user-1', $this->makeGuard($this->signedInRequest())->id());
        self::assertNull($this->makeGuard($this->request())->id());
        self::assertNull($this->makeGuard($this->request(['yatzy_user' => 'user-1']))->id());
    }

    public function test_the_user_is_the_account_the_api_has_for_the_bearer_token_and_is_only_fetched_once(): void
    {
        Http::fake(['api.test/v3/auth/user' => Http::response(['id' => 'user-1', 'name' => 'Ada', 'email' => 'ada@example.test'], 200)]);

        $guard = $this->makeGuard($this->signedInRequest());

        self::assertFalse($guard->hasUser());

        $user = $guard->user();

        self::assertInstanceOf(User::class, $user);
        self::assertSame('user-1', $user->id);
        self::assertSame('Ada', $user->name);
        self::assertSame('ada@example.test', $user->email);
        self::assertTrue($guard->hasUser());

        self::assertSame($user, $guard->user());

        Http::assertSentCount(1);
        Http::assertSent(fn (ClientRequest $request) => $request->header('Authorization') === ['Bearer the-bearer']);
    }

    public function test_there_is_no_user_when_the_api_does_not_recognise_the_bearer_token(): void
    {
        Http::fake(['api.test/v3/auth/user' => Http::response(['message' => 'Unauthenticated'], 401)]);

        $guard = $this->makeGuard($this->signedInRequest());

        self::assertNull($guard->user());
        self::assertFalse($guard->hasUser());
    }

    public function test_there_is_no_user_and_no_api_request_for_a_guest(): void
    {
        $guard = $this->makeGuard($this->request());

        self::assertNull($guard->user());
        self::assertNull($this->makeGuard($this->request(['yatzy_bearer' => 'the-bearer']))->user());

        Http::assertNothingSent();
    }

    public function test_a_user_can_be_set_explicitly(): void
    {
        $user = new User();
        $user->id = 'user-9';

        $guard = $this->makeGuard($this->request())->setUser($user);

        self::assertSame($user, $guard->user());
        self::assertTrue($guard->hasUser());
        Http::assertNothingSent();
    }

    public function test_validating_credentials_signs_in_with_the_api_and_queues_the_cookies(): void
    {
        Http::fake(['api.test/v3/auth/login' => Http::response(['id' => 'user-1', 'type' => 'Bearer', 'token' => 'new-token'], 201)]);

        // A stale bearer from an earlier sign-in must not be sent to the public sign-in route
        $request = $this->request(['yatzy_bearer' => 'stale-bearer', 'yatzy_user' => 'user-0']);

        self::assertTrue($this->makeGuard($request)->validate(['email' => 'ada@example.test', 'password' => 'secret-secret']));

        self::assertSame('new-token', Cookie::queued('yatzy_bearer')->getValue());
        self::assertSame('user-1', Cookie::queued('yatzy_user')->getValue());
        Http::assertSent(fn (ClientRequest $request) => $request->hasHeader('Authorization') === false);
    }

    public function test_the_cookies_last_for_the_session_unless_the_player_asked_to_be_remembered(): void
    {
        Http::fake(['api.test/v3/auth/login' => Http::response(['id' => 'user-1', 'type' => 'Bearer', 'token' => 'new-token'], 201)]);

        $this->makeGuard($this->request())->attempt(['email' => 'ada@example.test', 'password' => 'secret-secret']);
        self::assertSame(0, Cookie::queued('yatzy_bearer')->getExpiresTime());

        $this->makeGuard($this->request())->attempt(['email' => 'ada@example.test', 'password' => 'secret-secret'], true);
        self::assertEqualsWithDelta(time() + (43200 * 60), Cookie::queued('yatzy_bearer')->getExpiresTime(), 5);
        self::assertEqualsWithDelta(time() + (43200 * 60), Cookie::queued('yatzy_user')->getExpiresTime(), 5);
    }

    public function test_validating_credentials_the_api_rejects_returns_false_with_the_apis_errors(): void
    {
        Http::fake(['api.test/v3/auth/login' => Http::response([
            'message' => 'Validation error.',
            'fields' => ['email' => ['errors' => ['These credentials do not match our records.']]],
        ], 422)]);

        $guard = $this->makeGuard($this->request());

        self::assertFalse($guard->validate(['email' => 'ada@example.test', 'password' => 'bad-password-bad']));
        self::assertSame(['email' => ['errors' => ['These credentials do not match our records.']]], $guard->errors());
        self::assertNull(Cookie::queued('yatzy_bearer'));
    }

    public function test_an_unauthorised_sign_in_has_the_same_error_shape_as_every_other_error(): void
    {
        Http::fake(['api.test/v3/auth/login' => Http::response(['message' => 'Unauthorised'], 401)]);

        $guard = $this->makeGuard($this->request());

        self::assertFalse($guard->validate(['email' => 'ada@example.test', 'password' => 'bad-password-bad']));
        self::assertSame(['email' => ['errors' => ['Unauthorised']]], $guard->errors());
    }

    public function test_any_other_sign_in_failure_is_false_without_errors(): void
    {
        Http::fake(['api.test/v3/auth/login' => Http::response(['message' => 'The API is down'], 503)]);

        $guard = $this->makeGuard($this->request());

        self::assertFalse($guard->validate(['email' => 'ada@example.test', 'password' => 'secret-secret']));
        self::assertSame([], $guard->errors());
    }

    public function test_missing_credentials_are_never_sent_to_the_api(): void
    {
        $guard = $this->makeGuard($this->request());

        self::assertFalse($guard->validate(['email' => null, 'password' => 'secret-secret']));
        self::assertSame(['email' => ['errors' => ['You need to provide your email address']]], $guard->errors());

        $guard = $this->makeGuard($this->request());

        self::assertFalse($guard->validate(['email' => 'ada@example.test']));
        self::assertSame(['password' => ['errors' => ['You need to provide your password']]], $guard->errors());

        Http::assertNothingSent();
    }

    public function test_signing_out_revokes_the_bearer_token_in_the_api_and_forgets_the_cookies(): void
    {
        Http::fake(['api.test/v3/auth/logout' => Http::response(['message' => 'Account signed out'], 200)]);

        $guard = $this->makeGuard($this->signedInRequest());
        $guard->setUser(new User());

        $guard->logout();

        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'GET'
            && $request->url() === 'http://api.test/v3/auth/logout'
            && $request->header('Authorization') === ['Bearer the-bearer']);

        self::assertLessThan(time(), Cookie::queued('yatzy_bearer')->getExpiresTime());
        self::assertLessThan(time(), Cookie::queued('yatzy_user')->getExpiresTime());
        self::assertFalse($guard->hasUser());
    }

    public function test_the_token_can_be_left_valid_for_a_job_that_still_needs_it(): void
    {
        $guard = $this->makeGuard($this->signedInRequest());

        $guard->logout(false);

        Http::assertNothingSent();
        self::assertLessThan(time(), Cookie::queued('yatzy_bearer')->getExpiresTime());
        self::assertLessThan(time(), Cookie::queued('yatzy_user')->getExpiresTime());
    }

    public function test_signing_out_without_a_bearer_does_not_call_the_api(): void
    {
        $this->makeGuard($this->request())->logout();

        Http::assertNothingSent();
        self::assertLessThan(time(), Cookie::queued('yatzy_bearer')->getExpiresTime());
    }

    public function test_a_failure_revoking_the_token_never_stops_the_player_signing_out(): void
    {
        Exceptions::fake();
        Http::fake(['api.test/v3/auth/logout' => fn () => throw new ConnectionException('Connection failed')]);

        $this->makeGuard($this->signedInRequest())->logout();

        self::assertLessThan(time(), Cookie::queued('yatzy_bearer')->getExpiresTime());
        self::assertLessThan(time(), Cookie::queued('yatzy_user')->getExpiresTime());
        Exceptions::assertReported(ConnectionException::class);
    }

    public function test_the_provider_has_nothing_to_do_for_password_hashing_or_remember_tokens(): void
    {
        $provider = new UserProvider($this->config, $this->request());
        $user = new User();

        self::assertNull($provider->retrieveByCredentials(['email' => 'ada@example.test']));
        self::assertNull($provider->validateCredentials($user, ['password' => 'secret']));
        self::assertNull($provider->rehashPasswordIfRequired($user, ['password' => 'secret']));
        self::assertNull($provider->updateRememberToken($user, 'token'));
    }

    public function test_the_provider_retrieves_by_token_in_the_same_way_as_by_id(): void
    {
        Http::fake(['api.test/v3/auth/user' => Http::response(['id' => 'user-1', 'name' => 'Ada', 'email' => 'ada@example.test'], 200)]);

        $provider = new UserProvider($this->config, $this->signedInRequest());

        self::assertSame('user-1', $provider->retrieveByToken('user-1', 'ignored')->id);
    }
}
