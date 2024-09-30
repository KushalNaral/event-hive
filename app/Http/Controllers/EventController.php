<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventCollection;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\EventCategory;
use App\Services\RecommendationEngine;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Database\QueryException;
use GuzzleHttp\Client;
use App\Traits\ImageUploader;

class EventController extends Controller
{
    use ImageUploader;

    public function getAllEvents(Request $request){
        try {
            $events = Event::all();
            return successResponse( EventResource::collection(new EventCollection($events)), 'Event(s) fetched successfully');
        } catch (\Throwable $th) {
            return errorResponse($th->getMessage(), $th->getStatusCode(), $th->errors() );
        }
    }

    public function createEvents(CreateEventRequest $request){
        $params = $request->validated();
        try {
            DB::beginTransaction();
            $event = Event::create($params);
            if($event){
                $this->generateAndSetEventAttributes($event);
            }

            if($request->hasFile('image')){
                $uploadedFile = $this->handleFileUpload($request->image, 'uploads', 'App\Models\Event', $event->id);
            }

            DB::commit();
            return successResponse($event, "Event Created Successfully", 200);
        } catch (QueryException $q) {
            DB::rollBack();
            return errorResponse('Database error occurred while creating event.', 500, [$q->getMessage()]);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse('An unexpected error occurred.', 500, [$e->getMessage()]);
        }
    }

    public function updateEventById(UpdateEventRequest $request, $id){
        $params = $request->validated();
        try {
            DB::beginTransaction();
            $event = Event::where('id', $id)->first();
            if(!$event || $event == null){
                return errorResponse('The selected event does not exist', 404, []);
            }
            $update_status = $event->update($params);
            if(!$update_status || $update_status == null){
                return errorResponse('An error occured, please try again later', 400, []);
            }

            //if($request->hasFile('image')){
            //   $this->updateFile($event->image, $request('image'), 'uploads');
            //}

            DB::commit();
            $this->generateAndSetEventAttributes($event);
            return successResponse($event, "Event Updated Successfully", 200);
        } catch (QueryException $q) {
            DB::rollBack();
            return errorResponse('Database error occurred while updating event.', 500, [$q->getMessage()]);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse('An unexpected error occurred.', 500, [$e->getMessage()]);
        }
    }

    public function deleteEventById($id){
        try {
            DB::beginTransaction();
            $event = Event::where('id', $id)->first();
            if(!$event || $event == null){
                return errorResponse('The selected event does not exist', 404, []);
            }
            $delete_status = $event->delete();
            if(!$delete_status || $delete_status == null){
                return errorResponse('An error occured, please try again later', 400, []);
            }
            DB::commit();
            return successResponse($event, "Event Deleted Successfully", 200);
        } catch (QueryException $q) {
            DB::rollBack();
            return errorResponse('Database error occurred while deleting event.', 500, [$q->getMessage()]);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse('An unexpected error occurred.', 500, [$e->getMessage()]);
        }
    }

    public function getEventById($id){
        try {
            DB::beginTransaction();
            $event = Event::where('id', $id)->first();
            if(!$event || $event == null){
                return errorResponse('The selected event does not exist', 404, []);
            }
            DB::commit();
            $mapped_event =  [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'start_date' => $event->start_date,
                'end_date' => $event->end_date,
                'location' => $event->location,
                "expected_participants" => $event->expected_participants,
                "total_involved_participants" => $event->total_involved_participants,

                "image" => $event->image?->url,

                'bookmarked' => $event->getInteractions('bookmark') ? true : false,
                'registered' => $event->getInteractions('register') ? true : false,

                'total_views' => $event->getTotalInteractions('view'),
                'total_bookmarked' => $event->getTotalInteractions('bookmark'),
                'total_attending' => $event->getTotalInteractions('view'),
                'total_registered' => $event->getTotalInteractions('view'),

                'logged_user_rating' => $event->getUserRating(),

                "category_id" => $event->category_id,
                "category" => $event->category,
                "created_by" => [ "id" => $event->createdBy?->id , "name" => $event->createdBy?->name ],
                "created_at" => $event->created_at,
            ];

            return successResponse($mapped_event, 'Event(s) fetched successfully');
        } catch (QueryException $q) {
            DB::rollBack();
            return errorResponse('Database error occurred while deleting event.', 500, [$q->getMessage()]);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse('An unexpected error occurred.', 500, [$e->getMessage()]);
        }


    }

    //attribute section
    private function generateAndSetEventAttributes(Event $event)
    {
        $attributeSchema = [
            'duration_days' => null,
            'is_weekend' => null,
            'days_until_event' => null,
            'category_name' => null,
            'event_size' => null,
            'season' => null,
            'is_holiday' => null,
            'time_of_day' => null,
            'location_type' => null,
            'organizer_reputation' => null,
            'key_themes' => [],
            'formality_level' => null
        ];

        $startDate = Carbon::parse($event->start_date);
        $endDate = Carbon::parse($event->end_date);

        $attributes = $attributeSchema;

        $attributes['duration_days'] = $endDate->diffInDays($startDate) + 1;
        $attributes['is_weekend'] = $startDate->isWeekend() || $endDate->isWeekend();
        $attributes['days_until_event'] = now()->diffInDays($startDate, false);

        $category = EventCategory::find($event->category_id);
        $attributes['category_name'] = $category ? $category->name : null;

        $attributes['event_size'] = $this->classifyEventSize($event->expected_participants);
        $attributes['season'] = $this->getSeason($startDate);
        $attributes['is_holiday'] = $this->isHoliday($startDate);
        $attributes['time_of_day'] = $this->getTimeOfDay($startDate);

        $attributes['location_type'] = $this->classifyLocation($event->location);
        $attributes['organizer_reputation'] = $this->getOrganizerReputation($event->created_by);
        $attributes['key_themes'] = $this->extractKeyThemes($event->description);
        $attributes['formality_level'] = $this->assessFormality($event->description, $event->title);

        $event->attributes = json_encode($attributes);
        $event->save();

        return $event;
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
        $month = $date->month;
        return match (true) {
            $month >= 3 && $month <= 5 => 'spring',
            $month >= 6 && $month <= 8 => 'summer',
            $month >= 9 && $month <= 11 => 'autumn',
            default => 'winter',
        };
    }

    private function isHoliday($date)
    {
        $holidays = [
            '01-01' => 'New Year\'s Day',
            '12-25' => 'Christmas Day',
            '09-25' => 'Ashtami',
            '10-03' => 'Ghatathapana',
            '10-24' => 'Fulpati',
            '10-25' => 'Nawami',
        ];

        return array_key_exists($date->format('m-d'), $holidays);
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

    private function getOrganizerReputation($userId)
    {
        $pastEvents = Event::where('created_by', $userId)
            ->where('end_date', '<', now())
            ->with('rating')
            ->get();

        if ($pastEvents->isEmpty()) {
            return null;
        }

        $totalRating = 0;
        $ratingCount = 0;

        foreach ($pastEvents as $event) {
            if ($event->rating) {
                $totalRating += $event->rating->rating;
                $ratingCount++;
            }
        }

        return $ratingCount > 0 ? $totalRating / $ratingCount : null;
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

    private function assessFormality($description, $title)
    {
        $formalWords = ['conference', 'symposium', 'gala', 'ceremony', 'formal', 'professional'];
        $casualWords = ['party', 'hangout', 'meetup', 'casual', 'relaxed'];

        $text = strtolower($title . ' ' . $description);
        $formalCount = 0;
        $casualCount = 0;

        foreach ($formalWords as $word) {
            if (strpos($text, $word) !== false) {
                $formalCount++;
            }
        }

        foreach ($casualWords as $word) {
            if (strpos($text, $word) !== false) {
                $casualCount++;
            }
        }

        if ($formalCount > $casualCount) {
            return 'formal';
        } elseif ($casualCount > $formalCount) {
            return 'casual';
        } else {
            return 'semi-formal';
        }
    }

    public function getRecommendations(Request $request)
    {
        $user = Auth::user();
        $recommendationEngine = new RecommendationEngine();
        $recommendedEventIds = $recommendationEngine->getRecommendations($user, 100);


        $recommendedEvents = Event::whereIn('id', array_keys($recommendedEventIds))
            ->orderByRaw("FIELD(id, " . implode(',', array_keys($recommendedEventIds)) . ")")
            ->get();
        //->pluck('id');

        return successResponse( EventResource::collection(new EventCollection($recommendedEvents)), "Recommended events", 200);

    }

    public function getUserEvents()
    {
        try {
            $events = Event::where('created_by', auth()->user()->id)->get();

            if(!$events && count($events) <= 0){
                return successResponse([], "You have not created any events yet", 200);
            }
            return successResponse( EventResource::collection(new EventCollection($events)), 'Event(s) fetched successfully');
        } catch (\Throwable $th) {
            return errorResponse($th->getMessage(), $th->getStatusCode(), $th->errors() );
        }
    }
}
