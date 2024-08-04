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
        Schema::create('event_ratings', function (Blueprint $table) {
            $table->id();

            $table->integer('rating')->default(0);

            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('created_by');

            $table->foreign('event_id')->references('id')->on('events');
            $table->foreign('created_by')->references('id')->on('users');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_ratings');
    }
};
