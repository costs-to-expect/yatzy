<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The users, password_resets and personal_access_tokens tables come from the Laravel skeleton, sign in is backed by
 * the Costs to Expect API and nothing reads or writes them (the User model, Sanctum and the API routes are gone).
 *
 * A table is only dropped when it is empty, if one has rows someone put them there and a person should look first.
 */
return new class extends Migration
{
    private const TABLES = ['personal_access_tokens', 'password_resets', 'users'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && DB::table($table)->doesntExist()) {
                Schema::drop($table);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') === false) {
            Schema::create('users', static function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('password_resets') === false) {
            Schema::create('password_resets', static function (Blueprint $table) {
                $table->string('email')->index();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (Schema::hasTable('personal_access_tokens') === false) {
            Schema::create('personal_access_tokens', static function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
            });
        }
    }
};
