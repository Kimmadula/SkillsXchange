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
        // Ensure the premium_price fee setting exists for admin management
        $exists = \Illuminate\Support\Facades\DB::table('trade_fee_settings')
            ->where('fee_type', 'premium_price')
            ->exists();

        if (!$exists) {
            \Illuminate\Support\Facades\DB::table('trade_fee_settings')->insert([
                'fee_type' => 'premium_price',
                'fee_amount' => 299, // default ₱299 per month
                'is_active' => true,
                'description' => 'Premium subscription price per month (in PHP pesos).',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Ensure the premium_duration_months fee setting exists for admin management
        $durationExists = \Illuminate\Support\Facades\DB::table('trade_fee_settings')
            ->where('fee_type', 'premium_duration_months')
            ->exists();

        if (!$durationExists) {
            \Illuminate\Support\Facades\DB::table('trade_fee_settings')->insert([
                'fee_type' => 'premium_duration_months',
                'fee_amount' => 1, // default 1 month
                'is_active' => true,
                'description' => 'Premium subscription duration in months (e.g., 1 = 1 month, 3 = 3 months).',
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
        // Optionally remove the seeded premium_price and premium_duration_months records on rollback
        \Illuminate\Support\Facades\DB::table('trade_fee_settings')
            ->where('fee_type', 'premium_price')
            ->delete();
        \Illuminate\Support\Facades\DB::table('trade_fee_settings')
            ->where('fee_type', 'premium_duration_months')
            ->delete();
    }
};
