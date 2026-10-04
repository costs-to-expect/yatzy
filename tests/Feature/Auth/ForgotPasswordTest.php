<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Notifications\ForgotPassword;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    private const FORGOT_PASSWORD = 'api.test/v3/auth/forgot-password?send=false';

    private function accepted(): \GuzzleHttp\Promise\PromiseInterface
    {
        return Http::response([
            'message' => 'Request received, please check your email for instructions on how to create your new password',
            'uris' => [
                'create-new-password' => [
                    'uri' => '/v3/auth/create-new-password?encrypted_token=encrypted-token&email=ada@example.test',
                    'method' => 'POST',
                    'parameters' => ['encrypted_token' => 'encrypted-token', 'email' => 'ada@example.test'],
                ],
            ],
        ], 201);
    }

    public function test_the_sign_in_page_links_to_the_forgot_password_page(): void
    {
        $this->get('/sign-in')
            ->assertOk()
            ->assertSee(route('forgot-password.view'), false);
    }

    public function test_the_forgot_password_form_asks_for_the_email_address(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Forgot your password?')
            ->assertSee('action="'.route('forgot-password.action').'"', false)
            ->assertSee('name="email"', false)
            ->assertSee('<meta name="robots" content="noindex,follow">', false);
    }

    public function test_the_request_is_sent_to_the_api_with_the_internal_api_key(): void
    {
        Notification::fake();
        Http::fake([self::FORGOT_PASSWORD => $this->accepted()]);

        $this->post('/forgot-password', ['email' => 'ada@example.test']);

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === 'http://api.test/v3/auth/forgot-password?send=false'
            && $request->data() === ['email' => 'ada@example.test']
            && $request->header('X-Internal-Api-Key') === ['testing-internal-api-key']
            && $request->hasHeader('Authorization') === false);
    }

    public function test_the_api_only_accepts_the_request_when_the_internal_key_matches(): void
    {
        Notification::fake();
        Http::fake([
            self::FORGOT_PASSWORD => fn (Request $request) => $request->header('X-Internal-Api-Key') === ['testing-internal-api-key']
                ? $this->accepted()
                : Http::response(['message' => 'This route can only be called by a trusted internal service.'], 403),
        ]);

        $this->post('/forgot-password', ['email' => 'ada@example.test'])
            ->assertRedirect(route('forgot-password.confirmation'));

        config(['app.config.internal_key' => 'the-wrong-key']);

        $this->post('/forgot-password', ['email' => 'ada@example.test'])
            ->assertRedirect(route('forgot-password.view'))
            ->assertSessionHas('authentication.failed', 'This route can only be called by a trusted internal service.');
    }

    public function test_the_player_is_emailed_a_link_to_create_a_new_password(): void
    {
        Notification::fake();
        Http::fake([self::FORGOT_PASSWORD => $this->accepted()]);

        $this->post('/forgot-password', ['email' => 'ada@example.test'])
            ->assertRedirect(route('forgot-password.confirmation'));

        Notification::assertSentOnDemand(
            ForgotPassword::class,
            fn (ForgotPassword $notification, array $channels, object $notifiable) => $notifiable->routes['mail'] === 'ada@example.test'
                && $notification->toMail($notifiable)->actionUrl === url('/create-new-password').'?encrypted_token=encrypted-token&email=ada%40example.test'
        );
    }

    public function test_the_email_says_what_it_is_for(): void
    {
        $mail = (new ForgotPassword('ada+1@example.test', 'a token+/='))->toMail(new \stdClass());

        self::assertSame('Yatzy Game Scorer: Create a new Password!', $mail->subject);
        self::assertSame('Create Password', $mail->actionText);
        self::assertSame(
            url('/create-new-password').'?encrypted_token=a+token%2B%2F%3D&email=ada%2B1%40example.test',
            $mail->actionUrl
        );
    }

    public function test_the_confirmation_page_tells_the_player_to_check_their_email(): void
    {
        $this->get('/forgot-password-confirmation')
            ->assertOk()
            ->assertSee('Email sent!')
            ->assertSee(route('forgot-password.view'), false);
    }

    public function test_an_account_the_api_cannot_find_is_explained_on_the_form(): void
    {
        Notification::fake();
        Http::fake([self::FORGOT_PASSWORD => Http::response(['message' => 'Unable to find your account, please try again later'], 404)]);

        $this->post('/forgot-password', ['email' => 'nobody@example.test'])
            ->assertRedirect(route('forgot-password.view'))
            ->assertSessionHas('authentication.errors', ['email' => ['errors' => ['Unable to find your account, please try again later']]]);

        Notification::assertNothingSent();

        $this->followingRedirects()
            ->post('/forgot-password', ['email' => 'nobody@example.test'])
            ->assertSee('Unable to find your account, please try again later')
            ->assertSee('value="nobody@example.test"', false);
    }

    public function test_validation_errors_from_the_api_come_back_to_the_form(): void
    {
        Notification::fake();
        Http::fake([self::FORGOT_PASSWORD => Http::response([
            'message' => 'Validation error.',
            'fields' => ['email' => ['errors' => ['The email field must be a valid email address.']]],
        ], 422)]);

        $this->followingRedirects()
            ->post('/forgot-password', ['email' => 'not-an-email'])
            ->assertSee('The email field must be a valid email address.');

        Notification::assertNothingSent();
    }

    public function test_an_email_address_is_needed_before_the_api_is_asked(): void
    {
        Notification::fake();

        $this->post('/forgot-password', [])
            ->assertRedirect(route('forgot-password.view'))
            ->assertSessionHas('authentication.errors', ['email' => ['errors' => ['Please enter your email address']]]);

        Http::assertNothingSent();
        Notification::assertNothingSent();
    }

    public function test_an_api_failure_is_explained_on_the_form(): void
    {
        Notification::fake();
        Http::fake([self::FORGOT_PASSWORD => Http::response(['message' => 'The API is down'], 503)]);

        $this->followingRedirects()
            ->post('/forgot-password', ['email' => 'ada@example.test'])
            ->assertOk()
            ->assertSee('We were unable to start your password reset')
            ->assertSee('The API is down');

        Notification::assertNothingSent();
    }
}
