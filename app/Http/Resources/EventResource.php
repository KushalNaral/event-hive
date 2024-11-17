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
            'description' => $this->description,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'location' => $this->location,
            "expected_participants" => $this->expected_participants,

            "image" => $this->image?->url,

            "total_involved_participants" => $this->total_involved_participants,

            'bookmarked' => $this->getInteractions('bookmark') ? true : false,
            'registered' => $this->getInteractions('register') ? true : false,
            'liked' => $this->getInteractions('like') ? true : false,
            'not_interested' => $this->getInteractions('dis-like') ? true : false,
            'attending' => $this->getInteractions('attend') ? true : false,

            'published' => $this->is_published ? true : false,

            'total_views' => $this->getTotalInteractions('view'),
            'total_bookmarked' => $this->getTotalInteractions('bookmark'),
            'total_attending' => $this->getTotalInteractions('view'),
            'total_registered' => $this->getTotalInteractions('view'),

            'total_rating' => $this->getTotalRating() ?? 0,
            'my_rating' => $this->getUserRating(),

            'is_over' => $this->is_over,
            'is_running' => $this->is_running,

            "category_id" => $this->category_id,
            "category" => $this->category,
            "created_by" => [ "id" => $this->createdBy?->id , "name" => $this->createdBy?->name ],
            "created_at" => $this->created_at,
        ];
    }

}
