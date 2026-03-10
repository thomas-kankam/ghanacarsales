<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Approval extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_slug',
        'car_slug',
        'dealer_slug',
        'type',
        'status',
        'dealer_code',
        'dealer_name',
        'payment_slug',
        'admin_approval',
        'admin_slug',
        'admin_approval_at',
        'reason',
    ];

    protected $casts = [
        'admin_approval'    => 'boolean',
        'admin_approval_at' => 'datetime',
    ];

    public function car()
    {
        return $this->belongsTo(Car::class, 'car_slug', 'car_slug');
    }

    public function dealer()
    {
        return $this->belongsTo(Dealer::class, 'dealer_slug', 'dealer_slug');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_slug', 'payment_slug');
    }
}
