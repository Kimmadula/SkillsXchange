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
        // Check if password_resets table exists and rename it
        if (Schema::hasTable('password_resets')) {
            Schema::rename('password_resets', 'password_reset_tokens');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Rename back to password_resets
        if (Schema::hasTable('password_reset_tokens')) {
            Schema::rename('password_reset_tokens', 'password_resets');
        }
    }
};
