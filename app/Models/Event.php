<?php

namespace App\Models;

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

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($event) {
            $event->created_by = auth()->user()->id;
        });
    }
}

