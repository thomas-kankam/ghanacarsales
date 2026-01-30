<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'car_slug',
        'seller_id',
        'brand_id',
        'model_id',
        'year_of_manufacture',
        'mileage',
        'mileage_unit',
        'price',
        'swap_deals',
        'aircon',
        'registered',
        'registration_year',
        'fuel_type',
        'transmission',
        'colour',
        'location',
        'status',
        'expires_at',
        'payment_made_at',
    ];

    protected $casts = [
        'swap_deals' => 'boolean',
        'aircon' => 'boolean',
        'registered' => 'boolean',
        'price' => 'decimal:2',
        'expires_at' => 'datetime',
        'payment_made_at' => 'datetime',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(CarModel::class, 'model_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(CarImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasMany
    {
        return $this->hasMany(CarImage::class)->where('is_primary', true);
    }

    public function paymentCars(): HasMany
    {
        return $this->hasMany(PaymentCar::class);
    }
}
