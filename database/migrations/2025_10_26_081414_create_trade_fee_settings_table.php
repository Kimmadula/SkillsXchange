<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trade_fee_settings', function (Blueprint $table) {
            $table->id();
            $table->string('fee_type')->unique(); // 'trade_request', 'trade_acceptance', etc.
            $table->integer('fee_amount')->default(1); // Token amount
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default fee settings
        DB::table('trade_fee_settings')->insert([
            [
                'fee_type' => 'trade_request',
                'fee_amount' => 1,
                'is_active' => true,
                'description' => 'Fee charged when requesting a trade',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'fee_type' => 'trade_acceptance',
                'fee_amount' => 1,
                'is_active' => true,
                'description' => 'Fee charged when accepting a trade request',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trade_fee_settings');
    }
};
