<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\User;

use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EventTest extends TestCase
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
    public function can_list_events()
    {

        //because its a protected route, we need this else it fails
        $user = User::factory()->create();
        Passport::actingAs($user);

            $categories = EventCategory::factory()->count(4)->create();
        $event = Event::factory()->count(10)->create();

        $response = $this->get($this->api .'/events');
        $response->assertStatus(200);

    }

    /** @test */
    public function can_create_events(): void
    {
        //because its a protected route, we need this else it fails
        $user = User::factory()->create();
        Passport::actingAs($user);

        $categories = EventCategory::factory()->count(4)->create();

        $data = [
            'title' => 'Seminar for the soul',
            'description' => 'A soul inducing seminar for the new generation',
            'start_date' => '2024/12/01 7:00',
            'end_date' => '2024/12/15',
            'location' => 'Kathmandu',
            'expected_participants' => 10,
            'category_id' => EventCategory::latest()->first()->id,
            'time' => '12:00',
        ];

        $response = $this->post($this->api .'/events', $data);
        $response->assertStatus(200);
    }

    /** @test */
    public function can_update_events(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $category = EventCategory::factory()->create();

        $event = Event::factory()->create([
            'title' => 'event 1',
            'description' => 'Initial description',
            'location' => 'Initial location',
            'expected_participants' => 10,
            'category_id' => $category->id
        ]);

        $update_data = [
            'title' => 'event 1 updated',
            'description' => 'Updated description',
            'location' => 'Updated location',
            'expected_participants' => 20,
            'category_id' => $category->id,
        ];

        $response = $this->post($this->api . "/events/{$event->id}", $update_data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'event 1 updated',
            'description' => 'Updated description',
            'location' => 'Updated location',
            'expected_participants' => 20,
            'category_id' => $category->id,
        ]);
    }

    /** @test */
    public function can_update_event_category_id(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $oldCategory = EventCategory::factory()->create();
        $newCategory = EventCategory::factory()->create();

        $event = Event::factory()->create([
            'title' => 'event 1',
            'description' => 'Initial description',
            'location' => 'Initial location',
            'expected_participants' => 10,
            'category_id' => $oldCategory->id
        ]);

        $update_data = [
            'title' => 'event 1',
            'description' => 'Initial description',
            'location' => 'Initial location',
            'expected_participants' => 10,
            'category_id' => $newCategory->id,
        ];

        $response = $this->post($this->api . "/events/{$event->id}", $update_data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'event 1',
            'description' => 'Initial description',
            'location' => 'Initial location',
            'expected_participants' => 10,
            'category_id' => $newCategory->id,
        ]);
    }

    /** @test */
    public function can_delete_events(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $category = EventCategory::factory()->create();
        $event = Event::factory()->create([
            'category_id' => $category->id
        ]);

        $response = $this->delete($this->api . "/events/{$event->id}");
        $response->assertStatus(200);

        $this->assertDatabaseMissing('event_categories', [
            'id' => $event->id
        ]);
    }

    /** @test */
    public function can_get_specific_event(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $category = EventCategory::factory()->create();
        $event = Event::factory()->create([
            'category_id' => $category->id,
            'is_published' => 1,
        ]);

        $response = $this->get($this->api . "/events/{$event->id}");
        $response->assertStatus(200);

    }

    /** @test */
    public function canSetEventAttributes(): void
    {
        // Create a user and authenticate
        $user = User::factory()->create();
        Passport::actingAs($user);

        // Create a category and an event
        $category = EventCategory::factory()->create();
        $event = Event::factory()->create([
            'category_id' => $category->id,
        ]);

        // Decode attributes and compare keys
        // doing array diff here insures that key are same as array_diff([1], [1]) => []
        $eventAttributes = json_decode($event->attributes, true);
        $fetchedAttributes = json_decode(Event::find($event->id)->attributes, true);

        $this->assertEquals(count( array_diff( array_keys($eventAttributes), array_keys($fetchedAttributes) ) ), 0 );
    }
}
