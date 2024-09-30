<?php

namespace App\Http\Resources;

use App\Models\UserInteractions;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'title' => $this->title,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'location' => $this->location,
            "expected_participants" => $this->expected_participants,
            "total_involved_participants" => $this->total_involved_participants,

            'bookmarked' => $this->getInteractions('bookmark') ? true : false,

            'total_views' => $this->getTotalInteractions('view'),
            'total_bookmarked' => $this->getTotalInteractions('bookmark'),
            'total_attending' => $this->getTotalInteractions('view'),
            'total_registered' => $this->getTotalInteractions('view'),

            'logged_user_rating' => $this->getUserRating(),

            "category_id" => $this->category_id,
            "category" => $this->category,
            "created_by" => [ "id" => $this->createdBy?->id , "name" => $this->createdBy?->name ],
            //"attributes" => $this->attributes ? json_decode($this->attributes) : null,
            "created_at" => $this->created_at,
        ];
        return parent::toArray($request);
    }

    private function getInteractions($type)
    {
        $event_id = $this->id;
        $user_id = auth()->user()->id;

        $interaction = UserInteractions::where('user_id', $user_id)->where('event_id', $event_id)->where('interaction_type', $type)->latest()->first();

        return $interaction ?? 0;

        dd($event_id, $user_id, $type, $interaction);
    }

    private function getTotalInteractions($type)
    {
        $event_id = $this->id;

        $interaction = UserInteractions::where('event_id', $event_id)->where('interaction_type', $type)->get()->count();
        return $interaction ?? 0;
    }

    private function getUserRating()
    {
        $event_id = $this->id;
        $user_id = auth()->user()->id;

        $rating = Rating::where('event_id', $event_id)->where('created_by', $user_id)->latest()->first()?->rating ;
        return $rating ?? 'not-rated';
    }
}
