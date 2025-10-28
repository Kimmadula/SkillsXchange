<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure the token_price fee setting exists for admin management
        $exists = DB::table('trade_fee_settings')
            ->where('fee_type', 'token_price')
            ->exists();

        if (!$exists) {
            DB::table('trade_fee_settings')->insert([
                'fee_type' => 'token_price',
                'fee_amount' => 5, // default ₱5 per token
                'is_active' => true,
                'description' => 'Price per token (in PHP pesos).',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally remove the seeded token_price record on rollback
        DB::table('trade_fee_settings')
            ->where('fee_type', 'token_price')
            ->delete();
    }
};


