<?php

namespace App\Policies;

use App\Models\Car;
use App\Models\Seller;

class CarPolicy
{
    public function viewAny(Seller $seller): bool
    {
        return true;
    }

    public function view(Seller $seller, Car $car): bool
    {
        return $seller->id === $car->seller_id;
    }

    public function create(Seller $seller): bool
    {
        return $seller->mobile_verified_at !== null && $seller->terms_accepted;
    }

    public function update(Seller $seller, Car $car): bool
    {
        return $seller->id === $car->seller_id && $car->status !== 'sold';
    }

    public function delete(Seller $seller, Car $car): bool
    {
        return $seller->id === $car->seller_id && $car->status !== 'sold';
    }
}
