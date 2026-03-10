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
        'reference',
        'plan_price',
        'amount',
        'payment_method',
    ];

    protected $casts = [
        'plan_price' => 'decimal:2',
        'amount'     => 'decimal:2',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class, 'dealer_slug', 'dealer_slug');
    }

    public function paymentItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PaymentItem::class, 'payment_slug', 'payment_slug');
    }

    public function cars(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            Car::class,
            'payment_items',
            'payment_slug',
            'car_slug',
            'payment_slug',
            'car_slug'
        )->withPivot('price');
    }
}
