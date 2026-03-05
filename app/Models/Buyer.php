<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Buyer extends Authenticatable
{
    protected $fillable = [
        'buyer_slug',
        'phone_number',
        'email',
        'full_name',
        'verified_at',
        'verified',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'verified'    => 'boolean',
    ];

    public function getRouteKeyName()
    {
        return "buyer_slug";
    }

    public function views(): HasMany
    {
        return $this->hasMany(View::class);
    }
}
