<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A share link's parameters hold the owner's bearer token in the clear, anyone who could read the table could
 * act as the owner until the game was completed. Encrypt them with the application key.
 *
 * Encrypted values are not JSON, so the json column becomes text (MySQL refuses anything else in a json column).
 * The ShareToken model reads either form, so a row that is still plain JSON keeps working.
 *
 * Changing APP_KEY makes the stored parameters unreadable, the links of games in progress stop working (links only
 * live until the game is completed) so change it between games.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('share_token', static function (Blueprint $table) {
            $table->text('parameters')->change();
        });

        DB::table('share_token')->orderBy('token')->each(static function (object $row): void {
            try {
                Crypt::decryptString($row->parameters);

                return; // already encrypted
            } catch (DecryptException) {
                // Plain JSON, encrypt it
            }

            DB::table('share_token')
                ->where('token', $row->token)
                ->update(['parameters' => Crypt::encryptString($row->parameters)]);
        });
    }

    public function down(): void
    {
        DB::table('share_token')->orderBy('token')->each(static function (object $row): void {
            try {
                $json = Crypt::decryptString($row->parameters);
            } catch (DecryptException) {
                return; // already plain
            }

            DB::table('share_token')
                ->where('token', $row->token)
                ->update(['parameters' => $json]);
        });

        Schema::table('share_token', static function (Blueprint $table) {
            $table->json('parameters')->change();
        });
    }
};
