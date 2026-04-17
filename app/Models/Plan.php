<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_name',
        'plan_slug',
        'price',
        'is_recommend',
        'duration_days',
        'features',
    ];

    protected $casts = [
        'price'         => 'decimal:2',
        'is_recommend'  => 'boolean',
        'features'      => 'array',
    ];
}
