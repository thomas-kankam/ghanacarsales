<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Car extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'car_slug',
        'dealer_slug',
        'brand',
        'model',
        'region',
        'location',
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
        'images',
        'status',
        'description',
        'plan_slug',
        'plan_price',
        'plan_details',
        'start_date',
        'expiry_date',
    ];

    protected $casts = [
        'swap_deals'   => 'boolean',
        'aircon'       => 'boolean',
        'registered'   => 'boolean',
        'price'        => 'decimal:2',
        'plan_price'   => 'decimal:2',
        'plan_details' => 'array',
        'images'       => 'array',
        'start_date'   => 'datetime',
        'expiry_date'  => 'datetime',
    ];

    public function getRouteKeyName()
    {
        return "car_slug";
    }

    public function dealer()
    {
        return $this->belongsTo(Dealer::class, 'dealer_slug', 'dealer_slug');
    }

    public function approvals()
    {
        return $this->hasMany(Approval::class, 'car_slug', 'car_slug');
    }

    public function latestApproval()
    {
        return $this->hasOne(Approval::class, 'car_slug', 'car_slug')->latestOfMany();
    }

    public function views()
    {
        return $this->hasMany(View::class, 'car_slug', 'car_slug');
    }

    public function paymentItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PaymentItem::class, 'car_slug', 'car_slug');
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            Payment::class,
            'payment_items',
            'car_slug',
            'payment_slug',
            'car_slug',
            'payment_slug'
        )->withPivot('price');
    }
}
