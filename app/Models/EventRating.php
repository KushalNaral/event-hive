<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventRating extends Model
{
    use HasFactory;

    protected $table = "event_ratings";

    protected $fillable = [
        'rating',
        'event_id'
    ];

    protected $with = ['user', 'event'];

    //user relation
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function event(){
        return $this->hasOne(Event::class, 'id', 'event_id');
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($rating) {
            $rating->created_by = auth()->user()->id;
        });
    }
}
