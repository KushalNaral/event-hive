<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RecommendationLogger
{
    private const LOG_TABLE = 'recommendation_logs';
    private const SCORE_LOG_TABLE = 'recommendation_score_logs';
    private const PREFERENCE_LOG_TABLE = 'recommendation_preference_logs';

    public function logRecommendationStart(int $userId): int
    {
        return DB::table(self::LOG_TABLE)->insertGetId([
            'user_id' => $userId,
            'status' => 'started',
            'created_at' => Carbon::now(),
        ]);
    }

    public function logRecommendationComplete(int $logId, array $recommendations): void
    {
        DB::table(self::LOG_TABLE)
            ->where('id', $logId)
            ->update([
                'status' => 'completed',
                'recommendations' => json_encode($recommendations),
                'updated_at' => Carbon::now(),
            ]);
    }

    public function logScores(int $logId, int $eventId, array $scores): void
    {
        DB::table(self::SCORE_LOG_TABLE)->insert([
            'log_id' => $logId,
            'event_id' => $eventId,
            'interaction_score' => $scores['interaction'] ?? 0,
            'preference_score' => $scores['preference'] ?? 0,
            'popularity_score' => $scores['popularity'] ?? 0,
            'capacity_score' => $scores['capacity'] ?? 0,
            'rating_score' => $scores['rating'] ?? 0,
            'user_correlation_score' => $scores['user_correlation'] ?? 0,
            'final_score' => $scores['final'] ?? 0,
            'created_at' => Carbon::now(),
        ]);
    }

    public function logPreferenceMatch(int $logId, int $eventId, array $preferenceMatches): void
    {
        DB::table(self::PREFERENCE_LOG_TABLE)->insert([
            'log_id' => $logId,
            'event_id' => $eventId,
            'category_match' => $preferenceMatches['category'] ?? 0,
            'theme_match' => $preferenceMatches['theme'] ?? 0,
            'season_match' => $preferenceMatches['season'] ?? 0,
            'day_match' => $preferenceMatches['day'] ?? 0,
            'size_match' => $preferenceMatches['size'] ?? 0,
            'time_match' => $preferenceMatches['time'] ?? 0,
            'duration_match' => $preferenceMatches['duration'] ?? 0,
            'location_match' => $preferenceMatches['location'] ?? 0,
            'formality_match' => $preferenceMatches['formality'] ?? 0,
            'created_at' => Carbon::now(),
        ]);
    }
}
