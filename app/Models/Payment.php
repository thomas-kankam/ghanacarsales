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
        'plan_price',
        'payment_method',
        'car_slugs',
        'plan_details',
    ];

    protected $casts = [
        'plan_price'   => 'decimal:2',
        'car_slugs'    => 'array',
        'plan_details' => 'array',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class, 'dealer_slug', 'dealer_slug');
    }

    public function cars()
    {
        return $this->belongsToMany(Car::class, 'payments', 'car_slugs', 'car_slug')
            ->whereRaw('JSON_CONTAINS(car_slugs, ?)', [$this->car_slug]);
    }
}
