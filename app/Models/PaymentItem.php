<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_slug',
        'car_slug',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_slug', 'payment_slug');
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'car_slug', 'car_slug');
    }
}
