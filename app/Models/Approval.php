<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Approval extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_slug',
        'dealer_slug',
        'dealer_code',
        'dealer_approval',
        'admin_approval',
        'admin_slug',
        'admin_approval_at',
        'dealer_approval_at',
        'payment_slug',
        'dealer_name',
    ];

    protected $casts = [
        'dealer_approval'    => 'boolean',
        'admin_approval'     => 'boolean',
        'dealer_approval_at' => 'datetime',
        'admin_approval_at'  => 'datetime',
    ];

    public function car()
    {
        return $this->belongsTo(Car::class, 'car_slug', 'car_slug');
    }

    public function dealer()
    {
        return $this->belongsTo(Dealer::class, 'dealer_slug', 'dealer_slug');
    }
}
