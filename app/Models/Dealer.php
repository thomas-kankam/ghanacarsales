<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Dealer extends Actor
{
    protected $fillable = [
        'dealer_slug',
        'phone_number',
        'email',
        'email_verified_at',
        'phone_verified_at',
        'business_type',
        'full_name',
        'business_name',
        'region',
        'city',
        'landmark',
        'terms_accepted',
        'terms_accepted_at',
        'is_active',
        'is_onboarded',
        'dealer_code',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at'  => 'datetime',
        'mobile_verified_at' => 'datetime',
        'terms_accepted'     => 'boolean',
        'terms_accepted_at'  => 'datetime',
        'is_active'          => 'boolean',
        'is_onboarded'       => 'boolean',
    ];

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
