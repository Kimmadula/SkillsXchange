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
        Schema::table('task_evaluations', function (Blueprint $table) {
            // Add grade field (letter grade like A, B, C, etc.)
            $table->string('grade', 5)->nullable()->after('score_percentage');
            
            // Add viewed_at timestamp to track when evaluator viewed the submission
            $table->timestamp('viewed_at')->nullable()->after('evaluated_at');
            
            // Add checked_at as alias for evaluated_at (for clarity in UI)
            // We'll use evaluated_at as checked_at, but add an index for better queries
            $table->index('evaluated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('task_evaluations', function (Blueprint $table) {
            $table->dropIndex(['evaluated_at']);
            $table->dropColumn(['grade', 'viewed_at']);
        });
    }
};
