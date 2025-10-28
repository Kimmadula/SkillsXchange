<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            // audience_type: all, role, status, custom
            $table->string('audience_type')->default('all')->after('priority');
            // audience_value: json payload depending on type (e.g., ["admin"], ["verified"], [1,2,3])
            $table->json('audience_value')->nullable()->after('audience_type');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn(['audience_type', 'audience_value']);
        });
    }
};


