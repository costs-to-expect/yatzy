<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sessions', static function (Blueprint $table) {

            $table->dropIndex('sessions_user_id_index');
            $table->dropColumn('user_id');

        });

        Schema::table('sessions', static function (Blueprint $table) {

            $table->string('user_id')->nullable()->index()->after('id');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No down
    }
};
