<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventBookmarks extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'event_id', 'notes'];

    protected $table = "event_booksmarks";
}
