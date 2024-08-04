<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\EventCategory;

class EventCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Technology',
            'Health',
            'Travel',
            'Education',
        ];

        foreach ($categories as $category) {
            EventCategory::create([
                'name' => $category,
                'slug' => Str::slug($category),
            ]);
        }
    }
}
