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
        Schema::table('trade_tasks', function (Blueprint $table) {
            // Add new fields for enhanced task management
            $table->json('associated_skills')->nullable()->after('due_date'); // Skills this task teaches
            $table->boolean('requires_submission')->default(false)->after('associated_skills');
            $table->enum('submission_type', ['file', 'text', 'both'])->default('both')->after('requires_submission');
            $table->text('submission_instructions')->nullable()->after('submission_type');
            $table->integer('max_score')->default(100)->after('submission_instructions'); // Maximum possible score
            $table->integer('passing_score')->default(70)->after('max_score'); // Minimum score to pass
            $table->enum('current_status', ['assigned', 'in_progress', 'submitted', 'evaluated', 'completed'])->default('assigned')->after('passing_score');
            $table->timestamp('started_at')->nullable()->after('current_status');
            $table->timestamp('submitted_at')->nullable()->after('started_at');
            $table->timestamp('evaluated_at')->nullable()->after('submitted_at');
            
            // Add indexes for better performance
            $table->index(['current_status', 'created_at']);
            $table->index(['assigned_to', 'current_status']);
            $table->index(['created_by', 'current_status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trade_tasks', function (Blueprint $table) {
            $table->dropIndex(['current_status', 'created_at']);
            $table->dropIndex(['assigned_to', 'current_status']);
            $table->dropIndex(['created_by', 'current_status']);
            
            $table->dropColumn([
                'associated_skills',
                'requires_submission',
                'submission_type',
                'submission_instructions',
                'max_score',
                'passing_score',
                'current_status',
                'started_at',
                'submitted_at',
                'evaluated_at'
            ]);
        });
    }
};
