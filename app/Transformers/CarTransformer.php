<?php
namespace App\Transformers;

use App\Models\Car;

class CarTransformer
{
    public static function summary(Car $car): array
    {
        $brandName = $car->brand;
        $modelName = $car->model;

        $payload = [
            'id'       => $car->id,
            'car_slug' => $car->car_slug,
            'title'    => trim("{$brandName} {$modelName} {$car->year_of_manufacture}"),
            'brand' => $car->brand,
            'model' => $car->model,
            'status' => $car->status,
            'price' => $car->price !== null ? (float) $car->price : null,
            'year_of_manufacture' => $car->year_of_manufacture,
            'mileage' => $car->mileage,
            'mileage_unit' => $car->mileage_unit,
            'fuel_type' => $car->fuel_type,
            'transmission' => $car->transmission,
            'colour' => $car->colour,
            'swap_deals' => (bool) $car->swap_deals,
            'aircon' => (bool) $car->aircon,
            'registered' => (bool) $car->registered,
            'registration_year' => $car->registration_year,
            'plan_slug' => $car->plan_slug,
            'plan_price' => $car->plan_price !== null ? (float) $car->plan_price : null,
            'plan_details' => $car->plan_details ?? null,
            'start_date' => $car->start_date?->toIso8601String(),
            'expiry_date' => $car->expiry_date?->toIso8601String(),
            'description' => $car->description,
            'dealer' => [
                'id'            => $car->dealer->id ?? null,
                'business_name' => $car->dealer->business_name ?? null,
                'full_name'     => $car->dealer->full_name ?? null,
                'region'        => $car->dealer->region ?? null,
                'city'          => $car->dealer->city ?? null,
                'dealer_code'   => $car->dealer->dealer_code ?? null,
                'dealer_slug'   => $car->dealer->dealer_slug ?? null,
            ],
            'images' => $car->images ?? [],
            'created_at' => optional($car->created_at)->toIso8601String(),
        ];

        if ($car->relationLoaded('paymentItems')) {
            $payload['payments'] = $car->paymentItems->map(function ($item) {
                $p = $item->payment;
                return $p ? [
                    'payment_slug'   => $p->payment_slug,
                    'amount'         => (float) $p->amount,
                    'plan_slug'      => $p->plan_slug,
                    'plan_name'      => $p->plan_name,
                    'status'         => $p->status,
                    'reference_id'   => $p->reference_id,
                    'reference'      => $p->reference,
                    'network'        => $p->network,
                    'payment_method' => $p->payment_method,
                ] : null;
            })->filter()->values()->all();
        }

        return $payload;
    }
}
