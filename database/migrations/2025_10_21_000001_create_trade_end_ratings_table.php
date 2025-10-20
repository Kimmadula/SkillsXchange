<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('trade_end_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_id')->constrained('trades')->onDelete('cascade');
            $table->foreignId('rater_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('rated_user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedTinyInteger('service_quality_rating')->nullable();
            $table->unsignedTinyInteger('communication_rating')->nullable();
            $table->unsignedTinyInteger('timeliness_rating')->nullable();
            $table->unsignedTinyInteger('value_rating')->nullable();
            $table->unsignedTinyInteger('overall_experience_rating')->nullable();
            $table->text('written_feedback')->nullable();
            $table->boolean('would_recommend')->default(false);
            $table->unsignedTinyInteger('trade_completion_satisfaction')->nullable();
            $table->timestamps();

            $table->index(['rated_user_id']);
            $table->index(['rater_id']);
            $table->index(['trade_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('trade_end_ratings');
    }
};


