<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditFollowup extends Model
{
    public const REASON_LOW_SCORE = 'low_score';

    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_BOUNCED = 'bounced';

    public $timestamps = false;

    protected $fillable = [
        'audit_id', 'email', 'reason', 'score_at_send',
        'subject', 'status', 'error', 'message_id',
        'metadata', 'sent_at', 'opened_at', 'clicked_at',
    ];

    protected $casts = [
        'score_at_send' => 'integer',
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }
}
