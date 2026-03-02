<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'dealer_id',
        'plan_id',
        'starts_at',
        'ends_at',
        'status',
        'published_count',
        'metadata',
        'last_payment_id',
    ];

    protected $casts = [
        'starts_at'      => 'datetime',
        'ends_at'        => 'datetime',
        'published_count'=> 'integer',
        'metadata'       => 'array',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function lastPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'last_payment_id');
    }
}

