<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_slug',
        'dealer_id',
        'subscription_id',
        'plan_id',
        'payment_type',
        'amount',
        'phone_number',
        'payment_method',
        'provider',
        'channel',
        'status',
        'network',
        'transaction_id',
        'reference',
        'duration_days',
        'expires_at',
        'metadata',
        'raw_callback',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'expires_at'   => 'datetime',
        'metadata'     => 'array',
        'raw_callback' => 'array',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

//     public function plan(): BelongsTo
//     {
//         return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
//     }
}
