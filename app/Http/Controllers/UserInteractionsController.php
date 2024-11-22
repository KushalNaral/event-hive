<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserInteractionRequest;
use App\Models\UserInteractions;
use App\Models\EventLikes;
use App\Models\EventBookmarks;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Notifications\EventRegistrationConfirmation;
use Illuminate\Support\Facades\Auth;
use App\Models\Event;

use Exception;

class UserInteractionsController extends Controller
{
    public function getDefaultInteractions()
    {
        $interactions = UserInteractions::interactions();
        return successResponse($interactions, 'Default Interactions Fetched Successfully', 200);
    }

    public function setInteractions(UserInteractionRequest $request)
    {
        try {
            $params = $request->validated();
            $userId = auth()->user()->id;
            $eventId = $params['event_id'];
            $interactionType = $params['interaction_type'];

            DB::beginTransaction();

            //$this->deleteInteraction($userId, $eventId, $interactionType);
            if($interactionType == 'attend') {
                $this->registerForEvent($eventId);
            }

            if ($interactionType === 'like' || $interactionType === 'un-like' || $interactionType === 'dis-like') {

                $this->removePreviousSetActions(['like', 'un-like', 'dis-like'], $userId, $eventId, $interactionType);
                $this->handleLikeInteraction($userId, $eventId, $interactionType);
            }

            if ($interactionType === 'bookmark' || $interactionType === 'un-bookmark') {
                $this->removePreviousSetActions(['bookmark', 'un-bookmark'], $userId, $eventId, $interactionType);
                $this->handleBookmarkInteraction($userId, $eventId, $params);
            }

            $this->storeUserInteraction($userId, $eventId, $interactionType);

            DB::commit();

            return successResponse(null, ucfirst($interactionType) . " action completed successfully", 200);

        } catch (QueryException $q) {
            DB::rollBack();
            return errorResponse('Database error occurred while setting interaction.', 500, [$q->getMessage()]);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse('An unexpected error occurred.', 500, [$e->getMessage()]);
        }
    }


    protected function handleLikeInteraction($userId, $eventId, $interactionType)
    {
        $existingLike = EventLikes::where('user_id', $userId)
            ->where('event_id', $eventId)
            ->first();

        if ($interactionType === 'like') {
            if (!$existingLike) {
                EventLikes::create(['user_id' => $userId, 'event_id' => $eventId]);
            }
        } else {
            if ($existingLike) {
                $existingLike->delete();
            }
        }
    }

    protected function handleBookmarkInteraction($userId, $eventId, $params)
    {
        $existingBookmark = EventBookmarks::where('user_id', $userId)
            ->where('event_id', $eventId)
            ->first();

        $notes = $params['notes'] ?? null;

        if ($existingBookmark) {
            $existingBookmark->delete();
        } else {
            EventBookmarks::create(['user_id' => $userId, 'event_id' => $eventId, 'notes' => $notes]);
        }
    }

    protected function storeUserInteraction($userId, $eventId, $interactionType)
    {
        UserInteractions::where('user_id', $userId)
            ->where('event_id', $eventId)
            ->where('interaction_type', $interactionType)
            ->delete();  // Remove any existing interaction record

        UserInteractions::create([
            'user_id' => $userId,
            'event_id' => $eventId,
            'interaction_type' => $interactionType
        ]);
    }

    public function getAllInteractions()
    {
        try {
            $interactions = UserInteractions::all();
            return successResponse($interactions, 'Interaction(s) fetched successfully');
        } catch (\Throwable $th) {
            return errorResponse($th->getMessage(), $th->getStatusCode(), $th->errors());
        }
    }

    public function getAllInteractionsForUser(Request $request, $user_id)
    {
        try {
            $filters = null;
            $interactions = UserInteractions::where('user_id', $user_id);

            if (isset($request) && isset($request->interaction_type)) {
                $filters = $request->interaction_type;
                $interactions = $interactions->where('interaction_type', $filters);
            }

            return successResponse($interactions->get(), 'Interaction(s) fetched successfully');
        } catch (\Throwable $th) {
            return errorResponse($th->getMessage(), $th->getStatusCode(), $th->errors());
        }
    }

    public function getInteractionForEvent(Request $request, $event_id)
    {
        try {
            $filters = null;
            $interactions = UserInteractions::where('event_id', $event_id);

            if (isset($request) && isset($request->interaction_type)) {
                $filters = $request->interaction_type;
                $interactions = $interactions->where('interaction_type', $filters);
            }

            return successResponse($interactions->get(), 'Event(s) fetched successfully');
        } catch (\Throwable $th) {
            return errorResponse($th->getMessage(), $th->getStatusCode(), $th->errors());
        }
    }

    public function getInteractionForEventAndUser(Request $request, $event_id)
    {
        try {
            $filters = null;
            $interactions = UserInteractions::where('user_id', auth()->user()->id)->where('event_id', $event_id);

            if (isset($request) && isset($request->interaction_type)) {
                $filters = $request->interaction_type;
                $interactions = $interactions->where('interaction_type', $filters);
            }

            return successResponse($interactions->get(), 'Event(s) fetched successfully');
        } catch (\Throwable $th) {
            return errorResponse($th->getMessage(), $th->getStatusCode(), $th->errors());
        }
    }

    private function deleteInteraction($userId, $eventId, $interactionType)
    {
        UserInteractions::where('user_id', $userId)
            ->where('event_id', $eventId)
            ->where('interaction_type', $interactionType)
            ->delete();
    }

    /**
     * @param array $interactions
     * @param "like"|"un-like"|"dis-like" $interactionType
     */
    private function removePreviousSetActions(array $interactions, $userId, $eventId, string $interactionType)
    {
        foreach ($interactions as $interaction) {
            if ($interaction != $interactionType) {
                UserInteractions::where('user_id', $userId)
                    ->where('event_id', $eventId)
                    ->where('interaction_type', $interaction)
                    ->delete();
            }
        }
    }


    public function registerForEvent($eventId)
    {
        try {
            DB::beginTransaction();

            $event = Event::findOrFail($eventId);
            $user = Auth::user();

            $exists = UserInteractions::where('event_id', $eventId)->where('user_id', $user->id)->exists();
            if ($exists) {
                return errorResponse('User is already registered for this event', 400);
            }

            $interaction = UserInteractions::create([
                'user_id' => $user->id,
                'event_id' => $eventId,
                'interaction_type' => 'attend'
            ]);

            $user->notify(new EventRegistrationConfirmation($event));

            DB::commit();

            return successResponse(null, 'Successfully registered for event');
        } catch (\Exception $e) {
            // Rollback any changes if an error occurs
            DB::rollBack();

            return errorResponse('Failed to register for event', 500, [$e->getMessage()]);
        }
    }
}

