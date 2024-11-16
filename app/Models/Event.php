<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable= [
        "title",
        "description",
        "start_date",
        "end_date",
        "location",
        "expected_participants",
        "total_involved_participants",
        "category_id",
        "created_by",
        "attributes",
        "created_at",
        "updated_at",
    ];

    public function category(){
        return $this->hasOne(EventCategory::class, 'id');
    }

    public function createdBy(){
        return $this->hasOne(User::class, 'id');
    }

    public function rating(){
        return $this->hasOne(Rating::class);
    }

    public function image(){
        return $this->hasOne(Files::class, 'model_id');
    }

    public function interactions()
    {
        return $this->hasMany(UserInteractions::class);
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($event) {
            $event->created_by = auth()->user()->id;
        });
    }


    public function getInteractions($type)
    {
        $event_id = $this->id;
        $user_id = auth()->user()->id;

        $interaction = UserInteractions::where('user_id', $user_id)->where('event_id', $event_id)->where('interaction_type', $type)->latest()->first();

        return $interaction ?? 0;
    }

    public function getTotalInteractions($type)
    {
        $event_id = $this->id;

        $interaction = UserInteractions::where('event_id', $event_id)->where('interaction_type', $type)->get()->count();
        return $interaction ?? 0;
    }

    public function getUserRating()
    {
        $event_id = $this->id;
        $user_id = auth()->user()->id;

        $rating = Rating::where('event_id', $event_id)->where('created_by', $user_id)->latest()->first()?->rating ;
        return $rating ?? 'not-rated';
    }

    public function getIsOverAttribute()
    {
        return Carbon::now()->greaterThan($this->end_date);
    }

    public function getIsRunningAttribute()
    {
        return Carbon::now()->between($this->start_date, $this->end_date);
    }
}

