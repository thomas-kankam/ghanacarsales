<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionArchive extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_slug',
        'dealer_slug',
        'plan_name',
        'duration_days',
        'price',
        'plan_slug',
        'status',
        'starts_at',
        'expiry_date',
        'reference_id',
        'features',
    ];

    protected $casts = [
        'price'    => 'decimal:2',
        'features' => 'array',
    ];
}
