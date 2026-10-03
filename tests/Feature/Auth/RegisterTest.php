<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\PartialRegistration;
use App\Notifications\CreatePassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private const REGISTER = 'api.test/v3/auth/register?send=false';

    private function created(): \GuzzleHttp\Promise\PromiseInterface
    {
        return Http::response([
            'id' => 'user-1',
            'uris' => ['create-password' => ['parameters' => ['token' => 'token-1', 'email' => 'ada@example.test']]],
        ], 201);
    }

    public function test_the_register_form_asks_for_a_name_and_email(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('action="'.route('register.action').'"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false);
    }

    public function test_registering_posts_the_account_to_the_api_with_the_internal_api_key(): void
    {
        Notification::fake();
        Http::fake([self::REGISTER => $this->created()]);

        $this->post('/register', ['name' => 'Ada', 'email' => 'ada@example.test']);

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === 'http://api.test/v3/auth/register?send=false'
            && $request->data() === ['name' => 'Ada', 'email' => 'ada@example.test', 'registered_via' => 'yatzy']
            && $request->header('X-Internal-Api-Key') === ['testing-internal-api-key']
            && $request->hasHeader('Authorization') === false);
    }

    public function test_the_api_accepts_the_registration_only_when_the_internal_key_matches(): void
    {
        Notification::fake();
        Http::fake([
            self::REGISTER => fn (Request $request) => $request->header('X-Internal-Api-Key') === ['testing-internal-api-key']
                ? $this->created()
                : Http::response(['message' => 'This route can only be called by a trusted internal service.'], 403),
        ]);

        $this->post('/register', ['name' => 'Ada', 'email' => 'ada@example.test'])
            ->assertRedirect(route('create-password.view'));

        config(['app.config.internal_key' => 'the-wrong-key']);

        $this->post('/register', ['name' => 'Ada', 'email' => 'ada@example.test'])
            ->assertRedirect(route('register.view'))
            ->assertSessionHas('authentication.failed', 'This route can only be called by a trusted internal service.');
    }

    public function test_a_registration_is_remembered_and_the_player_is_sent_to_create_their_password(): void
    {
        Notification::fake();
        Http::fake([self::REGISTER => $this->created()]);

        $this->post('/register', ['name' => 'Ada', 'email' => 'ada@example.test'])
            ->assertRedirect(route('create-password.view'))
            ->assertSessionHas('authentication.parameters', ['token' => 'token-1', 'email' => 'ada@example.test']);

        $this->assertDatabaseHas('partial_registration', ['token' => 'token-1', 'email' => 'ada@example.test']);
    }

    public function test_the_create_password_email_is_sent_to_the_player_a_few_minutes_later(): void
    {
        Notification::fake();
        Http::fake([self::REGISTER => $this->created()]);

        $this->post('/register', ['name' => 'Ada', 'email' => 'ada@example.test']);

        Notification::assertSentOnDemand(
            CreatePassword::class,
            fn (CreatePassword $notification, array $channels, object $notifiable) => $notifiable->routes['mail'] === 'ada@example.test'
                && $notification->delay->greaterThan(now()->addMinutes(4))
        );
    }

    public function test_validation_errors_from_the_api_come_back_to_the_form(): void
    {
        Notification::fake();
        Http::fake([self::REGISTER => Http::response([
            'message' => 'Validation error.',
            'fields' => ['email' => ['errors' => ['The email has already been taken.']]],
        ], 422)]);

        $this->post('/register', ['name' => 'Ada', 'email' => 'ada@example.test'])
            ->assertRedirect(route('register.view'))
            ->assertSessionHas('authentication.errors', ['email' => ['errors' => ['The email has already been taken.']]]);

        $this->assertDatabaseCount('partial_registration', 0);
        Notification::assertNothingSent();

        $this->followingRedirects()
            ->post('/register', ['name' => 'Ada', 'email' => 'ada@example.test'])
            ->assertSee('The email has already been taken.')
            ->assertSee('value="ada@example.test"', false);
    }

    public function test_an_api_failure_is_explained_on_the_form(): void
    {
        Notification::fake();
        Http::fake([self::REGISTER => Http::response(['message' => 'Registrations are closed'], 503)]);

        $this->followingRedirects()
            ->post('/register', ['name' => 'Ada', 'email' => 'ada@example.test'])
            ->assertOk()
            ->assertSee('We were unable to create your account')
            ->assertSee('Registrations are closed');

        $this->assertDatabaseCount('partial_registration', 0);
    }

    public function test_the_create_password_notification_is_only_sent_while_the_registration_is_still_pending(): void
    {
        $notification = new CreatePassword('ada@example.test', 'token-1');

        self::assertFalse($notification->shouldSend(new \stdClass(), 'mail'));

        PartialRegistration::query()->forceCreate(['token' => 'token-1', 'email' => 'ada@example.test']);

        self::assertTrue($notification->shouldSend(new \stdClass(), 'mail'));
    }

    public function test_the_create_password_email_links_to_the_create_password_page(): void
    {
        $mail = (new CreatePassword('ada+1@example.test', 'tok en'))->toMail(new \stdClass());

        self::assertSame('Yatzy Game Scorer: Create Password', $mail->subject);
        self::assertSame(
            url('/create-password').'?token=tok+en&email=ada%2B1%40example.test',
            $mail->actionUrl
        );
    }
}
