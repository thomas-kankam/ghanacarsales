<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuyerAlert extends Actor
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'mobile_number',
        'email',
        'brand',
        'model',
        'min_year',
        'max_year',
        'min_mileage',
        'max_mileage',
        'mileage_unit',
        'min_price',
        'max_price',
        'swap_deals',
        'aircon',
        'registered',
        'fuel_type',
        'transmission',
        'colour',
        'is_active',
    ];

    protected $casts = [
        'min_price'  => 'decimal:2',
        'max_price'  => 'decimal:2',
        'swap_deals' => 'boolean',
        'aircon'     => 'boolean',
        'registered' => 'boolean',
        'is_active'  => 'boolean',
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    // public function brand(): BelongsTo
    // {
    //     return $this->belongsTo(Brand::class);
    // }

    // public function model(): BelongsTo
    // {
    //     return $this->belongsTo(CarModel::class);
    // }

    public function notifications(): HasMany
    {
        return $this->hasMany(AlertNotification::class);
    }
}
