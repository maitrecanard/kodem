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
        'pdf_price_cents', 'pdf_paid_at',
        'cwv_price_cents', 'cwv_paid_at', 'cwv_results',
    ];

    protected $casts = [
        'results' => 'array',
        'score_seo' => 'integer',
        'score_security' => 'integer',
        'score_total' => 'integer',
        'price_cents' => 'integer',
        'paid_at' => 'datetime',
        'pdf_price_cents' => 'integer',
        'pdf_paid_at' => 'datetime',
        'cwv_price_cents' => 'integer',
        'cwv_paid_at' => 'datetime',
        'cwv_results' => 'array',
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

    public function isPdfPaid(): bool
    {
        return $this->pdf_paid_at !== null;
    }

    public function isCwvPaid(): bool
    {
        return $this->cwv_paid_at !== null;
    }
}
