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
            // Add file type requirements
            $table->json('allowed_file_types')->nullable()->after('submission_instructions');
            $table->boolean('strict_file_types')->default(false)->after('allowed_file_types');
            
            // Add indexes for better performance
            $table->index(['requires_submission', 'current_status']);
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
            $table->dropIndex(['requires_submission', 'current_status']);
            $table->dropColumn(['allowed_file_types', 'strict_file_types']);
        });
    }
};
