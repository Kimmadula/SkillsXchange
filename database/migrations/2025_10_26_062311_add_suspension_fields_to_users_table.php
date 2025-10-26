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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_suspended')->default(false)->after('is_verified');
            $table->timestamp('suspension_start')->nullable()->after('is_suspended');
            $table->timestamp('suspension_end')->nullable()->after('suspension_start');
            $table->text('suspension_reason')->nullable()->after('suspension_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_suspended', 'suspension_start', 'suspension_end', 'suspension_reason']);
        });
    }
};