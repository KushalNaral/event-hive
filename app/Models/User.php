<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'otp',
        'otp_verified_at',
        'first_login',
        'email_verified_at',
        'preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    //updating preferences here
    public function updatePreferences(array $preferences)
    {
        $this->preferences = array_merge($this->preferences ?? [], $preferences);
        $this->save();
    }

    //event-cat relation
    public function categories()
    {
        return $this->belongsToMany(EventCategory::class, 'user_event_categories');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    // Check if the user has a specific role
    public function hasRole($roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    // Assign a role to the user
    public function assignRole($role)
    {
        $this->roles()->attach($role);
    }

    // Remove a role from the user
    public function removeRole($role)
    {
        $this->roles()->detach($role);
    }
}
