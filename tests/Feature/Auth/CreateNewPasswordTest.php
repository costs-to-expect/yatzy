<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CreateNewPasswordTest extends TestCase
{
    private const CREATE_NEW_PASSWORD = 'api.test/v3/auth/create-new-password?encrypted_token=encrypted-token&email=ada%40example.test';

    private function passwords(array $overrides = []): array
    {
        return $overrides + [
            'encrypted_token' => 'encrypted-token',
            'email' => 'ada@example.test',
            'password' => 'a-long-enough-password',
            'password_confirmation' => 'a-long-enough-password',
        ];
    }

    public function test_the_page_is_built_from_the_link_in_the_email(): void
    {
        $this->get('/create-new-password?encrypted_token=encrypted-token&email=ada@example.test')
            ->assertOk()
            ->assertSee('Create a new password')
            ->assertSee('action="'.route('create-new-password.action').'"', false)
            ->assertSee('name="encrypted_token" value="encrypted-token"', false)
            ->assertSee('name="email" value="ada@example.test"', false)
            ->assertSee('<meta name="robots" content="noindex,follow">', false);
    }

    public function test_the_page_does_not_exist_without_the_link_parameters(): void
    {
        $this->get('/create-new-password')->assertNotFound();
        $this->get('/create-new-password?email=ada@example.test')->assertNotFound();
        $this->get('/create-new-password?encrypted_token=encrypted-token')->assertNotFound();
    }

    public function test_the_new_password_is_sent_to_the_api_for_the_link_in_the_email(): void
    {
        Http::fake([self::CREATE_NEW_PASSWORD => Http::response(null, 204)]);

        $this->post('/create-new-password', $this->passwords())
            ->assertRedirect(route('create-new-password.confirmation'));

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === 'http://api.test/v3/auth/create-new-password?encrypted_token=encrypted-token&email=ada%40example.test'
            && $request->data() === ['password' => 'a-long-enough-password', 'password_confirmation' => 'a-long-enough-password']
            && $request->hasHeader('X-Internal-Api-Key') === false);
    }

    public function test_the_confirmation_page_invites_the_player_to_sign_in(): void
    {
        $this->get('/create-new-password-confirmation')
            ->assertOk()
            ->assertSee('Password created!')
            ->assertSee(route('sign-in.view'), false);
    }

    public function test_a_rejected_password_returns_to_the_form_for_the_same_link_with_the_errors(): void
    {
        Http::fake([self::CREATE_NEW_PASSWORD => Http::response([
            'message' => 'Validation error.',
            'fields' => [
                'password' => ['errors' => ['The password field must be at least 12 characters.']],
                'password_confirmation' => ['errors' => ['The password confirmation does not match.']],
            ],
        ], 422)]);

        $this->post('/create-new-password', $this->passwords(['password' => 'short']))
            ->assertRedirect(route('create-new-password.view', ['encrypted_token' => 'encrypted-token', 'email' => 'ada@example.test']));

        $this->followingRedirects()
            ->post('/create-new-password', $this->passwords(['password' => 'short']))
            ->assertOk()
            ->assertSee('The password field must be at least 12 characters.')
            ->assertSee('The password confirmation does not match.')
            ->assertSee('name="encrypted_token" value="encrypted-token"', false)
            ->assertSee('name="email" value="ada@example.test"', false);
    }

    public function test_the_password_is_never_flashed_back_into_the_session_or_the_page(): void
    {
        Http::fake([self::CREATE_NEW_PASSWORD => Http::response([
            'message' => 'Validation error.',
            'fields' => ['password' => ['errors' => ['Too short.']]],
        ], 422)]);

        $this->post('/create-new-password', $this->passwords(['password' => 'short-short']))
            ->assertSessionMissing('_old_input');

        $this->followingRedirects()
            ->post('/create-new-password', $this->passwords(['password' => 'short-short']))
            ->assertDontSee('short-short');
    }

    public function test_an_invalid_link_is_explained_on_the_form(): void
    {
        Http::fake([self::CREATE_NEW_PASSWORD => Http::response(['message' => 'Sorry, the email and or token you supplied are invalid'], 404)]);

        $this->followingRedirects()
            ->post('/create-new-password', $this->passwords())
            ->assertOk()
            ->assertSee('Sorry, the email and or token you supplied are invalid')
            ->assertSee(route('forgot-password.view'), false);
    }

    public function test_errors_about_the_link_itself_offer_a_new_link(): void
    {
        Http::fake([self::CREATE_NEW_PASSWORD => Http::response([
            'message' => 'Validation error.',
            'fields' => ['encrypted_token' => ['errors' => ['The encrypted token field is required.']]],
        ], 422)]);

        $this->followingRedirects()
            ->post('/create-new-password', $this->passwords())
            ->assertSee('The link you followed is not valid')
            ->assertSee(route('forgot-password.view'), false);
    }
}
