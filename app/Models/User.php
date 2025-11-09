<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Builder;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'contact_number', 'status'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'role' => 'string',
        'status' => 'string',
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

    public function getAvatarUrlAttribute(): string
    {
        return asset('assets/img/avatars/1.png');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === 'active' ? 'Active' : 'Inactive';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->status === 'active' ? 'bg-success' : 'bg-secondary';
    }

    public function routeNotificationForMail($notification = null)
{
    if (app()->environment(['local', 'staging'])) {
        return 'naquib@lumimarketing.com.my';
    }
    return $this->email;
}
}