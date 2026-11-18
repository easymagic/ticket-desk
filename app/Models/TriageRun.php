<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TriageRun extends Model
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';
    public const STATUS_WAITING_FOR_APPROVAL = 'waiting-for-approval';

    protected $fillable = [
        'ticket_id', 'status', 'idempotency_key', 'conversation_id', 'result', 'trace',
        'pending_approvals', 'steps', 'input_tokens', 'output_tokens', 'error', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'result' => 'array',
            'trace' => 'array',
            'pending_approvals' => 'array',
            'finished_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function refundRequests(): HasMany
    {
        return $this->hasMany(RefundRequest::class);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_SUCCEEDED, self::STATUS_FAILED], true);
    }
}
