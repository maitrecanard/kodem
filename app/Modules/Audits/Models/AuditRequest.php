<?php

declare(strict_types=1);

namespace App\Modules\Audits\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditRequest extends Model
{
    protected $table = 'audit_requests';

    protected $fillable = [
        'uuid',
        'type',
        'status',
        'name',
        'email',
        'domain',
        'options',
        'amount_cents',
        'currency',
        'stripe_session_id',
        'stripe_payment_intent',
        'paid_at',
        'access_token',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'options'      => 'array',
        'paid_at'      => 'datetime',
        'amount_cents' => 'integer',
    ];

    protected $hidden = [
        'id',
        'access_token',
        'stripe_session_id',
        'stripe_payment_intent',
        'ip_address',
        'user_agent',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
