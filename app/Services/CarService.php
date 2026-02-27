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
            $data['dealer_id'] = $dealer->id;
            $data['car_slug']  = Str::uuid();
            $data['status']    = 'pending';

            // Gallery images
            if (isset($data['images']) && is_array($data['images'])) {
                $data['images'] = array_values(array_filter(array_map(function ($img) {
                    if (! is_string($img)) {return null;}
                    return str_starts_with($img, 'data:') ? static::base64ImageDecode($img) : $img;
                }, $data['images'])));
            }

            $car = Car::create($data);

            // Upload and save images
            // $this->uploadImages($car, $images);

            return $car->load(['brand', 'model', 'images']);
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
