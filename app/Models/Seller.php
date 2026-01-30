<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Seller extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'seller_slug',
        'mobile_number',
        'email',
        'password',
        'seller_type',
        'business_name',
        'business_location',
        'terms_accepted',
        'terms_accepted_at',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'mobile_verified_at' => 'datetime',
        'terms_accepted' => 'boolean',
        'terms_accepted_at' => 'datetime',
        'is_active' => 'boolean',
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
