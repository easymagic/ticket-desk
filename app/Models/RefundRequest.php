<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundRequest extends Model
{
    protected $fillable = [
        'order_id', 'ticket_id', 'triage_run_id', 'amount_cents', 'reason', 'status',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function triageRun(): BelongsTo
    {
        return $this->belongsTo(TriageRun::class);
    }
}
