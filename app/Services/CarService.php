<?php
namespace App\Services;

use App\Models\Car;
use App\Models\Dealer;
use App\Traits\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CarService
{
    use Helpers;

    public function createCar(Dealer $dealer, array $data): Car
    {
        return DB::transaction(function () use ($dealer, $data) {

            $data['dealer_slug'] = $dealer->dealer_slug;
            $data['car_slug']    = Str::uuid();

            if (isset($data['images']) && is_array($data['images'])) {
                $data['images'] = array_values(array_filter(array_map(function ($img) {
                    if (! is_string($img)) {
                        return null;
                    }
                    return str_starts_with($img, 'data:') ? static::base64ImageDecode($img) : $img;
                }, $data['images'])));
            }

            $car = Car::create($data);

            return $car;
        });
    }

    public function updateCar(Car $car, array $data): Car
    {
        return DB::transaction(function () use ($car, $data) {
            // dealer_id & slug are immutable here
            unset($data['dealer_slug'], $data['car_slug']);

            if (isset($data['images']) && is_array($data['images'])) {
                $data['images'] = array_values(array_filter(array_map(function ($img) {
                    if (! is_string($img)) {
                        return null;
                    }
                    return str_starts_with($img, 'data:')
                        ? static::base64ImageDecode($img)
                        : $img;
                }, $data['images'])));
            }

            $car->update($data);

            return $car;
        });
    }

    /**
     * Activate/publish a car with start and expiry dates.
     */
    public function activateCar(Car $car, int $durationDays): Car
    {
        $startDate = now();
        $expiryDate = now()->addDays($durationDays);
        $car->update([
            'status'      => 'published',
            'start_date'  => $startDate,
            'expiry_date' => $expiryDate,
        ]);
        return $car;
    }

    /**
     * Delete cars that have been expired for more than 5 days.
     */
    public function deleteExpiredCars(): int
    {
        $expiredCars = Car::where('status', 'expired')
            ->where('expiry_date', '<', now()->subDays(5))
            ->get();

        $count = 0;
        foreach ($expiredCars as $car) {
            if (is_array($car->images)) {
                foreach ($car->images as $img) {
                    $path = is_string($img) ? $img : ($img['path'] ?? $img['image_path'] ?? null);
                    if ($path) {
                        Storage::disk('public')->delete($path);
                    }
                }
            }
            $car->forceDelete();
            $count++;
        }

        return $count;
    }

    /**
     * Mark published cars past expiry_date as expired.
     */
    public function markExpiredCars(): int
    {
        return Car::where('status', 'published')
            ->where('expiry_date', '<', now())
            ->update(['status' => 'expired']);
    }
}
