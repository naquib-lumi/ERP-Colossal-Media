<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Builder;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'contact_number'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'role' => 'string',
    ];

    public function hasRole($role)
    {
        return $this->role === $role;
    }

    public function scopeRole(Builder $query, $role): Builder
    {
        return $query->where('role', $role);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class, 'salesperson_id');
    }

    public function meetings()
    {
        return $this->hasMany(Meeting::class, 'user_id');
    }

    public function getDisplayRoleAttribute(): string
    {
        return str_replace('-', ' ', ucwords((string) $this->role, " -_"));
    }

    // Default avatar url (use your theme’s default)
    public function getAvatarUrlAttribute(): string
    {
        // change to your actual default image if different
        return asset('assets/img/avatars/1.png');
    }

    // Simple status; adjust if you later add a real status column
    public function getStatusLabelAttribute(): string { return 'Active'; }
    public function getStatusBadgeClassAttribute(): string { return 'bg-success'; }
}