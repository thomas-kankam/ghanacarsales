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
        'images',
        'status',
        'description',
        // 'expires_at',
        // 'payment_made_at',
        // 'admin_approval',
        // 'dealer_approval',
        // 'dealer_code',
        // 'is_published',
    ];

    protected $casts = [
        'swap_deals' => 'boolean',
        'aircon'     => 'boolean',
        'registered' => 'boolean',
        // 'admin_approval'  => 'boolean',
        // 'dealer_approval' => 'boolean',
        // 'is_published'    => 'boolean',
        'price'      => 'decimal:2',
        // 'expires_at'      => 'datetime',
        // 'payment_made_at' => 'datetime',
        'images'     => 'array',
    ];

    public function getRouteKeyName()
    {
        return "car_slug";
    }

    public function dealer()
    {
        return $this->belongsTo(Dealer::class,'dealer_slug','dealer_slug');
    }
}
