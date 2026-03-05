<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'dealer_slug',
        'subscription_slug',
        'plan_slug',
        'plan_name',
        'duration_days',
        'starts_at',
        'expiry_date',
        'status',
        'price'
    ];

    protected $casts = [
        'starts_at'   => 'datetime',
        'expiry_date' => 'datetime',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class, 'dealer_slug', 'dealer_slug');
    }
}

