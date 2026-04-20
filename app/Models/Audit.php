<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Audit extends Model
{
    protected $fillable = [
        'uuid', 'url', 'email', 'type', 'status',
        'score_seo', 'score_security', 'score_total',
        'results', 'error', 'ip_hash',
        'price_cents', 'paid_at', 'payment_reference',
    ];

    protected $casts = [
        'results' => 'array',
        'score_seo' => 'integer',
        'score_security' => 'integer',
        'score_total' => 'integer',
        'price_cents' => 'integer',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Audit $audit) {
            if (empty($audit->uuid)) {
                $audit->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }
}
