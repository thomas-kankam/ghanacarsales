<?php

namespace App\Observers;

use App\Models\Car;
use App\Services\AlertService;

class CarObserver
{
    public function created(Car $car): void
    {
        // When a new car is created and activated, check for matching alerts
        if ($car->status === 'active') {
            $alertService = app(AlertService::class);
            $alertService->checkAlertsForCar($car);
        }
    }

    public function updated(Car $car): void
    {
        // When a car status changes to active, check for matching alerts
        if ($car->isDirty('status') && $car->status === 'active') {
            $alertService = app(AlertService::class);
            $alertService->checkAlertsForCar($car);
        }
    }
}
