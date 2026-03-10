<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentCar extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_slug',
        'car_slug',
    ];
}
