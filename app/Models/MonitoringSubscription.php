<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MonitoringSubscription extends Model
{
    protected $fillable = [
        'token', 'url', 'email', 'price_cents',
        'active_until', 'last_run_at', 'last_score_total',
        'last_audit_uuid', 'status', 'payment_reference',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'active_until' => 'datetime',
        'last_run_at' => 'datetime',
        'last_score_total' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (MonitoringSubscription $s) {
            if (empty($s->token)) {
                $s->token = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->active_until !== null
            && $this->active_until->isFuture();
    }
}
