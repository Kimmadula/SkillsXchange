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
            $table->boolean('verified')->default(false)->after('completed_at');
            $table->timestamp('verified_at')->nullable()->after('verified');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null')->after('verified_at');
            $table->text('verification_notes')->nullable()->after('verified_by');
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
            $table->dropForeign(['verified_by']);
            $table->dropColumn(['verified', 'verified_at', 'verified_by', 'verification_notes']);
        });
    }
};
