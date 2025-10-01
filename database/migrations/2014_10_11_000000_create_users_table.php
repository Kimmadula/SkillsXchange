<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('skill_id')->nullable();

            $table->string('firstname', 50);
            $table->string('middlename', 50)->nullable();
            $table->string('lastname', 50);

            $table->string('username', 50)->unique();
            $table->string('email')->unique();               // ← required by Breeze
            $table->timestamp('email_verified_at')->nullable(); // ← Breeze-friendly
            $table->string('password');

            $table->string('photo')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->enum('role', ['user', 'admin'])->default('user');
            $table->enum('plan', ['free', 'premium'])->default('free');
            $table->integer('token_balance')->default(0);

            $table->timestamp('date_created')->useCurrent();
            $table->rememberToken(); // ← for "Remember me"

            $table->foreign('skill_id')
                  ->references('skill_id')
                  ->on('skills')
                  ->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
