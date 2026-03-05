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
        'dealer_slug',
        'plan_name',
        'plan_slug',
        'status',
        'phone_number',
        'network',
        'reference_id',
        'amount',
        'payment_method',
        'duration_days',
        'car_slugs',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'car_slugs'     => 'array',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class, 'dealer_slug', 'dealer_slug');
    }

//     public function plan(): BelongsTo
//     {
//         return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
//     }
}
