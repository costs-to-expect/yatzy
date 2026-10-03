<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\DeleteAccount;
use App\Jobs\DeleteYatzyAccount;
use App\Notifications\ApiError;
use App\Notifications\Bye;
use App\Notifications\ByeBye;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Deleting an account is done in the background: the API is asked to delete, the browser
 * sessions of the player are removed and the player is emailed. The jobs run on the sync
 * queue in the tests, which gives them the same failure handling as a real queue worker.
 */
class DeleteAccountJobsTest extends TestCase
{
    use RefreshDatabase;

    private function createSession(string $id, string $user_id): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user_id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'tests',
            'payload' => 'payload',
            'last_activity' => time(),
        ]);
    }

    public function test_deleting_the_account_asks_the_api_removes_the_sessions_and_says_goodbye(): void
    {
        Notification::fake();
        $this->createSession('s-1', 'user-1');
        $this->createSession('s-2', 'user-1');
        $this->createSession('s-3', 'someone-else');
        Http::fake([
            'api.test/v3/auth/user/request-delete' => Http::response(['message' => 'Request received'], 201),
            'api.test/v3/auth/logout' => Http::response(['message' => 'Account signed out'], 200),
        ]);

        DeleteAccount::dispatch('the-bearer', 'user-1', 'ada@example.test');

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === 'http://api.test/v3/auth/user/request-delete'
            && $request->header('Authorization') === ['Bearer the-bearer']);

        $this->assertDatabaseMissing('sessions', ['user_id' => 'user-1']);
        $this->assertDatabaseHas('sessions', ['id' => 's-3']);
        Notification::assertSentOnDemand(ByeBye::class, fn (ByeBye $n, array $channels, object $notifiable) => $notifiable->routes['mail'] === 'ada@example.test');
        Notification::assertNotSentTo(new \Illuminate\Notifications\AnonymousNotifiable(), ApiError::class);
    }

    public function test_deleting_the_yatzy_account_asks_the_api_to_delete_the_resource(): void
    {
        Notification::fake();
        $this->createSession('s-1', 'user-1');
        Http::fake([
            'api.test/v3/auth/user/permitted-resource-types/rt-1/resources/r-1/request-delete' => Http::response(['message' => 'Request received'], 201),
            'api.test/v3/auth/logout' => Http::response(['message' => 'Account signed out'], 200),
        ]);

        DeleteYatzyAccount::dispatch('the-bearer', 'rt-1', 'r-1', 'user-1', 'ada@example.test');

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->header('Authorization') === ['Bearer the-bearer']);

        $this->assertDatabaseMissing('sessions', ['user_id' => 'user-1']);
        Notification::assertSentOnDemand(Bye::class, fn (Bye $n, array $channels, object $notifiable) => $notifiable->routes['mail'] === 'ada@example.test');
    }

    public function test_a_failed_account_deletion_is_reported_and_the_player_is_not_told_it_worked(): void
    {
        Notification::fake();
        $this->createSession('s-1', 'user-1');
        Http::fake([
            'api.test/v3/auth/user/request-delete' => Http::response(['message' => 'The API is down'], 503),
            'api.test/v3/auth/logout' => Http::response(['message' => 'Account signed out'], 200),
        ]);

        DeleteAccount::dispatch('the-bearer', 'user-1', 'ada@example.test');

        Notification::assertSentOnDemand(
            ApiError::class,
            fn (ApiError $notification, array $channels, object $notifiable) => $notifiable->routes['mail'] === 'errors@yatzy.test'
        );
        Notification::assertNotSentTo(new \Illuminate\Notifications\AnonymousNotifiable(), ByeBye::class);
        $this->assertDatabaseHas('sessions', ['user_id' => 'user-1']);
    }

    public function test_a_failed_yatzy_account_deletion_is_reported_and_the_player_is_not_told_it_worked(): void
    {
        Notification::fake();
        $this->createSession('s-1', 'user-1');
        Http::fake([
            'api.test/v3/auth/user/permitted-resource-types/rt-1/resources/r-1/request-delete' => Http::response(['message' => 'The API is down'], 503),
            'api.test/v3/auth/logout' => Http::response(['message' => 'Account signed out'], 200),
        ]);

        DeleteYatzyAccount::dispatch('the-bearer', 'rt-1', 'r-1', 'user-1', 'ada@example.test');

        Notification::assertSentOnDemand(ApiError::class);
        Notification::assertNotSentTo(new \Illuminate\Notifications\AnonymousNotifiable(), Bye::class);
        $this->assertDatabaseHas('sessions', ['user_id' => 'user-1']);
    }

    /**
     * The jobs carry the player's bearer token, anyone who can read the queue table could use it
     * to act as the player if the payload was readable.
     */
    public function test_the_queued_jobs_do_not_store_the_bearer_token_in_the_clear(): void
    {
        config(['queue.default' => 'database']);

        DeleteAccount::dispatch('secret-bearer-one', 'user-1', 'ada@example.test');
        DeleteYatzyAccount::dispatch('secret-bearer-two', 'rt-1', 'r-1', 'user-1', 'ada@example.test');

        $payloads = DB::table('jobs')->pluck('payload')->all();

        self::assertCount(2, $payloads);
        foreach ($payloads as $payload) {
            self::assertStringNotContainsString('secret-bearer', $payload);
            self::assertStringNotContainsString('ada@example.test', $payload);
        }
    }

    public function test_an_encrypted_job_still_runs_from_the_queue(): void
    {
        Notification::fake();
        Http::fake([
            'api.test/v3/auth/user/request-delete' => Http::response(['message' => 'Request received'], 201),
            'api.test/v3/auth/logout' => Http::response(['message' => 'Account signed out'], 200),
        ]);

        config(['queue.default' => 'database']);
        DeleteAccount::dispatch('secret-bearer-one', 'user-1', 'ada@example.test');

        $this->artisan('queue:work', ['--once' => true, '--stop-when-empty' => true])->assertSuccessful();

        Http::assertSent(fn (Request $request) => $request->header('Authorization') === ['Bearer secret-bearer-one']);
        Notification::assertSentOnDemand(ByeBye::class);
        $this->assertDatabaseCount('jobs', 0);
    }

    /**
     * @return list<string> the method and URL of each request sent to the API, in order
     */
    private function sentRequests(): array
    {
        return array_map(
            static fn (array $pair): string => $pair[0]->method().' '.$pair[0]->url(),
            Http::recorded()->all()
        );
    }

    public function test_the_token_is_revoked_once_the_account_deletion_has_been_requested(): void
    {
        Notification::fake();
        Http::fake([
            'api.test/v3/auth/user/request-delete' => Http::response(['message' => 'Request received'], 201),
            'api.test/v3/auth/logout' => Http::response(['message' => 'Account signed out'], 200),
        ]);

        DeleteAccount::dispatch('the-bearer', 'user-1', 'ada@example.test');

        // The delete request needs the token, so it is revoked after the request, never before
        self::assertSame(
            ['POST http://api.test/v3/auth/user/request-delete', 'GET http://api.test/v3/auth/logout'],
            $this->sentRequests()
        );
        Http::assertSent(fn (Request $request) => $request->url() === 'http://api.test/v3/auth/logout'
            && $request->header('Authorization') === ['Bearer the-bearer']);
    }

    public function test_the_token_is_revoked_once_the_yatzy_account_deletion_has_been_requested(): void
    {
        Notification::fake();
        Http::fake([
            'api.test/v3/auth/user/permitted-resource-types/rt-1/resources/r-1/request-delete' => Http::response(['message' => 'Request received'], 201),
            'api.test/v3/auth/logout' => Http::response(['message' => 'Account signed out'], 200),
        ]);

        DeleteYatzyAccount::dispatch('the-bearer', 'rt-1', 'r-1', 'user-1', 'ada@example.test');

        self::assertSame(
            [
                'POST http://api.test/v3/auth/user/permitted-resource-types/rt-1/resources/r-1/request-delete',
                'GET http://api.test/v3/auth/logout',
            ],
            $this->sentRequests()
        );
        Http::assertSent(fn (Request $request) => $request->url() === 'http://api.test/v3/auth/logout'
            && $request->header('Authorization') === ['Bearer the-bearer']);
    }

    public function test_the_token_is_revoked_even_when_the_deletion_request_fails(): void
    {
        // The player has already been signed out, nothing should be left holding a valid token
        Notification::fake();
        Http::fake([
            'api.test/v3/auth/user/request-delete' => Http::response(['message' => 'The API is down'], 503),
            'api.test/v3/auth/logout' => Http::response(['message' => 'Account signed out'], 200),
        ]);

        DeleteAccount::dispatch('the-bearer', 'user-1', 'ada@example.test');

        Http::assertSent(fn (Request $request) => $request->url() === 'http://api.test/v3/auth/logout');
    }

    public function test_a_token_that_cannot_be_revoked_does_not_stop_the_player_being_told(): void
    {
        Notification::fake();
        Http::fake([
            'api.test/v3/auth/user/request-delete' => Http::response(['message' => 'Request received'], 201),
            'api.test/v3/auth/logout' => Http::response(['message' => 'The API is down'], 503),
        ]);

        DeleteAccount::dispatch('the-bearer', 'user-1', 'ada@example.test');

        Notification::assertSentOnDemand(ByeBye::class);
    }

    public function test_a_token_revoke_that_throws_does_not_stop_the_player_being_told(): void
    {
        Notification::fake();
        Http::fake([
            'api.test/v3/auth/user/permitted-resource-types/rt-1/resources/r-1/request-delete' => Http::response(['message' => 'Request received'], 201),
            'api.test/v3/auth/logout' => static fn () => throw new \Illuminate\Http\Client\ConnectionException('Timed out'),
        ]);

        DeleteYatzyAccount::dispatch('the-bearer', 'rt-1', 'r-1', 'user-1', 'ada@example.test');

        Notification::assertSentOnDemand(Bye::class);
    }
}
