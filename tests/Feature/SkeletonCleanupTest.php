<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Sign in is backed by the Costs to Expect API, the local users, Sanctum and the API routes of the
 * Laravel skeleton were never used and have been removed.
 */
class SkeletonCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_there_are_no_api_routes(): void
    {
        foreach (Route::getRoutes() as $route) {
            self::assertStringStartsNotWith('api/', $route->uri());
            self::assertStringNotContainsString('sanctum', $route->uri());
        }

        $this->getJson('/api/user')->assertNotFound();
    }

    public function test_sanctum_and_the_skeleton_user_are_gone(): void
    {
        self::assertFalse(class_exists('Laravel\Sanctum\Sanctum'));
        self::assertFalse(class_exists('App\Models\User'));
        self::assertNull(config('sanctum'));
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_10_03_000100_drop_unused_skeleton_tables.php');
    }

    public function test_the_migration_drops_the_empty_skeleton_tables(): void
    {
        // The test database has already been migrated, so bring the tables back as an old install has them
        $this->migration()->down();
        self::assertTrue(Schema::hasTable('users'));

        $this->migration()->up();

        foreach (['users', 'password_resets', 'personal_access_tokens'] as $table) {
            self::assertFalse(Schema::hasTable($table), "{$table} should have been dropped");
        }
        // The tables the app does use are untouched
        foreach (['sessions', 'jobs', 'failed_jobs', 'cache', 'share_token', 'partial_registration'] as $table) {
            self::assertTrue(Schema::hasTable($table), "{$table} should still exist");
        }
    }

    public function test_a_skeleton_table_with_rows_in_it_is_left_for_a_person_to_look_at(): void
    {
        $this->migration()->down();
        DB::table('users')->insert(['name' => 'Someone', 'email' => 'someone@example.test', 'password' => 'x']);

        $this->migration()->up();

        self::assertTrue(Schema::hasTable('users'));
        self::assertSame(1, DB::table('users')->count());
        self::assertFalse(Schema::hasTable('personal_access_tokens'));
    }

    public function test_the_migration_can_be_reversed(): void
    {
        self::assertFalse(Schema::hasTable('users'));

        $this->migration()->down();

        foreach (['users', 'password_resets', 'personal_access_tokens'] as $table) {
            self::assertTrue(Schema::hasTable($table), "{$table} should be back");
        }
    }
}
