<?php

namespace App\Jobs;

use App\Services\CarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteExpiredCars implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(CarService $carService): void
    {
        $count = $carService->deleteExpiredCars();
        \Log::info("Deleted {$count} expired cars");
    }
}
