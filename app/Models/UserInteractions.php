<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInteractions extends Model
{
    use HasFactory;

    protected $fillable= [
        'interaction_type',
        'event_id',
        'user_id'
    ];

    // this is the basis for user and event UserInteractions
    // helps in settign some user behaviour stats
    public static function interactions(){

        $default_interactions = ['view', 'bookmark', 'un-bookmark', 'register', 'attend', 'feedback', 'like', 'un-like', 'dis-like'];
        return $default_interactions;
    }

    protected $with = ['user', 'event'];

    //user relation
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function event(){
        return $this->hasOne(Event::class, 'id', 'event_id');
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($event) {
            $event->user_id = auth()->user()->id;
        });
    }
}
