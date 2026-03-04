<?php
namespace App\Transformers;

use App\Models\Car;

class CarTransformer
{
    public static function summary(Car $car): array
    {
        $brandName = $car->brand;
        $modelName = $car->model;

        return [
            'id'       => $car->id,
            'car_slug' => $car->car_slug,
            'title'    => trim("{$brandName} {$modelName} {$car->year_of_manufacture}"),
            'brand' => $car->brand,
            'model' => $car->model,
            'status' => $car->status,
            'price' => $car->price !== null ? (float) $car->price : null,
            'year' => $car->year_of_manufacture,
            'mileage' => $car->mileage,
            'mileage_unit' => $car->mileage_unit,
            'fuel_type' => $car->fuel_type,
            'transmission' => $car->transmission,
            'colour' => $car->colour,
            'location' => $car->location,
            'swap_deals' => (bool) $car->swap_deals,
            'aircon' => (bool) $car->aircon,
            'registered' => (bool) $car->registered,
            'registration_year' => $car->registration_year,
            'is_published' => (bool) $car->is_published,
            'dealer' => [
                'id'            => $car->dealer->id ?? null,
                'business_name' => $car->dealer->business_name ?? null,
                'full_name'     => $car->dealer->full_name ?? null,
                'region'        => $car->dealer->region ?? null,
                'city'          => $car->dealer->city ?? null,
                'dealer_code'   => $car->dealer->dealer_code ?? null,
            ],
            'images' => $car->images ?? [],
            'created_at' => optional($car->created_at)->toIso8601String(),
            'expires_at' => optional($car->expires_at)->toIso8601String(),
        ];
    }
}
