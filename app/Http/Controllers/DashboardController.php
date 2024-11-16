<?php

namespace App\Http\Controllers;

use App\Services\RecommendationLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Date range handling
        $dateRange = $this->getDateRange($request);

        // Base queries
        $baseQuery = DB::table('recommendation_logs')
            ->whereBetween('created_at', $dateRange);

        if ($request->filled('status')) {
            $baseQuery->where('status', $request->status);
        }

        // Calculate metrics
        $totalRecommendations = $baseQuery->count();

        // Get previous period metrics for trends
        $previousRange = [
            $dateRange[0]->copy()->subDays($dateRange[0]->diffInDays($dateRange[1])),
            $dateRange[0]->copy()->subDay()
        ];

        $previousTotal = DB::table('recommendation_logs')
            ->whereBetween('created_at', $previousRange)
            ->count();

        $recommendationTrend = $previousTotal ?
        round((($totalRecommendations - $previousTotal) / $previousTotal) * 100, 1) : 0;

        // Score metrics
        $scoreMetrics = DB::table('recommendation_score_logs')
            ->join('recommendation_logs', 'recommendation_logs.id', '=', 'recommendation_score_logs.log_id')
            ->whereBetween('recommendation_logs.created_at', $dateRange)
            ->select(
                DB::raw('AVG(final_score) as avg_final'),
                DB::raw('AVG(interaction_score) as avg_interaction'),
                DB::raw('AVG(preference_score) as avg_preference'),
                DB::raw('AVG(popularity_score) as avg_popularity'),
                DB::raw('AVG(capacity_score) as avg_capacity'),
                DB::raw('AVG(rating_score) as avg_rating'),
                DB::raw('AVG(user_correlation_score) as avg_correlation')
            )
            ->first();

        // Preference metrics
        $preferenceMetrics = DB::table('recommendation_preference_logs')
            ->join('recommendation_logs', 'recommendation_logs.id', '=', 'recommendation_preference_logs.log_id')
            ->whereBetween('recommendation_logs.created_at', $dateRange)
            ->select(
                DB::raw('AVG(category_match) as category'),
                DB::raw('AVG(theme_match) as theme'),
                DB::raw('AVG(season_match) as season'),
                DB::raw('AVG(day_match) as day'),
                DB::raw('AVG(size_match) as size'),
                DB::raw('AVG(time_match) as time'),
                DB::raw('AVG(duration_match) as duration'),
                DB::raw('AVG(location_match) as location'),
                DB::raw('AVG(formality_match) as formality')
            )
            ->first();

        // Success rate calculation
        $successRate = $baseQuery->where('status', 'completed')->count() / max($totalRecommendations, 1) * 100;

        // Average processing time
        $avgProcessingTime = DB::table('recommendation_logs')
            ->whereBetween('created_at', $dateRange)
            ->whereNotNull('updated_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_time'))
            ->value('avg_time');

        $prevProcessingTime = DB::table('recommendation_logs')
            ->whereBetween('created_at', $previousRange)
            ->whereNotNull('updated_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_time'))
            ->value('avg_time');

        $processingTimeTrend = $prevProcessingTime ?
        round((($avgProcessingTime - $prevProcessingTime) / $prevProcessingTime) * 100, 1) : 0;

        // Score distribution for chart
        $scoreDistribution = [
            'Interaction' => $scoreMetrics->avg_interaction ?? 0,
            'Preference' => $scoreMetrics->avg_preference ?? 0,
            'Popularity' => $scoreMetrics->avg_popularity ?? 0,
            'Capacity' => $scoreMetrics->avg_capacity ?? 0,
            'Rating' => $scoreMetrics->avg_rating ?? 0,
            'User Correlation' => $scoreMetrics->avg_correlation ?? 0
        ];

        // Preference distribution for radar chart
        $preferenceDistribution = [
            'Category' => $preferenceMetrics->category ?? 0,
            'Theme' => $preferenceMetrics->theme ?? 0,
            'Season' => $preferenceMetrics->season ?? 0,
            'Day' => $preferenceMetrics->day ?? 0,
            'Size' => $preferenceMetrics->size ?? 0,
            'Time' => $preferenceMetrics->time ?? 0,
            'Duration' => $preferenceMetrics->duration ?? 0,
            'Location' => $preferenceMetrics->location ?? 0,
            'Formality' => $preferenceMetrics->formality ?? 0
        ];

        // Score breakdown for detailed metrics
        $scoreBreakdown = [
            'interaction' => $scoreMetrics->avg_interaction ?? 0,
            'preference' => $scoreMetrics->avg_preference ?? 0,
            'popularity' => $scoreMetrics->avg_popularity ?? 0,
            'capacity' => $scoreMetrics->avg_capacity ?? 0,
            'rating' => $scoreMetrics->avg_rating ?? 0,
            'user_correlation' => $scoreMetrics->avg_correlation ?? 0
        ];

        // Get top performing events
        $topEvents = DB::table('recommendation_score_logs')
            ->join('events', 'events.id', '=', 'recommendation_score_logs.event_id')
            ->whereBetween('recommendation_score_logs.created_at', $dateRange)
            ->select(
                'events.id',
                'events.title',
                DB::raw('AVG(final_score) as avg_score'),
                DB::raw('COUNT(*) as recommendation_count')
            )
            ->groupBy('events.id', 'events.title')
            ->orderBy('avg_score', 'desc')
            ->limit(5)
            ->get();

        $averageFinalScore = $scoreMetrics->avg_final ?? 0;

        return view('dashboard.index', compact(
            'totalRecommendations',
            'recommendationTrend',
            'averageFinalScore',
            'successRate',
            'avgProcessingTime',
            'processingTimeTrend',
            'scoreDistribution',
            'preferenceDistribution',
            'scoreBreakdown',
            'topEvents'
        ));
    }

    private function getDateRange(Request $request): array
    {
        if ($request->filled('date_range')) {
            $dates = explode(' to ', $request->date_range);
            return [
                Carbon::parse($dates[0])->startOfDay(),
                Carbon::parse($dates[1] ?? $dates[0])->endOfDay()
            ];
        }

        // Default to last 30 days
        return [
            Carbon::now()->subDays(30)->startOfDay(),
            Carbon::now()->endOfDay()
        ];
    }

    public function exportData(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        $data = DB::table('recommendation_logs')
            ->join('recommendation_score_logs', 'recommendation_logs.id', '=', 'recommendation_score_logs.log_id')
            ->join('recommendation_preference_logs', 'recommendation_logs.id', '=', 'recommendation_preference_logs.log_id')
            ->whereBetween('recommendation_logs.created_at', $dateRange)
            ->select(
                'recommendation_logs.id',
                'recommendation_logs.user_id',
                'recommendation_logs.status',
                'recommendation_logs.created_at',
                'recommendation_score_logs.final_score',
                'recommendation_score_logs.interaction_score',
                'recommendation_score_logs.preference_score',
                'recommendation_score_logs.popularity_score',
                'recommendation_score_logs.capacity_score',
                'recommendation_score_logs.rating_score',
                'recommendation_score_logs.user_correlation_score',
                'recommendation_preference_logs.category_match',
                'recommendation_preference_logs.theme_match',
                'recommendation_preference_logs.season_match',
                'recommendation_preference_logs.day_match',
                'recommendation_preference_logs.size_match',
                'recommendation_preference_logs.time_match',
                'recommendation_preference_logs.duration_match',
                'recommendation_preference_logs.location_match',
                'recommendation_preference_logs.formality_match'
            )
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=recommendation_data.csv'
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');

            // Add headers
            fputcsv($file, array_keys((array)$data->first()));

            // Add data
            foreach ($data as $row) {
                fputcsv($file, (array)$row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
