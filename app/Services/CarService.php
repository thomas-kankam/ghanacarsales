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

            $data['dealer_id']   = $dealer->id;
            $data['car_slug']    = Str::uuid();
            $data['is_published']= $data['is_published'] ?? false;

            // ------------------------------------------------
            // Status handling
            // ------------------------------------------------
            if (! empty($data['dealer_code'])) {
                // Requires sponsor dealer + admin approval before publish
                $data['status']          = 'pending_sponsor_approval';
                $data['dealer_approval'] = false;
                $data['admin_approval']  = false;
            } elseif (($data['status'] ?? null) === 'draft') {
                $data['status']          = 'draft';
                $data['dealer_approval'] = false;
                $data['admin_approval']  = false;
            } else {
                // Default to draft – explicit publish happens via dedicated endpoint
                $data['status']          = 'draft';
                $data['dealer_approval'] = false;
                $data['admin_approval']  = false;
            }

            // ------------------------------------------------
            // Process Images
            // ------------------------------------------------
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

            // ------------------------------------------------
            // Create Car
            // ------------------------------------------------
            $car = Car::create($data);

            return $car->load(['brand', 'model', 'images']);
        });
    }

    public function updateCar(Car $car, array $data): Car
    {
        return DB::transaction(function () use ($car, $data) {
            // dealer_id & slug are immutable here
            unset($data['dealer_id'], $data['car_slug']);

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

            return $car->fresh()->load(['brand', 'model', 'images']);
        });
    }
    // public function uploadImages(Car $car, array $images): void
    // {
    //     $sortOrder = 0;
    //     foreach ($images as $index => $image) {
    //         if ($image instanceof UploadedFile) {
    //             $path = $image->store('cars/' . $car->id, 'public');

    //             CarImage::create([
    //                 'car_id'     => $car->id,
    //                 'image_path' => $path,
    //                 'sort_order' => $sortOrder++,
    //                 'is_primary' => $index === 0, // First image is primary
    //             ]);
    //         }
    //     }
    // }

    public function activateCar(Car $car, int $durationDays = 30): void
    {
        $car->update([
            'status'          => 'active',
            'expires_at'      => now()->addDays($durationDays),
            'payment_made_at' => now(),
        ]);
    }

    public function deleteExpiredCars(): int
    {
        $expiredCars = Car::where('status', 'expired')
            ->where('expires_at', '<', now()->subDays(5))
            ->get();

        $count = 0;
        foreach ($expiredCars as $car) {
            // Delete images
            foreach ($car->images as $image) {
                Storage::disk('public')->delete($image->image_path);
            }
            $car->delete();
            $count++;
        }

        return $count;
    }

    public function markExpiredCars(): int
    {
        return Car::where('status', 'active')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }
}
