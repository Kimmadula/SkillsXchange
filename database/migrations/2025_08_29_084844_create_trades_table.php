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
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('offering_skill_id');
            $table->unsignedBigInteger('looking_skill_id');

            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->time('available_from')->nullable();
            $table->time('available_to')->nullable();
            $table->json('preferred_days')->nullable(); // ["Mon","Tue",...]

            $table->enum('gender_pref', ['any','male','female'])->default('any');
            $table->string('location')->nullable();
            $table->enum('session_type', ['any','online','onsite'])->default('any');

            $table->boolean('use_username')->default(false);
            $table->enum('status', ['open','ongoing','closed'])->default('open');

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('offering_skill_id')->references('skill_id')->on('skills')->onDelete('restrict');
            $table->foreign('looking_skill_id')->references('skill_id')->on('skills')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trades');
    }
};
