<?php

namespace App\Services;

use App\Models\Buyer;
use App\Models\BuyerAlert;
use App\Models\Car;
use Illuminate\Support\Facades\DB;

class AlertService
{
    public function createAlert(?Buyer $buyer, array $data): BuyerAlert
    {
        return BuyerAlert::create([
            'buyer_id' => $buyer?->id,
            'mobile_number' => $data['mobile_number'],
            'email' => $data['email'] ?? null,
            'brand_id' => $data['brand_id'] ?? null,
            'model_id' => $data['model_id'] ?? null,
            'min_year' => $data['min_year'] ?? null,
            'max_year' => $data['max_year'] ?? null,
            'min_mileage' => $data['min_mileage'] ?? null,
            'max_mileage' => $data['max_mileage'] ?? null,
            'mileage_unit' => $data['mileage_unit'] ?? null,
            'min_price' => $data['min_price'] ?? null,
            'max_price' => $data['max_price'] ?? null,
            'swap_deals' => $data['swap_deals'] ?? null,
            'aircon' => $data['aircon'] ?? null,
            'registered' => $data['registered'] ?? null,
            'fuel_type' => $data['fuel_type'] ?? null,
            'transmission' => $data['transmission'] ?? null,
            'colour' => $data['colour'] ?? null,
            'location' => $data['location'] ?? null,
            'is_active' => true,
        ]);
    }

    public function checkAlertsForCar(Car $car): void
    {
        $alerts = BuyerAlert::where('is_active', true)->get();

        foreach ($alerts as $alert) {
            if ($this->carMatchesAlert($car, $alert)) {
                \App\Jobs\SendAlertNotification::dispatch($alert, $car);
            }
        }
    }

    protected function carMatchesAlert(Car $car, BuyerAlert $alert): bool
    {
        if ($alert->brand_id && $car->brand_id !== $alert->brand_id) {
            return false;
        }

        if ($alert->model_id && $car->model_id !== $alert->model_id) {
            return false;
        }

        if ($alert->min_year && $car->year_of_manufacture < $alert->min_year) {
            return false;
        }

        if ($alert->max_year && $car->year_of_manufacture > $alert->max_year) {
            return false;
        }

        if ($alert->min_price && $car->price < $alert->min_price) {
            return false;
        }

        if ($alert->max_price && $car->price > $alert->max_price) {
            return false;
        }

        if ($alert->fuel_type && $car->fuel_type !== $alert->fuel_type) {
            return false;
        }

        if ($alert->transmission && $car->transmission !== $alert->transmission) {
            return false;
        }

        if ($alert->location && $car->location !== $alert->location) {
            return false;
        }

        if ($alert->swap_deals !== null && $car->swap_deals !== $alert->swap_deals) {
            return false;
        }

        if ($alert->aircon !== null && $car->aircon !== $alert->aircon) {
            return false;
        }

        if ($alert->registered !== null && $car->registered !== $alert->registered) {
            return false;
        }

        if ($alert->colour && $car->colour !== $alert->colour) {
            return false;
        }

        return true;
    }
}
