<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\PartialRegistration;
use App\Notifications\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CreatePasswordTest extends TestCase
{
    use RefreshDatabase;

    private const CREATE_PASSWORD = 'api.test/v3/auth/create-password?token=token-1&email=ada%40example.test';

    private function passwords(array $overrides = []): array
    {
        return $overrides + [
            'token' => 'token-1',
            'email' => 'ada@example.test',
            'password' => 'a-long-enough-password',
            'password_confirmation' => 'a-long-enough-password',
        ];
    }

    public function test_the_page_is_built_from_the_registration_link_in_the_email(): void
    {
        $this->get('/create-password?token=token-1&email=ada@example.test')
            ->assertOk()
            ->assertSee('action="'.route('create-password.process.action').'"', false)
            ->assertSee('name="token" value="token-1"', false)
            ->assertSee('name="email" value="ada@example.test"', false);
    }

    public function test_the_page_is_built_from_the_registration_just_completed(): void
    {
        $this->withSession(['authentication.parameters' => ['token' => 'token-2', 'email' => 'ben@example.test']])
            ->get('/create-password')
            ->assertOk()
            ->assertSee('name="token" value="token-2"', false)
            ->assertSee('name="email" value="ben@example.test"', false);
    }

    public function test_the_page_does_not_exist_without_registration_details(): void
    {
        $this->get('/create-password')->assertNotFound();
        $this->get('/create-password?token=token-1')->assertNotFound();
    }

    public function test_setting_a_password_signs_the_player_in_and_tidies_up_the_registration(): void
    {
        Notification::fake();
        PartialRegistration::query()->forceCreate(['token' => 'token-1', 'email' => 'ada@example.test']);
        Http::fake([
            self::CREATE_PASSWORD => Http::response(null, 204),
            'api.test/v3/auth/login' => Http::response(['id' => 'user-1', 'type' => 'Bearer', 'token' => 'new-token'], 201),
        ]);

        $this->post('/create-password', $this->passwords())
            ->assertRedirect(route('home'))
            ->assertCookie('yatzy_bearer', 'new-token')
            ->assertCookie('yatzy_user', 'user-1');

        $this->assertDatabaseMissing('partial_registration', ['token' => 'token-1']);
        Notification::assertSentOnDemand(
            Registered::class,
            fn (Registered $notification, array $channels, object $notifiable) => $notifiable->routes['mail'] === 'ada@example.test'
        );

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/v3/auth/create-password')
            && $request->data() === ['password' => 'a-long-enough-password', 'password_confirmation' => 'a-long-enough-password']);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/v3/auth/login')
            && $request['email'] === 'ada@example.test');
    }

    public function test_the_player_is_told_to_sign_in_when_the_automatic_sign_in_fails(): void
    {
        Notification::fake();
        Http::fake([
            self::CREATE_PASSWORD => Http::response(null, 204),
            'api.test/v3/auth/login' => Http::response(['message' => 'Validation error.', 'fields' => ['email' => ['errors' => ['No match']]]], 422),
        ]);

        $this->post('/create-password', $this->passwords())
            ->assertRedirect(route('registration-complete'))
            ->assertCookieMissing('yatzy_bearer');

        $this->get('/registration-complete')->assertOk()->assertSee('All done!');
    }

    /**
     * The API answers a weak password with its own validation errors, which come back to
     * the form for the same registration link, with the field errors beside the inputs.
     */
    public function test_a_rejected_password_returns_to_the_form_for_the_same_registration(): void
    {
        Http::fake([self::CREATE_PASSWORD => Http::response([
            'message' => 'Validation error.',
            'fields' => ['password' => ['errors' => ['The password field must be at least 12 characters.']]],
        ], 422)]);

        $this->post('/create-password', $this->passwords(['password' => 'short', 'password_confirmation' => 'short']))
            ->assertRedirect(route('create-password.view', ['email' => 'ada@example.test', 'token' => 'token-1']))
            ->assertSessionHas('authentication.errors', ['password' => ['errors' => ['The password field must be at least 12 characters.']]]);
    }

    public function test_the_form_shows_the_errors_for_a_rejected_password_and_keeps_the_registration_link(): void
    {
        Http::fake([self::CREATE_PASSWORD => Http::response([
            'message' => 'Validation error.',
            'fields' => [
                'password' => ['errors' => ['The password field must be at least 12 characters.']],
                'password_confirmation' => ['errors' => ['The password confirmation does not match.']],
            ],
        ], 422)]);

        $this->followingRedirects()
            ->post('/create-password', $this->passwords(['password' => 'short']))
            ->assertOk()
            ->assertSee('The password field must be at least 12 characters.')
            ->assertSee('The password confirmation does not match.')
            ->assertSee('name="token" value="token-1"', false)
            ->assertSee('name="email" value="ada@example.test"', false);
    }

    public function test_an_invalid_registration_link_is_explained_on_the_form(): void
    {
        Notification::fake();
        Http::fake([self::CREATE_PASSWORD => Http::response(['message' => 'Sorry, the email and or token you supplied are invalid'], 404)]);

        $this->followingRedirects()
            ->post('/create-password', $this->passwords())
            ->assertOk()
            ->assertSee('Sorry, the email and or token you supplied are invalid');

        Notification::assertNothingSent();
    }

    public function test_the_passwords_are_never_flashed_back_into_the_session_or_the_page(): void
    {
        Http::fake([self::CREATE_PASSWORD => Http::response([
            'message' => 'Validation error.',
            'fields' => ['password' => ['errors' => ['Too short.']]],
        ], 422)]);

        $this->post('/create-password', $this->passwords(['password' => 'short-short', 'password_confirmation' => 'short-short']))
            ->assertSessionHasInput('email', 'ada@example.test')
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input.password_confirmation');

        $this->followingRedirects()
            ->post('/create-password', $this->passwords(['password' => 'short-short', 'password_confirmation' => 'short-short']))
            ->assertDontSee('short-short');
    }
}
