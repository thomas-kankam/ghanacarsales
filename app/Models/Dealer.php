<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Dealer extends Actor
{
    protected $fillable = [
        'dealer_slug',
        'phone_number',
        'email',
        'full_name',
        'verified',
        'verified_at',
        'business_type',
        'city',
        'region',
        'landmark',
        'business_name',
        'dealer_code',
        'status',
        'terms_accepted',
        'terms_accepted_at',
        'is_onboarded',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'verified_at'       => 'datetime',
        'terms_accepted'    => 'boolean',
        'terms_accepted_at' => 'datetime',
        'is_onboarded'      => 'boolean',
    ];

    public function getRouteKeyName()
    {
        return "dealer_slug";
    }

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class, 'dealer_slug', 'dealer_slug');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'dealer_slug', 'dealer_slug');
    }

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subscription::class, 'dealer_slug', 'dealer_slug');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class, 'dealer_slug', 'dealer_slug');
    }
}
