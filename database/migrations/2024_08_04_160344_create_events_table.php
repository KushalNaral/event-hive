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
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->longText('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('location');
            $table->integer('expected_participants')->nullable();
            $table->integer('total_involved_participants')->nullable();

            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('created_by');

            $table->foreign('category_id')->references('id')->on('event_categories');
            $table->foreign('created_by')->references('id')->on('users');

            $table->json('attributes')->nullable();
            //similar to users preferences but for events

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
