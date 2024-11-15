<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;

class AdminEventController extends Controller
{
    public function publish($id)
    {
        try {
            $event = Event::findOrFail($id);

            // Toggle the published status
            $event->is_published = !$event->is_published;
            $event->save();

            $status = $event->is_published ? 'published' : 'unpublished';
            return successResponse(
                new EventResource($event),
                "Event $status successfully"
            );

        } catch (\Throwable $th) {
            return errorResponse(
                $th->getMessage(),
                $th->getCode() ?: 500,
                $th?->errors() ?? []
            );
        }
    }

    /**
     * Toggle the verified status of an event
     */
    public function verify($id)
    {
        try {
            $event = Event::findOrFail($id);

            // Toggle the verified status
            $event->is_verified = !$event->is_verified;
            $event->save();

            $status = $event->is_verified ? 'verified' : 'unverified';
            return successResponse(
                new EventResource($event),
                "Event $status successfully"
            );

        } catch (\Throwable $th) {
            return errorResponse(
                $th->getMessage(),
                $th->getCode() ?: 500,
                $th?->errors() ?? []
            );
        }
    }}
