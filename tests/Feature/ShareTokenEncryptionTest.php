<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ShareToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A share link carries the owner's bearer token, so it is encrypted in the table: anyone who can read
 * the database, but does not have the application key, learns nothing that lets them act as the owner.
 */
class ShareTokenEncryptionTest extends TestCase
{
    use RefreshDatabase;

    private const PARAMETERS = [
        'resource_type_id' => 'rt-1',
        'resource_id' => 'r-1',
        'game_id' => 'g-1',
        'player_id' => 'p-1',
        'player_name' => 'Ada',
        'owner_bearer' => 'super-secret-owner-bearer',
    ];

    private function rawParameters(string $token): string
    {
        return (string) DB::table('share_token')->where('token', $token)->value('parameters');
    }

    private function insertPlain(string $token, array $parameters = self::PARAMETERS): void
    {
        DB::table('share_token')->insert([
            'token' => $token,
            'game_id' => 'g-1',
            'player_id' => 'p-1',
            'parameters' => json_encode($parameters, JSON_THROW_ON_ERROR),
        ]);
    }

    public function test_the_parameters_are_not_stored_in_the_clear(): void
    {
        ShareToken::issue('rt-1', 'r-1', 'g-1', 'p-1', 'Ada', 'super-secret-owner-bearer');
        $token = (string) ShareToken::query()->value('token');

        $raw = $this->rawParameters($token);

        self::assertStringNotContainsString('super-secret-owner-bearer', $raw);
        self::assertStringNotContainsString('rt-1', $raw);
        self::assertStringNotContainsString('Ada', $raw);
        self::assertSame(self::PARAMETERS, json_decode(Crypt::decryptString($raw), true, 512, JSON_THROW_ON_ERROR));
    }

    public function test_the_parameters_read_back_as_the_array_that_was_stored(): void
    {
        ShareToken::issue('rt-1', 'r-1', 'g-1', 'p-1', 'Ada', 'super-secret-owner-bearer');

        self::assertSame(self::PARAMETERS, ShareToken::query()->firstOrFail()->parameters);
    }

    public function test_the_parameters_are_never_serialised_with_the_model(): void
    {
        ShareToken::issue('rt-1', 'r-1', 'g-1', 'p-1', 'Ada', 'super-secret-owner-bearer');

        $json = ShareToken::query()->firstOrFail()->toJson();

        self::assertStringNotContainsString('super-secret-owner-bearer', $json);
        self::assertStringNotContainsString('parameters', $json);
    }

    public function test_a_row_that_is_still_plain_json_is_read_and_encrypted_when_saved_again(): void
    {
        $this->insertPlain('legacy-token');

        $share = ShareToken::query()->findOrFail('legacy-token');
        self::assertSame(self::PARAMETERS, $share->parameters);

        $share->parameters = $share->parameters;
        $share->save();

        self::assertStringNotContainsString('super-secret-owner-bearer', $this->rawParameters('legacy-token'));
        self::assertSame(self::PARAMETERS, ShareToken::query()->findOrFail('legacy-token')->parameters);
    }

    public function test_parameters_that_cannot_be_read_are_a_server_error_not_a_crash_with_the_secret(): void
    {
        DB::table('share_token')->insert([
            'token' => 'broken',
            'game_id' => 'g-1',
            'player_id' => 'p-1',
            'parameters' => Crypt::encryptString('not json'),
        ]);

        $this->get('/public/score-sheet/broken')->assertStatus(500);
    }

    public function test_a_row_encrypted_with_another_application_key_is_a_server_error(): void
    {
        $other_key = new \Illuminate\Encryption\Encrypter(random_bytes(32), 'AES-256-CBC');
        DB::table('share_token')->insert([
            'token' => 'rotated',
            'game_id' => 'g-1',
            'player_id' => 'p-1',
            'parameters' => $other_key->encryptString(json_encode(self::PARAMETERS)),
        ]);

        $this->get('/public/score-sheet/rotated')->assertStatus(500);
    }

    public function test_a_json_string_is_validated_rather_than_stored_unreadable(): void
    {
        $this->expectException(\JsonException::class);

        $share = new ShareToken();
        $share->parameters = '{not json';
    }

    public function test_the_migration_encrypts_the_rows_that_are_plain_json_and_leaves_the_rest(): void
    {
        $this->insertPlain('plain-1');
        $this->insertPlain('plain-2', ['owner_bearer' => 'second-secret'] + self::PARAMETERS);
        ShareToken::issue('rt-1', 'r-1', 'g-1', 'p-3', 'Cy', 'third-secret');
        $encrypted_token = (string) ShareToken::query()->where('player_id', 'p-3')->value('token');
        $encrypted_before = $this->rawParameters($encrypted_token);

        $migration = require database_path('migrations/2026_10_03_000000_encrypt_share_token_parameters.php');
        $migration->up();

        foreach (['plain-1', 'plain-2'] as $token) {
            self::assertStringNotContainsString('secret', $this->rawParameters($token));
        }
        self::assertSame('super-secret-owner-bearer', ShareToken::query()->findOrFail('plain-1')->parameters['owner_bearer']);
        self::assertSame('second-secret', ShareToken::query()->findOrFail('plain-2')->parameters['owner_bearer']);
        self::assertSame($encrypted_before, $this->rawParameters($encrypted_token), 'An encrypted row is not encrypted twice');
    }

    public function test_the_migration_can_be_reversed(): void
    {
        ShareToken::issue('rt-1', 'r-1', 'g-1', 'p-1', 'Ada', 'super-secret-owner-bearer');
        $token = (string) ShareToken::query()->value('token');

        $migration = require database_path('migrations/2026_10_03_000000_encrypt_share_token_parameters.php');
        $migration->down();

        self::assertSame(self::PARAMETERS, json_decode($this->rawParameters($token), true, 512, JSON_THROW_ON_ERROR));
    }

    public function test_the_tokens_can_be_limited_to_some_games(): void
    {
        foreach ([['t-1', 'g-1', 'p-1'], ['t-2', 'g-2', 'p-1'], ['t-3', 'g-3', 'p-2']] as [$token, $game, $player]) {
            DB::table('share_token')->insert(['token' => $token, 'game_id' => $game, 'player_id' => $player, 'parameters' => '{}']);
        }

        self::assertSame(
            ['g-1' => ['p-1' => 't-1'], 'g-3' => ['p-2' => 't-3']],
            (new ShareToken())->getShareTokens(['g-1', 'g-3'])
        );
        self::assertSame([], (new ShareToken())->getShareTokens([]));
    }
}
