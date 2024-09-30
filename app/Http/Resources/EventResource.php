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
            "created_at" => $this->created_at,
        ];
    }

}
