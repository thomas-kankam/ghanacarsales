<?php
namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Actor extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, SoftDeletes, HasApiTokens, Notifiable;

    // public function hasVerifiedEmail()
    // {
    //     return $this->email_verified_at != NULL;
    // }

    // public function hasVerifiedPhoneNumber()
    // {
    //     return $this->phone_number_verified_at != NULL;
    // }

}
