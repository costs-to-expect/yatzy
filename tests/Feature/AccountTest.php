<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\DeleteAccount;
use App\Jobs\DeleteYatzyAccount;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use ReflectionProperty;
use Tests\Feature\Concerns\FakesTheApi;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use FakesTheApi;

    private function property(object $job, string $name): mixed
    {
        return (new ReflectionProperty($job, $name))->getValue($job);
    }

    public function test_the_account_page_shows_the_name_and_email_of_the_player(): void
    {
        $this->signedIn()->get('/account')
            ->assertOk()
            ->assertSee('Your account')
            ->assertSee('Test Player')
            ->assertSee('player@example.test')
            ->assertSee(route('account.confirm-delete-yatzy-account'), false)
            ->assertSee(route('account.confirm-delete-account'), false);
    }

    public function test_the_account_page_is_a_404_when_the_account_cannot_be_fetched(): void
    {
        $this->fakeApi(['api.test/v3/auth/user' => Http::response(['message' => 'Unauthenticated'], 401)]);

        $this->signedIn()->get('/account')->assertNotFound();
    }

    public function test_the_delete_confirmation_pages_show_what_will_be_deleted(): void
    {
        $this->signedIn()->get('/account/confirm-delete-yatzy-account')
            ->assertOk()
            ->assertSee('Delete Yatzy account')
            ->assertSee('Data that will be deleted')
            ->assertSee('action="'.route('account.delete-yatzy-account.action').'"', false);

        $this->signedIn()->get('/account/confirm-delete-account')
            ->assertOk()
            ->assertSee('Delete account')
            ->assertSee('action="'.route('account.delete-account.action').'"', false);
    }

    public function test_deleting_the_yatzy_account_queues_a_job_and_signs_the_player_out(): void
    {
        Queue::fake();

        $this->signedIn()
            ->post('/account/delete-yatzy-account')
            ->assertRedirect(route('account', ['job' => 'delete-yatzy-account']));

        Queue::assertPushed(DeleteYatzyAccount::class, function (DeleteYatzyAccount $job) {
            self::assertSame('test-bearer-token', $this->property($job, 'bearer_token'));
            self::assertSame('rt-1', $this->property($job, 'resource_type_id'));
            self::assertSame('r-1', $this->property($job, 'resource_id'));
            self::assertSame('user-1', $this->property($job, 'user_id'));
            self::assertSame('player@example.test', $this->property($job, 'email'));
            self::assertTrue($job->delay->greaterThan(now()), 'the job is delayed a few seconds');

            return true;
        });
        Queue::assertNotPushed(DeleteAccount::class);
    }

    public function test_deleting_the_whole_account_queues_a_job(): void
    {
        Queue::fake();

        $this->signedIn()
            ->post('/account/delete-account')
            ->assertRedirect(route('account', ['job' => 'delete-account']));

        Queue::assertPushed(DeleteAccount::class, function (DeleteAccount $job) {
            self::assertSame('test-bearer-token', $this->property($job, 'bearer_token'));
            self::assertSame('user-1', $this->property($job, 'user_id'));
            self::assertSame('player@example.test', $this->property($job, 'email'));

            return true;
        });
        Queue::assertNotPushed(DeleteYatzyAccount::class);
    }

    public function test_no_job_is_queued_when_the_account_cannot_be_fetched(): void
    {
        Queue::fake();
        $this->fakeApi(['api.test/v3/auth/user' => Http::response(['message' => 'Unauthenticated'], 401)]);

        $this->signedIn()->post('/account/delete-account')->assertNotFound();
        $this->signedIn()->post('/account/delete-yatzy-account')->assertNotFound();

        Queue::assertNothingPushed();
    }

    public function test_after_a_delete_the_account_page_explains_what_is_happening_and_signs_the_player_out(): void
    {
        $this->fakeApi(['api.test/v3/auth/logout' => Http::response(['message' => 'Account signed out'], 200)]);

        $this->signedIn()->get('/account?job=delete-account')
            ->assertOk()
            ->assertSee('Delete started!')
            ->assertSee('A job has been added to delete your account')
            ->assertCookieExpired('yatzy_bearer')
            ->assertCookieExpired('yatzy_user');

        // The queued job still has to use the player's token, so it is not revoked
        Http::assertNotSent(fn (Request $request) => str_ends_with($request->url(), '/v3/auth/logout'));

        $this->signedIn()->get('/account?job=delete-yatzy-account')
            ->assertOk()
            ->assertSee('A job has been added to delete your Yatzy account');
    }

    public function test_an_ordinary_visit_to_the_account_page_keeps_the_player_signed_in(): void
    {
        $this->signedIn()->get('/account')
            ->assertOk()
            ->assertCookieMissing('yatzy_bearer');
    }
}
