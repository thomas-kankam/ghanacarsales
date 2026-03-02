<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'actor_id',
        'guard',
        'type',
        'channel',
        'expires_at',
        'is_verified',
        'verified_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'expires_at'  => 'datetime',
        'verified_at' => 'datetime',
    ];
}
