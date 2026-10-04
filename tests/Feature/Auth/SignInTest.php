<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SignInTest extends TestCase
{
    private function fakeLogin(int $status = 201, ?array $body = null): void
    {
        Http::fake([
            'api.test/v3/auth/login' => Http::response(
                $body ?? ['id' => 'user-1', 'type' => 'Bearer', 'token' => 'new-token'],
                $status
            ),
        ]);
    }

    public function test_the_sign_in_form_posts_the_credentials_and_offers_remember_me(): void
    {
        $this->get('/sign-in')
            ->assertOk()
            ->assertSee('action="'.route('sign-in.action').'"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee(route('register.view'), false);
    }

    /**
     * The stay signed-in checkbox only works if the browser submits it, an input without a
     * name is never part of the form submission.
     */
    public function test_the_remember_me_checkbox_is_part_of_the_form_submission(): void
    {
        $this->get('/sign-in')
            ->assertOk()
            ->assertSee('id="remember_me" name="remember_me"', false);
    }

    public function test_signing_in_sets_the_bearer_and_user_cookies_and_goes_home(): void
    {
        $this->fakeLogin();

        $this->post('/sign-in', ['email' => 'ada@example.test', 'password' => 'a-long-enough-password'])
            ->assertRedirect(route('home'))
            ->assertCookie('yatzy_bearer', 'new-token')
            ->assertCookie('yatzy_user', 'user-1');

        Http::assertSent(fn (Request $request) => $request->url() === 'http://api.test/v3/auth/login'
            && $request['email'] === 'ada@example.test'
            && $request['password'] === 'a-long-enough-password'
            && $request['device_name'] === 'yatzy:'
            && $request->hasHeader('Authorization') === false
            && $request->hasHeader('X-Internal-Api-Key') === false);
    }

    public function test_the_cookies_only_last_for_the_browser_session_by_default(): void
    {
        $this->fakeLogin();

        $response = $this->post('/sign-in', ['email' => 'ada@example.test', 'password' => 'a-long-enough-password']);

        self::assertSame(0, $response->getCookie('yatzy_bearer', false)->getExpiresTime());
        self::assertSame(0, $response->getCookie('yatzy_user', false)->getExpiresTime());
    }

    public function test_remember_me_keeps_the_cookies_for_thirty_days(): void
    {
        $this->fakeLogin();

        $response = $this->post('/sign-in', [
            'email' => 'ada@example.test',
            'password' => 'a-long-enough-password',
            'remember_me' => 'on',
        ]);

        $expected = time() + (43200 * 60);

        self::assertEqualsWithDelta($expected, $response->getCookie('yatzy_bearer', false)->getExpiresTime(), 5);
        self::assertEqualsWithDelta($expected, $response->getCookie('yatzy_user', false)->getExpiresTime(), 5);
    }

    public function test_credentials_the_api_rejects_return_to_the_form_with_the_reason(): void
    {
        $this->fakeLogin(422, [
            'message' => 'Validation error.',
            'fields' => ['email' => ['errors' => ['These credentials do not match our records.']]],
        ]);

        $this->post('/sign-in', ['email' => 'ada@example.test', 'password' => 'a-long-enough-password'])
            ->assertRedirect(route('sign-in.view'))
            ->assertSessionHas('authentication.errors', ['email' => ['errors' => ['These credentials do not match our records.']]])
            ->assertCookieMissing('yatzy_bearer')
            ->assertCookieMissing('yatzy_user');

        $this->followingRedirects()
            ->post('/sign-in', ['email' => 'ada@example.test', 'password' => 'a-long-enough-password'])
            ->assertOk()
            ->assertSee('These credentials do not match our records.');
    }

    public function test_an_unauthorised_response_is_shown_on_the_form_not_as_a_server_error(): void
    {
        $this->fakeLogin(401, ['message' => 'Unauthorised']);

        $this->followingRedirects()
            ->post('/sign-in', ['email' => 'ada@example.test', 'password' => 'a-long-enough-password'])
            ->assertOk()
            ->assertSee('Unauthorised');
    }

    public function test_missing_credentials_never_reach_the_api(): void
    {
        $this->post('/sign-in', ['email' => 'ada@example.test'])
            ->assertRedirect(route('sign-in.view'))
            ->assertSessionHas('authentication.errors', ['password' => ['errors' => ['You need to provide your password']]]);

        $this->post('/sign-in', [])
            ->assertRedirect(route('sign-in.view'))
            ->assertSessionHas('authentication.errors', [
                'email' => ['errors' => ['You need to provide your email address']],
                'password' => ['errors' => ['You need to provide your password']],
            ]);

        Http::assertNothingSent();
    }

    public function test_the_email_is_kept_when_the_form_comes_back_with_errors(): void
    {
        $this->fakeLogin(422, ['message' => 'Validation error.', 'fields' => ['email' => ['errors' => ['No match']]]]);

        $this->followingRedirects()
            ->post('/sign-in', ['email' => 'ada@example.test', 'password' => 'a-long-enough-password'])
            ->assertSee('value="ada@example.test"', false)
            ->assertDontSee('a-long-enough-password');
    }

    public function test_the_password_is_never_flashed_back_into_the_session(): void
    {
        $this->fakeLogin(422, ['message' => 'Validation error.', 'fields' => ['email' => ['errors' => ['No match']]]]);

        $this->post('/sign-in', ['email' => 'ada@example.test', 'password' => 'a-long-enough-password'])
            ->assertSessionHasInput('email', 'ada@example.test')
            ->assertSessionMissing('_old_input.password');
    }
}
