<?php

namespace Database\Factories;

use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'start_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'end_date' => $this->faker->dateTimeBetween('+1 month', '+2 months'),
            'location' => $this->faker->address(),
            'expected_participants' => $this->faker->numberBetween(5, 100),
            'total_involved_participants' => $this->faker->numberBetween(0, 100),
            'category_id' => $this->faker->numberBetween(1,3),
            'created_by' => $this->faker->randomElement([1]),
            'attributes' => json_encode([
                'duration_days' => $this->faker->numberBetween(1,5),
                'is_weekend' => true,
                'days_until_event' => $this->faker->numberBetween(1,20),
                'category_name' => $this->faker->word(5),
                'event_size' => $this->classifyEventSize($this->faker->numberBetween(50,50000)),
                'season' => $this->getSeason($this->faker->numberBetween(1,12)),
                'is_holiday' => true,
                'time_of_day' => $this->getTimeOfDay(now()),
                'location_type' => $this->classifyLocation($this->faker->city),
                'organizer_reputation' => null,
                'key_themes' => $this->extractKeyThemes($this->faker->paragraph),
                'formality_level' => null
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function classifyEventSize($expectedParticipants)
    {
        return match (true) {
            $expectedParticipants <= 50 => 'intimate',
            $expectedParticipants <= 200 => 'small',
            $expectedParticipants <= 1000 => 'medium',
            $expectedParticipants <= 5000 => 'large',
            default => 'massive',
        };
    }

    private function getSeason($date)
    {
        $month = $date;
        return match (true) {
            $month >= 3 && $month <= 5 => 'spring',
            $month >= 6 && $month <= 8 => 'summer',
            $month >= 9 && $month <= 11 => 'autumn',
            default => 'winter',
        };
    }

    private function getTimeOfDay($date)
    {
        $hour = $date->hour;
        return match (true) {
            $hour >= 5 && $hour < 12 => 'morning',
            $hour >= 12 && $hour < 17 => 'afternoon',
            $hour >= 17 && $hour < 21 => 'evening',
            default => 'night',
        };
    }

    private function extractKeyThemes($description)
    {
        // Convert to lowercase and remove punctuation
        $text = strtolower(preg_replace("/[^a-zA-Z0-9\s]/", "", $description));

        // Split into words
        $words = str_word_count($text, 1);

        // Remove stop words
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        $words = array_diff($words, $stopWords);

        // Count word frequencies
        $wordFrequencies = array_count_values($words);

        // Sort by frequency
        arsort($wordFrequencies);

        // Return top 5 most frequent words as themes
        return array_slice(array_keys($wordFrequencies), 0, 5);
    }

    private function classifyLocation($location)
    {
        $client = new Client();
        $response = $client->get('https://nominatim.openstreetmap.org/search', [
            'query' => [
                'q' => $location,
                'format' => 'json',
                'limit' => 1
            ],
            'headers' => [
                'User-Agent' => 'EventHive/1.0'
            ]
        ]);

        $data = json_decode($response->getBody(), true);

        if (empty($data)) {
            return 'unknown';
        }

        $types = explode(',', $data[0]['class'] ?? '');

        if (in_array('boundary', $types) || in_array('place', $types)) {
            return 'urban';
        } elseif (in_array('natural', $types)) {
            return 'nature';
        } elseif (in_array('amenity', $types) || in_array('building', $types)) {
            return 'venue';
        }

        return 'other';
    }
}
