<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fee_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('fee_type'); // 'trade_request_fee', 'trade_acceptance_fee', etc.
            $table->integer('amount'); // Token amount (negative for deductions, positive for refunds)
            $table->foreignId('trade_id')->nullable()->constrained()->onDelete('set null');
            $table->text('description')->nullable();
            $table->string('status')->default('completed'); // 'completed', 'failed', 'pending', 'refunded'
            $table->timestamps();

            $table->index(['user_id', 'fee_type']);
            $table->index(['trade_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_transactions');
    }
};
