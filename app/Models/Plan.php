<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'plan_slug',
        'price',
        'duration_days',
        'features',
    ];

    protected $casts = [
        'price'    => 'decimal',
        'features' => 'array',
    ];
}
