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

    public function getRouteKeyName()
    {
        return "dealer_slug";
    }

    public function hasVerifiedEmail()
    {
        return $this->email_verified_at != null;
    }

    public function hasVerifiedPhone()
    {
        return $this->phone_verified_at != null;
    }

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class, 'dealer_id', 'dealer_slug');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'dealer_id', 'id');
    }

    protected function subscription()
    {
        return $this->hasOne(Subscription::class, 'dealer_slug', 'dealer_slug');
    }
}
