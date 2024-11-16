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
        Schema::create('recommendation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->enum('status', ['started', 'completed', 'failed']);
            $table->json('recommendations')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('recommendation_score_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('log_id')->constrained('recommendation_logs')->onDelete('cascade');
            $table->foreignId('event_id')->constrained();
            $table->float('interaction_score');
            $table->float('preference_score');
            $table->float('popularity_score');
            $table->float('capacity_score');
            $table->float('rating_score');
            $table->float('user_correlation_score');
            $table->float('final_score');
            $table->timestamps();
        });

        Schema::create('recommendation_preference_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('log_id')->constrained('recommendation_logs')->onDelete('cascade');
            $table->foreignId('event_id')->constrained();
            $table->float('category_match');
            $table->float('theme_match');
            $table->float('season_match');
            $table->float('day_match');
            $table->float('size_match');
            $table->float('time_match');
            $table->float('duration_match');
            $table->float('location_match');
            $table->float('formality_match');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_preference_logs');
        Schema::dropIfExists('recommendation_score_logs');
        Schema::dropIfExists('recommendation_logs');
    }
};
