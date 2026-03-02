<?php

namespace App\Policies;

use App\Models\Car;
use App\Models\Dealer;

class CarPolicy
{
    public function viewAny(Dealer $dealer): bool
    {
        return true;
    }

    public function view(Dealer $dealer, Car $car): bool
    {
        return $dealer->id === $car->dealer_id;
    }

    public function create(Dealer $dealer): bool
    {
        return $dealer->phone_verified_at !== null && $dealer->terms_accepted;
    }

    public function update(Dealer $dealer, Car $car): bool
    {
        return $dealer->id === $car->dealer_id && $car->status !== 'sold';
    }

    public function delete(Dealer $dealer, Car $car): bool
    {
        return $dealer->id === $car->dealer_id && $car->status !== 'sold';
    }
}
