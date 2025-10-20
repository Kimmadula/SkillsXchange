<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('session_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->foreignId('rater_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('rated_user_id')->constrained('users')->onDelete('cascade');
            $table->enum('session_type', ['chat_session', 'trade_session', 'skill_sharing'])->default('chat_session');
            $table->unsignedTinyInteger('overall_rating')->nullable();
            $table->unsignedTinyInteger('communication_rating')->nullable();
            $table->unsignedTinyInteger('helpfulness_rating')->nullable();
            $table->unsignedTinyInteger('knowledge_rating')->nullable();
            $table->text('written_feedback')->nullable();
            $table->unsignedInteger('session_duration')->nullable();
            $table->json('skills_discussed')->nullable();
            $table->timestamps();

            $table->index(['rated_user_id']);
            $table->index(['rater_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('session_ratings');
    }
};


