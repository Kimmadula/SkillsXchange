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
        // Ensure the default_tokens_for_new_user fee setting exists for admin management
        $exists = \Illuminate\Support\Facades\DB::table('trade_fee_settings')
            ->where('fee_type', 'default_tokens_for_new_user')
            ->exists();

        if (!$exists) {
            \Illuminate\Support\Facades\DB::table('trade_fee_settings')->insert([
                'fee_type' => 'default_tokens_for_new_user',
                'fee_amount' => 0, // default 0 tokens for new users
                'is_active' => true,
                'description' => 'Default number of tokens given to new users upon registration.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Optionally remove the seeded default_tokens_for_new_user record on rollback
        \Illuminate\Support\Facades\DB::table('trade_fee_settings')
            ->where('fee_type', 'default_tokens_for_new_user')
            ->delete();
    }
};

