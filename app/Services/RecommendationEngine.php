<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use App\Models\UserInteractions as UserInteraction;
use App\Models\EventRating;
use App\Models\UserCategory as UserEventCategory;
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
    private const USER_CORRELATION_WEIGHT = 0.2;

    private $logFile;
    private $logger;
    private $currentLogId;

    public function __construct(RecommendationLogger $logger)
    {
        $this->logger = $logger;
        $this->logFile = storage_path('logs/recommendation_engine.log');
    }

    public function getRecommendations(User $user, int $limit = 10): array
    {
        $this->logCalculation("Starting recommendation calculation for User ID: {$user->id}");
        $this->logCalculation("User Name: {$user->name}");

        $this->currentLogId = $this->logger->logRecommendationStart($user->id);

        $interactionBasedScores = $this->getInteractionBasedScores($user);
        $preferenceBasedScores = $this->getPreferenceBasedScores($user);
        $popularityScores = $this->getPopularityScores();
        $capacityScores = $this->getCapacityScores();
        $ratingScores = $this->getRatingScores();
        $userCorrelationScores = $this->getUserCorrelationScores($user);

        $combinedScores = $this->combineScores(
            $interactionBasedScores,
            $preferenceBasedScores,
            $popularityScores,
            $capacityScores,
            $ratingScores,
            $userCorrelationScores
        );


        arsort($combinedScores);
        $topRecommendations = array_slice($combinedScores, 0, $limit, true);
        $interactedEventsIndexex = UserInteraction::where('user_id', $user->id )->get()->pluck('event_id')->toArray();

        // Filter out the interacted events from $topRecommendations
        $topRecommendations = array_filter($topRecommendations, function($key) use ($interactedEventsIndexex) {
            return !in_array($key, $interactedEventsIndexex);
        }, ARRAY_FILTER_USE_KEY);

        $recommendations = $this->getSimilarEvents($topRecommendations, $combinedScores);

        //dd($recommendations , $interactedEventsIndexex, auth()->user()->id);

        $this->logCalculation("Finished recommendation calculation for User ID: {$user->id}");
        $this->logger->logRecommendationComplete($this->currentLogId, $recommendations);

        return $recommendations;
    }

    private function getUserCorrelationScores(User $user): array
    {
        $this->logCalculation("Calculating user correlation scores for User ID: {$user->id}");

        $userCategories = UserEventCategory::where('user_id', $user->id)->pluck('event_category_id')->toArray();

        $correlatedUsers = UserEventCategory::whereIn('event_category_id', $userCategories)
            ->where('user_id', '!=', $user->id)
            ->select('user_id', DB::raw('COUNT(*) as category_match'))
            ->groupBy('user_id')
            ->orderByDesc('category_match')
            ->limit(10)
            ->get();

        $scores = [];
        foreach ($correlatedUsers as $correlatedUser) {
            $correlationStrength = $correlatedUser->category_match / count($userCategories);

            $userInteractions = UserInteraction::where('user_id', $correlatedUser->user_id)
                ->select('event_id', 'interaction_type')
                ->get();

            foreach ($userInteractions as $interaction) {
                $interactionWeight = $this->getInteractionWeight($interaction->interaction_type);
                $scores[$interaction->event_id] = ($scores[$interaction->event_id] ?? 0) + ($interactionWeight * $correlationStrength);
            }
        }

        $this->logCalculation("User correlation scores calculated. Number of correlated events: " . count($scores));

        return $scores;
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
            'like' => 0.7,
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

            $score = $this->calculatePreferenceMatch($eventAttributes, $userPreferences, $event->id);
            $scores[$event->id] = $score;
        }

        return $scores;
    }

    private function calculatePreferenceMatch(array $eventAttributes, array $userPreferences, $eventId): float
    {
        $matches = [];
        $totalScore = 0;
        $totalWeight = 0;

        foreach ($userPreferences as $key => $preference) {
            $weight = $this->getPreferenceWeight($key);
            $totalWeight += $weight;
            $score = $this->comparePreference($key, $preference, $eventAttributes);
            $totalScore += $score * $weight;
            $matches[$key] = $score;
        }

        // Prepare logging data with null checks for each field
        $loggingData = [
            'category' => $matches['preferred_categories'] ?? 0,
            'theme' => $matches['preferred_themes'] ?? 0,
            'season' => $matches['preferred_seasons'] ?? 0,
            'day' => $matches['preferred_days'] ?? 0,
            'size' => $matches['preferred_event_sizes'] ?? 0,
            'time' => $matches['preferred_times_of_day'] ?? 0,
            'duration' => $matches['preferred_duration_days'] ?? 0,
            'location' => $matches['preferred_location_types'] ?? 0,
            'formality' => $matches['preferred_formality_levels'] ?? 0,
            'event_attributes' => $eventAttributes, // Adding original attributes for debugging
        ];

        // Log preference matches with the generated or actual event ID
        try {
            $this->logger->logPreferenceMatch($this->currentLogId, $eventId, $loggingData);
        } catch (\Exception $e) {
            // Log the error but don't interrupt the calculation
            $this->logger->error("Failed to log preference match: " . $e->getMessage(), [
                'event_id' => $eventId,
                'log_id' => $this->currentLogId
            ]);
        }

        return $totalWeight > 0 ? $totalScore / $totalWeight : 0.5;
    }
    /* private function calculatePreferenceMatch(array $eventAttributes, array $userPreferences): float */
    /* { */
    /*     $totalScore = 0; */
    /*     $totalWeight = 0; */
    /*  */
    /*     foreach ($userPreferences as $key => $preference) { */
    /*         $weight = $this->getPreferenceWeight($key); */
    /*         $totalWeight += $weight; */
    /*  */
    /*         $score = $this->comparePreference($key, $preference, $eventAttributes); */
    /*         $totalScore += $score * $weight; */
    /*  */
    /*         $this->logCalculation("Preference: $key, Weight: $weight, Score: $score"); */
    /*     } */
    /*  */
    /*     $finalScore = $totalWeight > 0 ? $totalScore / $totalWeight : 0.5; */
    /*     $this->logCalculation("Final Preference Score: $finalScore"); */
    /*  */
    /*     return $finalScore; */
    /* } */

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
        if (empty($preferredDurations)) {
            $preferredDurations = [1];
        }
        $closestDuration = $this->getClosestValue($eventDuration, $preferredDurations ?? 1);
        $maxDifference = max($preferredDurations) - min($preferredDurations);
        $actualDifference = abs($eventDuration - $closestDuration);
        return 1 - ($actualDifference / max($maxDifference, 1));
    }

    private function getClosestValue(int $target, array $array)
    {
        if (empty($array)) {
            $array = [1];
        }

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

        $maxRating = EventRating::max('rating') ?: 1;

        foreach ($eventRatings as $rating) {
            $scores[$rating->event_id] = $rating->avg_rating / $maxRating;
        }

        return $scores;
    }

    private function combineScores(
        array $interactionScores,
        array $preferenceScores,
        array $popularityScores,
        array $capacityScores,
        array $ratingScores,
        array $userCorrelationScores
    ): array {
        $this->logCalculation("Starting score combination process", [
            'total_events' => count(array_unique(array_merge(
                array_keys($interactionScores),
                array_keys($preferenceScores),
                array_keys($popularityScores),
                array_keys($capacityScores),
                array_keys($ratingScores),
                array_keys($userCorrelationScores)
            )))
        ]);

        $combinedScores = [];
        $allEventIds = array_unique(array_merge(
            array_keys($interactionScores),
            array_keys($preferenceScores),
            array_keys($popularityScores),
            array_keys($capacityScores),
            array_keys($ratingScores),
            array_keys($userCorrelationScores)
        ));

        foreach ($allEventIds as $eventId) {
            $interactionScore = $interactionScores[$eventId] ?? 0;
            $preferenceScore = $preferenceScores[$eventId] ?? 0;
            $popularityScore = $popularityScores[$eventId] ?? 0;
            $capacityScore = $capacityScores[$eventId] ?? 0;
            $ratingScore = $ratingScores[$eventId] ?? 0;
            $userCorrelationScore = $userCorrelationScores[$eventId] ?? 0;

            // Log individual scores before combination
            $this->logCalculation("Raw scores for Event ID: $eventId", [
                'interaction_score' => [
                    'value' => $interactionScore,
                    'weight' => 0.35
                ],
                'preference_score' => [
                    'value' => $preferenceScore,
                    'weight' => 0.20
                ],
                'popularity_score' => [
                    'value' => $popularityScore,
                    'weight' => self::POPULARITY_WEIGHT
                ],
                'capacity_score' => [
                    'value' => $capacityScore,
                    'weight' => self::CAPACITY_WEIGHT
                ],
                'rating_score' => [
                    'value' => $ratingScore,
                    'weight' => self::RATING_WEIGHT
                ],
                'user_correlation_score' => [
                    'value' => $userCorrelationScore,
                    'weight' => self::USER_CORRELATION_WEIGHT
                ]
            ]);

            // Calculate weighted components for logging
            $weightedComponents = [
                'interaction' => $interactionScore * 0.35,
                'preference' => $preferenceScore * 0.20,
                'popularity' => $popularityScore * self::POPULARITY_WEIGHT,
                'capacity' => $capacityScore * self::CAPACITY_WEIGHT,
                'rating' => $ratingScore * self::RATING_WEIGHT,
                'user_correlation' => $userCorrelationScore * self::USER_CORRELATION_WEIGHT
            ];

            // Calculate final combined score
            $combinedScores[$eventId] =
            $weightedComponents['interaction'] +
                $weightedComponents['preference'] +
                $weightedComponents['popularity'] +
                $weightedComponents['capacity'] +
                $weightedComponents['rating'] +
                $weightedComponents['user_correlation'];

            // Log weighted components and final score
            $this->logCalculation("Score calculation details for Event ID: $eventId", [
                'event_id' => $eventId,
                'weighted_components' => $weightedComponents,
                'final_score' => $combinedScores[$eventId]
            ]);
        }

        // Log summary statistics
        $this->logCalculation("Score combination complete", [
            'total_events_processed' => count($allEventIds),
            'score_range' => [
                'min' => min($combinedScores),
                'max' => max($combinedScores),
                'avg' => array_sum($combinedScores) / count($combinedScores)
            ],
            'top_scores' => array_slice(
                array_combine(
                    array_keys($combinedScores),
                    array_values($combinedScores)
                ),
                0,
                5,
                true
            )
        ]);

        return $combinedScores;
    }
    /* private function combineScores(array $interactionScores, array $preferenceScores, array $popularityScores, array $capacityScores, array $ratingScores, array $userCorrelationScores): array */
    /* { */
    /*     $this->logCalculation("Combining scores for final recommendations"); */
    /*  */
    /*     $combinedScores = []; */
    /*  */
    /*     $allEventIds = array_unique(array_merge( */
    /*         array_keys($interactionScores), */
    /*         array_keys($preferenceScores), */
    /*         array_keys($popularityScores), */
    /*         array_keys($capacityScores), */
    /*         array_keys($ratingScores), */
    /*         array_keys($userCorrelationScores) */
    /*     )); */
    /*  */
    /*     foreach ($allEventIds as $eventId) { */
    /*         $interactionScore = $interactionScores[$eventId] ?? 0; */
    /*         $preferenceScore = $preferenceScores[$eventId] ?? 0; */
    /*         $popularityScore = $popularityScores[$eventId] ?? 0; */
    /*         $capacityScore = $capacityScores[$eventId] ?? 0; */
    /*         $ratingScore = $ratingScores[$eventId] ?? 0; */
    /*         $userCorrelationScore = $userCorrelationScores[$eventId] ?? 0; */
    /*  */
    /*         $combinedScores[$eventId] = */
    /*         ($interactionScore * 0.35) + */
    /*             ($preferenceScore * 0.20) + */
    /*             ($popularityScore * self::POPULARITY_WEIGHT) + */
    /*             ($capacityScore * self::CAPACITY_WEIGHT) + */
    /*             ($ratingScore * self::RATING_WEIGHT) + */
    /*             ($userCorrelationScore * self::USER_CORRELATION_WEIGHT); */
    /*  */
    /*         $this->logCalculation("Event ID: $eventId, Combined Score: {$combinedScores[$eventId]}"); */
    /*     } */
    /*  */
    /*     return $combinedScores; */
    /* } */

    private function logCalculation(string $message): void
    {
        $formattedMessage = "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
        file_put_contents($this->logFile, $formattedMessage, FILE_APPEND);
    }

}
