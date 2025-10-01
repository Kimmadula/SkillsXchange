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
        if (!Schema::hasTable('trade_messages')) {
            Schema::create('trade_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('trade_id');
                $table->unsignedBigInteger('sender_id');
                $table->text('message');
                $table->timestamps();
                $table->foreign('trade_id')->references('id')->on('trades')->onDelete('cascade');
                $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
            });
        } else {
            // If table exists, add missing columns
            Schema::table('trade_messages', function (Blueprint $table) {
                if (!Schema::hasColumn('trade_messages', 'trade_id')) {
                    $table->unsignedBigInteger('trade_id');
                    $table->foreign('trade_id')->references('id')->on('trades')->onDelete('cascade');
                }
                if (!Schema::hasColumn('trade_messages', 'sender_id')) {
                    $table->unsignedBigInteger('sender_id');
                    $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
                }
                if (!Schema::hasColumn('trade_messages', 'message')) {
                    $table->text('message');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trade_messages');
    }
};
