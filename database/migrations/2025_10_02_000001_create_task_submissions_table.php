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
        Schema::create('task_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('trade_tasks')->onDelete('cascade');
            $table->foreignId('submitted_by')->constrained('users')->onDelete('cascade');
            $table->text('submission_notes')->nullable();
            $table->json('file_paths')->nullable(); // Store multiple file paths as JSON
            $table->enum('file_types', ['image', 'video', 'document', 'mixed'])->default('mixed');
            $table->timestamp('submitted_at');
            $table->boolean('is_latest')->default(true); // Mark the latest submission
            $table->timestamps();
            
            $table->index(['task_id', 'is_latest']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('task_submissions');
    }
};
