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
        Schema::create('task_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('trade_tasks')->onDelete('cascade');
            $table->foreignId('submission_id')->nullable()->constrained('task_submissions')->onDelete('cascade');
            $table->foreignId('evaluated_by')->constrained('users')->onDelete('cascade');
            $table->integer('score_percentage')->unsigned()->nullable(); // 0-100 percentage score
            $table->enum('status', ['pass', 'fail', 'needs_improvement', 'pending'])->default('pending');
            $table->text('feedback')->nullable();
            $table->text('improvement_notes')->nullable();
            $table->json('skills_to_add')->nullable(); // Skills to add to learner's profile if passed
            $table->boolean('skills_added')->default(false); // Track if skills were added
            $table->timestamp('evaluated_at');
            $table->timestamps();
            
            $table->index(['task_id', 'status']);
            $table->index(['evaluated_by', 'evaluated_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('task_evaluations');
    }
};
