<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\EventCategory;

use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Config;

class UserCategoryTest extends TestCase
{

    use RefreshDatabase;

    protected $api;
    protected $authToken;

    protected function setUp(): void
    {
        parent::setUp();
        // Initialize the API endpoint base with the version
        $this->api = '/api/' . config('apiVersion.version');
    }

    /** @test */
    public function can_list_categories(): void
    {

        //because its a protected route, we need this else it fails
        $user = User::factory()->create();
        Passport::actingAs($user);

        $categories = EventCategory::factory()->count(4)->create();

        $response = $this->get($this->api .'/event-category');
        $response->assertStatus(200);

        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'name',
                ]
            ]
        ]);

        $response->assertJson([
            'data' => $categories->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
            ])->toArray()
        ]);

    }


    /** @test */
    public function can_create_categories(): void
    {

        //because its a protected route, we need this else it fails
        $user = User::factory()->create();
        Passport::actingAs($user);

        $data = [
            'name' => 'New Category'
        ];

        $response = $this->post($this->api .'/event-category', $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('event_categories', $data);
    }


    /** @test */
    public function can_update_categories(): void
    {

        //because its a protected route, we need this else it fails
        $user = User::factory()->create();
        Passport::actingAs($user);

        $category = EventCategory::factory()->create([
            'name' => 'Old Name'
        ]);

        $data = [
            'name' => 'Updated Name'
        ];

        $response = $this->put($this->api . "/event-category/{$category->id}", $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('event_categories', [
            'id' => $category->id,
            'name' => 'Updated Name'
        ]);
    }

    /** @test */
    public function can_delete_categories(): void
    {

        //because its a protected route, we need this else it fails
        $user = User::factory()->create();
        Passport::actingAs($user);

        $category = EventCategory::factory()->create();
        $response = $this->delete($this->api . "/event-category/{$category->id}");
        $response->assertStatus(200);

        $this->assertDatabaseMissing('event_categories', [
            'id' => $category->id
        ]);
    }


    /** @test */
    public function can_assign_single_category_to_user(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        $user = User::factory()->create();

        //because its a protected route, we need this else it fails
        Passport::actingAs($user);

        $categories = EventCategory::factory()->count(2)->create();

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

        $categories = EventCategory::factory()->count(31)->create();

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
        $categories = EventCategory::factory()->count(31)->create();
        $initialCategories = $categories->take(11); // Take 10 categories for assignment

        // Assign 11 categories to the user
        $response = $this->postJson('/api/' . config('apiVersion.version') . '/event-category/assign', [
            'user_id' => $user->id,
            'category_id' => $initialCategories->pluck('id')->toArray(),
        ]);

        $response->assertStatus(200);

        // Assert the initial 11 categories are assigned
        foreach ($initialCategories as $category) {
            $this->assertDatabaseHas('user_event_categories', [
                'user_id' => $user->id,
                'event_category_id' => $category->id,
            ]);
        }

        // Pick 6 random categories from the initially assigned 10 categories
        $categoriesToRemove = $initialCategories->random(6);

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
