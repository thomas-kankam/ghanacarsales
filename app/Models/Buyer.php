<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Buyer extends Authenticatable
{
    protected $fillable = [
        'buyer_slug',
        'mobile_number',
        'email',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at'  => 'datetime',
        'mobile_verified_at' => 'datetime',
        'password'           => 'hashed',
        'is_active'          => 'boolean',
    ];

    public function alerts(): HasMany
    {
        return $this->hasMany(BuyerAlert::class);
    }
}
