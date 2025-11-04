<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\Helpers;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'title',
        'description',
        'remind_at',
        'status',
        'is_auto',
        'last_notify_time',
        'created_by',
    ];
    protected static function booted()
{
    static::retrieved(function ($reminder) {
        if ($reminder->remind_at->isPast() && $reminder->status !== 'completed') {
            $reminder->update(['status' => 'overdue']);
        }
    });
}

    protected $casts = [
        'remind_at' => 'datetime',
        'is_auto' => 'boolean',
        'last_notify_time' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            Lead::class,
            'id',
            'id',
            'lead_id',
            'salesperson_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function notifyUser()
    {
        if (!$this->lead || !$this->lead->user) {
            Log::warning("Reminder ID {$this->id} has no associated lead or user.");
            return;
        }

        $user = $this->lead->user;
        try {
            Helpers::notify($user, "Reminder: {$this->title}", url('/leads/' . $this->lead_id), ['database', 'mail']);
            $this->update(['last_notify_time' => now()]);
        } catch (\Exception $e) {
            Log::error("Failed to send notification for Reminder ID {$this->id}: " . $e->getMessage());
        }
    }
}