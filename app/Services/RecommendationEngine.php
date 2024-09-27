<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use App\Models\UserInteractions as UserInteraction;
use App\Models\EventRating;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RecommendationEngine
{
    private const CACHE_TTL = 3600; // 1 hour
    private const TIME_DECAY_HALF_LIFE = 30; // 30 days
    private const POPULARITY_WEIGHT = 0.1;
    private const CAPACITY_WEIGHT = 0.05;
    private const RATING_WEIGHT = 0.15;
    private const SIMILARITY_THRESHOLD = 0.5;

    private $logFile;

    public function __construct()
    {
        $this->logFile = storage_path('logs/recommendation_engine.log');
    }

    public function getRecommendations(User $user, int $limit = 10): array
    {
        $this->logCalculation("Starting recommendation calculation for User ID: {$user->id}");
        $this->logCalculation("User Name: {$user->name}");

        //return Cache::remember("user_recommendations_{$user->id}", self::CACHE_TTL, function () use ($user, $limit) {
            $interactionBasedScores = $this->getInteractionBasedScores($user);
            $preferenceBasedScores = $this->getPreferenceBasedScores($user);
            $popularityScores = $this->getPopularityScores();
            $capacityScores = $this->getCapacityScores();
            $ratingScores = $this->getRatingScores();

            $combinedScores = $this->combineScores($interactionBasedScores, $preferenceBasedScores, $popularityScores, $capacityScores, $ratingScores);

            arsort($combinedScores);
            $topRecommendations = array_slice($combinedScores, 0, $limit, true);

            $recommendations = $this->getSimilarEvents($topRecommendations, $combinedScores);

            $this->logCalculation("Finished recommendation calculation for User ID: {$user->id}");

            return $recommendations;
        //});
    }

    private function getSimilarEvents(array $topRecommendations, array $allScores): array
    {
        $similarEvents = [];

        //dd($topRecommendations);

        foreach ($topRecommendations as $eventId => $score) {
            $similarEvents[$eventId] = $score;

            foreach ($allScores as $otherEventId => $otherScore) {
                if ($eventId != $otherEventId && !isset($similarEvents[$otherEventId])) {
                    //$similarity = $this->calculateEventSimilarity($eventId, $otherEventId);

                    if ($otherScore >= self::SIMILARITY_THRESHOLD) {
                        $similarEvents[$otherEventId] = $otherScore;
                    }
                }
            }
        }

        arsort($similarEvents);
        return $similarEvents;
    }

    private function calculateEventSimilarity(int $eventId1, int $eventId2): float
    {
        $event1 = Event::find($eventId1);
        $event2 = Event::find($eventId2);

        // Compare event attributes
        $categoryMatch = $event1->category === $event2->category ? 1 : 0;
        $themeMatch = $event1->theme === $event2->theme ? 1 : 0;
        $locationTypeMatch = $event1->location_type === $event2->location_type ? 1 : 0;

        $totalFactors = 3;
        $similarityScore = ($categoryMatch + $themeMatch + $locationTypeMatch) / $totalFactors;

        return $similarityScore;
    }

    private function getInteractionBasedScores(User $user): array
    {
        $scores = [];
        $userInteractions = UserInteraction::where('user_id', $user->id)->get();
        $now = Carbon::now();

        foreach ($userInteractions as $interaction) {
            $weight = $this->getInteractionWeight($interaction->interaction_type);
            $timeDecay = $this->calculateTimeDecay($interaction->created_at, $now);
            $scores[$interaction->event_id] = ($scores[$interaction->event_id] ?? 0) + ($weight * $timeDecay);
        }

        return $scores;
    }

    private function getInteractionWeight(string $interactionType): float
    {
        return match ($interactionType) {
            'view' => 0.1,
            'bookmark' => 0.3,
            'register' => 0.5,
            'attend' => 0.7,
            default => 0,
        };
    }

    private function calculateTimeDecay(Carbon $interactionDate, Carbon $now): float
    {
        $daysSince = $interactionDate->diffInDays($now);
        return pow(0.5, $daysSince / self::TIME_DECAY_HALF_LIFE);
    }

    private function getPreferenceBasedScores(User $user): array
    {
        $scores = [];
        $events = Event::all();
        $userPreferences = $user->preferences ? json_decode($user->preferences, true) : null;

        if (!$userPreferences) {
            return array_fill_keys($events->pluck('id')->toArray(), 0.5); // Neutral score if no preferences
        }

        foreach ($events as $event) {
            $eventAttributes = $event->attributes ? json_decode($event->attributes, true) : null;
            if (!$eventAttributes) {
                $scores[$event->id] = 0.5; // Neutral score if no event attributes
                continue;
            }


            $this->logCalculation("Calculation started for event id: " . $event->id);

            $score = $this->calculatePreferenceMatch($eventAttributes, $userPreferences);
            $scores[$event->id] = $score;
        }

        return $scores;
    }

    private function calculatePreferenceMatch(array $eventAttributes, array $userPreferences): float
    {
        $totalScore = 0;
        $totalWeight = 0;

        foreach ($userPreferences as $key => $preference) {
            $weight = $this->getPreferenceWeight($key);
            $totalWeight += $weight;

            $score = $this->comparePreference($key, $preference, $eventAttributes);
            $totalScore += $score * $weight;

            $this->logCalculation("Preference: $key, Weight: $weight, Score: $score");
        }

        $finalScore = $totalWeight > 0 ? $totalScore / $totalWeight : 0.5;
        $this->logCalculation("Final Preference Score: $finalScore");

        return $finalScore;
    }

    private function comparePreference(string $key, $userPreference, array $eventAttributes): float
    {
        switch ($key) {
            case 'preferred_days':
                return $this->compareDays($userPreference, $eventAttributes['is_weekend'] ?? false);
            case 'preferred_themes':
                return $this->compareThemes($userPreference, $eventAttributes['key_themes'] ?? []);
            case 'preferred_seasons':
                return $this->compareSingleValue($userPreference, $eventAttributes['season'] ?? null);
            case 'preferred_categories':
                return $this->compareSingleValue($userPreference, $eventAttributes['category_name'] ?? null);
            case 'preferred_event_sizes':
                return $this->compareSingleValue($userPreference, $eventAttributes['event_size'] ?? null);
            case 'preferred_times_of_day':
                return $this->compareSingleValue($userPreference, $eventAttributes['time_of_day'] ?? null);
            case 'preferred_duration_days':
                return $this->compareDuration($userPreference, $eventAttributes['duration_days'] ?? null);
            case 'preferred_location_types':
                return $this->compareSingleValue($userPreference, $eventAttributes['location_type'] ?? null);
            case 'preferred_formality_levels':
                return $this->compareSingleValue($userPreference, $eventAttributes['formality_level'] ?? null);
            default:
                return 0.5; // Neutral score for unknown preferences
        }
    }

    private function compareDays(array $preferredDays, bool $isWeekend): float
    {
        if (in_array('weekend', $preferredDays) && $isWeekend) return 1.0;
        if (in_array('weekday', $preferredDays) && !$isWeekend) return 1.0;
        return 0.0;
    }

    private function compareThemes(array $preferredThemes, array $eventThemes): float
    {
        $matchingThemes = array_intersect($preferredThemes, $eventThemes);
        return count($matchingThemes) / max(count($preferredThemes), 1);
    }

    private function compareSingleValue($preference, $eventValue): float
    {
        if (is_array($preference)) {
            return in_array($eventValue, $preference) ? 1.0 : 0.0;
        }
        return $preference === $eventValue ? 1.0 : 0.0;
    }

    private function compareDuration(array $preferredDurations, ?int $eventDuration): float
    {
        if ($eventDuration === null) return 0.5;
        $closestDuration = $this->getClosestValue($eventDuration, $preferredDurations);
        $maxDifference = max($preferredDurations) - min($preferredDurations);
        $actualDifference = abs($eventDuration - $closestDuration);
        return 1 - ($actualDifference / max($maxDifference, 1));
    }

    private function getClosestValue(int $target, array $array)
    {
        return $array[array_reduce(array_keys($array), function ($carry, $key) use ($array, $target) {
            return (abs($target - $array[$key]) < abs($target - $array[$carry])) ? $key : $carry;
        }, 0)];
    }

    private function getPreferenceWeight(string $preferenceName): float
    {
        return match ($preferenceName) {
            'preferred_categories' => 2.5,
            'preferred_themes' => 2.0,
            'preferred_days' => 1.8,
            'preferred_seasons' => 1.5,
            'preferred_event_sizes' => 1.3,
            'preferred_times_of_day' => 1.2,
            'preferred_duration_days' => 1.1,
            'preferred_location_types' => 1.0,
            'preferred_formality_levels' => 0.9,
            default => 0.5,
        };
    }

    private function calculateJaccardSimilarity(array $set1, array $set2): float
    {
        $intersection = count(array_intersect($set1, $set2));
        $union = count(array_unique(array_merge($set1, $set2)));
        return $union > 0 ? $intersection / $union : 0;
    }

    private function getPopularityScores(): array
    {
        $scores = [];
        $interactionWeights = [
            'view' => 1,
            'bookmark' => 2,
            'register' => 3,
            'attend' => 4,
            'rate' => 3,
            'feedback' => 2
        ];

        $maxWeightedInteractions = UserInteraction::select('event_id')
            ->selectRaw('SUM(CASE
                WHEN interaction_type = "view" THEN 1
                WHEN interaction_type = "bookmark" THEN 2
                WHEN interaction_type = "register" THEN 3
                WHEN interaction_type = "attend" THEN 4
                WHEN interaction_type = "rate" THEN 3
                WHEN interaction_type = "feedback" THEN 2
                ELSE 0 END) as weighted_interactions')
            ->groupBy('event_id')
            ->orderByDesc('weighted_interactions')
            ->value('weighted_interactions') ?: 1;

        $eventInteractions = UserInteraction::select('event_id', 'interaction_type')
            ->selectRaw('COUNT(*) as interaction_count')
            ->groupBy('event_id', 'interaction_type')
            ->get();

        foreach ($eventInteractions as $interaction) {
            $weight = $interactionWeights[$interaction->interaction_type] ?? 1;
            $weightedCount = $interaction->interaction_count * $weight;
            $scores[$interaction->event_id] = ($scores[$interaction->event_id] ?? 0) + ($weightedCount / $maxWeightedInteractions);
        }

        return $scores;
    }

    /* private function getPopularityScores(): array */
    /* { */
    /*     $scores = []; */
    /*     $maxInteractions = UserInteraction::select('event_id') */
    /*         ->groupBy('event_id') */
    /*         ->orderByRaw('COUNT(*) DESC') */
    /*         ->limit(1) */
    /*         ->withCount('event_id') */
    /*         ->first() */
    /*         ->event_id_count ?? 1; */
    /*  */
    /*     //dd($maxInteractions); */
    /*  */
    /*     $eventInteractions = UserInteraction::select('event_id') */
    /*         ->groupBy('event_id') */
    /*         ->withCount('event_id') */
    /*         ->get(); */
    /*  */
    /*     foreach ($eventInteractions as $interaction) { */
    /*         $scores[$interaction->event_id] = $interaction->event_id_count / $maxInteractions; */
    /*     } */
    /*  */
    /*     return $scores; */
    /* } */

    private function getCapacityScores(): array
    {
        $scores = [];
        $events = Event::all(['id', 'total_involved_participants', 'expected_participants']);

        foreach ($events as $event) {
            $capacityRatio = $event->total_involved_participants / $event->expected_participants;
            $scores[$event->id] = 1 - min($capacityRatio, 1); // Higher score for events with more available capacity
        }

        return $scores;
    }

    private function getRatingScores(): array
    {
        $scores = [];
        $eventRatings = EventRating::select('event_id', DB::raw('AVG(rating) as avg_rating'))
            ->groupBy('event_id')
            ->get();

        $maxRating = EventRating::max('rating') ?: 5; // Assuming a 5-star rating system if no ratings yet

        foreach ($eventRatings as $rating) {
            $scores[$rating->event_id] = $rating->avg_rating / $maxRating;
        }

        return $scores;
    }

    private function combineScores(array $interactionScores, array $preferenceScores, array $popularityScores, array $capacityScores, array $ratingScores): array
    {
        $combinedScores = [];

        $allEventIds = array_unique(array_merge(
            array_keys($interactionScores),
            array_keys($preferenceScores),
            array_keys($popularityScores),
            array_keys($capacityScores),
            array_keys($ratingScores)
        ));

        foreach ($allEventIds as $eventId) {
            $interactionScore = $interactionScores[$eventId] ?? 0;
            $preferenceScore = $preferenceScores[$eventId] ?? 0;
            $popularityScore = $popularityScores[$eventId] ?? 0;
            $capacityScore = $capacityScores[$eventId] ?? 0;
            $ratingScore = $ratingScores[$eventId] ?? 0;

            $combinedScores[$eventId] =
            ($interactionScore * 0.45) +
                ($preferenceScore * 0.25) +
                ($popularityScore * self::POPULARITY_WEIGHT) +
                ($capacityScore * self::CAPACITY_WEIGHT) +
                ($ratingScore * self::RATING_WEIGHT);
        }

        return $combinedScores;
    }

    private function logCalculation(string $message): void
    {
        $formattedMessage = "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
        file_put_contents($this->logFile, $formattedMessage, FILE_APPEND);
    }
}
