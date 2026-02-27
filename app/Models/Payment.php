<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_slug',
        'dealer_id',
        'payment_type',
        'amount',
        'phone_number',
        'payment_method',
        'status',
        'network',
        'transaction_id',
        'duration_days',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'expires_at' => 'datetime',
        'metadata'   => 'array',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function paymentCars(): HasMany
    {
        return $this->hasMany(PaymentCar::class);
    }
}
