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
        Schema::create('skill_acquisition_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('skill_id');
            $table->foreign('skill_id')->references('skill_id')->on('skills')->onDelete('cascade');
            $table->foreignId('task_id')->nullable()->constrained('trade_tasks')->onDelete('set null');
            $table->foreignId('trade_id')->nullable()->constrained('trades')->onDelete('set null');
            $table->enum('acquisition_method', ['task_completion', 'manual_add', 'trade_completion', 'verification'])->default('task_completion');
            $table->text('notes')->nullable();
            $table->integer('score_achieved')->nullable(); // Score when acquired through task
            $table->timestamp('acquired_at');
            $table->timestamps();
            
            $table->index(['user_id', 'acquired_at']);
            $table->index(['skill_id', 'acquired_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('skill_acquisition_history');
    }
};
