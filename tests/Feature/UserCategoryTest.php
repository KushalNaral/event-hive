<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\EventCategory;

use Laravel\Passport\Passport;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Config;

class UserCategoryTest extends TestCase
{

    use RefreshDatabase;
    /**
     * A basic feature test example.
     @test
     */
    public function can_assign_single_category_to_user(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        $user = User::factory()->create();

        //because its a protected route, we need this else it fails
        Passport::actingAs($user);

        $categories = EventCategory::factory()->count(1)->create();

        $response = $this->postJson( '/api/' . config('apiVersion.version') . '/event-category/assign', [
            'user_id' => $user->id,
            'category_id' => $categories->pluck('id')->toArray(),
        ]);

        $response->assertStatus(200);

        // Assert the categories are assigned
        foreach ($categories as $category) {
            $this->assertDatabaseHas('user_event_categories', [
                'user_id' => $user->id,
                'event_category_id' => $category->id,
            ]);
        }
    }


    /** @test */
    public function can_assign_multiple_category_to_user(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        $user = User::factory()->create();

        //because its a protected route, we need this else it fails
        Passport::actingAs($user);

        $categories = EventCategory::factory()->count(30)->create();

        $response = $this->postJson( '/api/' . config('apiVersion.version') . '/event-category/assign', [
            'user_id' => $user->id,
            'category_id' => $categories->pluck('id')->toArray(),
        ]);

        $response->assertStatus(200);

        // Assert the categories are assigned
        foreach ($categories as $category) {
            $this->assertDatabaseHas('user_event_categories', [
                'user_id' => $user->id,
                'event_category_id' => $category->id,
            ]);
        }
    }


    /** @test */
    public function can_remove_assigned_category_from_user(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        $user = User::factory()->create();

        // because it's a protected route, we need this else it fails
        Passport::actingAs($user);

        // Create categories and assign them to the user
        $categories = EventCategory::factory()->count(30)->create();
        $initialCategories = $categories->take(10); // Take 10 categories for assignment

        // Assign 10 categories to the user
        $response = $this->postJson('/api/' . config('apiVersion.version') . '/event-category/assign', [
            'user_id' => $user->id,
            'category_id' => $initialCategories->pluck('id')->toArray(),
        ]);

        $response->assertStatus(200);

        // Assert the initial 10 categories are assigned
        foreach ($initialCategories as $category) {
            $this->assertDatabaseHas('user_event_categories', [
                'user_id' => $user->id,
                'event_category_id' => $category->id,
            ]);
        }

        // Pick 5 random categories from the initially assigned 10 categories
        $categoriesToRemove = $initialCategories->random(5);

        // Prepare the categories to keep
        $categoriesToKeep = $initialCategories->diff($categoriesToRemove);

        // Sync the remaining categories to the user (this will remove the picked categories)
        $response = $this->postJson('/api/' . config('apiVersion.version') . '/event-category/assign', [
            'user_id' => $user->id,
            'category_id' => $categoriesToKeep->pluck('id')->toArray(),
        ]);

        $response->assertStatus(200);

        // Assert the removed categories are no longer assigned
        foreach ($categoriesToRemove as $category) {
            $this->assertDatabaseMissing('user_event_categories', [
                'user_id' => $user->id,
                'event_category_id' => $category->id,
            ]);
        }

        // Assert the remaining categories are still assigned
        foreach ($categoriesToKeep as $category) {
            $this->assertDatabaseHas('user_event_categories', [
                'user_id' => $user->id,
                'event_category_id' => $category->id,
            ]);
        }
    }
}
