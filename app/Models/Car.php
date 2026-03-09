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
        'images',
        'status',
        'description',
        'start_date',
        'expiry_date',
        'plan_name',
        'plan_slug',
        'payment_status',
        'plan_details',
    ];

    protected $casts = [
        'swap_deals'   => 'boolean',
        'aircon'       => 'boolean',
        'registered'   => 'boolean',
        'price'        => 'decimal:2',
        'images'       => 'array',
        'plan_details' => 'array',
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

    public function views()
    {
        return $this->hasMany(View::class, 'car_slug', 'car_slug');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'car_slug', 'car_slug');
    }

    // In Car.php model
    protected $appends = ['payment_info', 'latest_payment'];

    public function getPaymentInfoAttribute()
    {
        return Payment::whereJsonContains('car_slugs', $this->car_slug)->get();
    }

    public function getLatestPaymentAttribute()
    {
        return Payment::whereJsonContains('car_slugs', $this->car_slug)
            ->latest()
            ->first();
    }
}
