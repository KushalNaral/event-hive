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
        Schema::create('user_interactions', function (Blueprint $table) {
            $table->id();

            $table->string('interaction_type');
            //basically it is used for calculations by using metrics such as attended, liked, disliked, watched (seen) etc.

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('event_id');

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('event_id')->references('id')->on('events');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_interactions');
    }
};
