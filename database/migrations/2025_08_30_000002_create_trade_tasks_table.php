<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('trade_tasks')) {
            Schema::create('trade_tasks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('trade_id');
                $table->unsignedBigInteger('created_by'); // Who created the task
                $table->unsignedBigInteger('assigned_to'); // Who the task is for
                $table->string('title');
                $table->text('description')->nullable();
                $table->boolean('completed')->default(false);
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->foreign('trade_id')->references('id')->on('trades')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('assigned_to')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('trade_tasks');
    }
};
