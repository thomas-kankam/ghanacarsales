<?php

namespace App\Services;

use App\Models\Car;
use App\Models\CarImage;
use App\Models\Seller;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CarService
{
    public function createCar(Seller $seller, array $data, array $images): Car
    {
        return DB::transaction(function () use ($seller, $data, $images) {
            $data['seller_id'] = $seller->id;
            $data['car_slug'] = Str::uuid();
            $data['status'] = 'pending';

            $car = Car::create($data);

            // Upload and save images
            $this->uploadImages($car, $images);

            return $car->load(['brand', 'model', 'images']);
        });
    }

    public function uploadImages(Car $car, array $images): void
    {
        $sortOrder = 0;
        foreach ($images as $index => $image) {
            if ($image instanceof UploadedFile) {
                $path = $image->store('cars/' . $car->id, 'public');
                
                CarImage::create([
                    'car_id' => $car->id,
                    'image_path' => $path,
                    'sort_order' => $sortOrder++,
                    'is_primary' => $index === 0, // First image is primary
                ]);
            }
        }
    }

    public function activateCar(Car $car, int $durationDays = 30): void
    {
        $car->update([
            'status' => 'active',
            'expires_at' => now()->addDays($durationDays),
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
