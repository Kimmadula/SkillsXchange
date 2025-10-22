<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reported_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('trade_id')->nullable()->constrained('trades')->onDelete('cascade');
            $table->string('context')->nullable();
            $table->enum('reason', ['harassment', 'spam', 'inappropriate', 'fraud', 'safety', 'other']);
            $table->text('description');
            $table->json('evidence')->nullable();
            $table->enum('status', ['pending', 'under_review', 'resolved', 'dismissed'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['reported_user_id', 'status']);
            $table->index(['reporter_id']);
            $table->index(['trade_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_reports');
    }
};


