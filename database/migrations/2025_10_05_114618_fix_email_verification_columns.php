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
        // Check if email_verification_token column doesn't exist and add it
        if (!Schema::hasColumn('users', 'email_verification_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('email_verification_token', 60)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove email_verification_token column if it exists
        if (Schema::hasColumn('users', 'email_verification_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('email_verification_token');
            });
        }
    }
};
