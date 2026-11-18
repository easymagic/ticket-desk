<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    /** @use HasFactory<\Database\Factories\TicketFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id', 'order_id', 'subject', 'body', 'priority', 'status', 'draft_reply',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(TicketNote::class);
    }

    public function triageRuns(): HasMany
    {
        return $this->hasMany(TriageRun::class);
    }
}
