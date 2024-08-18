<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EventCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug'
    ];

    //user relation
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_event_categories');
    }

    //creating unique slug
    protected function generateUniqueSlug($name)
    {
        $slug = Str::slug($name);
        $count = static::where('slug', 'like', "$slug%")->count();

        return $count > 0 ? "{$slug}-{$count}" : $slug;
    }

    //while an entry is being created, a unique slug will be generated
    public static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            $post->slug = $post->generateUniqueSlug($post->name);
        });
    }
}
