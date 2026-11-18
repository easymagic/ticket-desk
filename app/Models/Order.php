<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id', 'number', 'status', 'total_cents', 'currency', 'tracking_number', 'items',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'total_cents' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function refundRequests(): HasMany
    {
        return $this->hasMany(RefundRequest::class);
    }
}
