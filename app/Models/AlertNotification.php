<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class AlertNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_alert_id',
        'car_id',
        'is_sent',
        'sent_at',
    ];

    protected $casts = [
        'is_sent' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function buyerAlert(): BelongsTo
    {
        return $this->belongsTo(BuyerAlert::class);
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }
}
