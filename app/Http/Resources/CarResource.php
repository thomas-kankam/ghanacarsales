<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $brandName = $this->brand;
        $modelName = $this->model;

        return [
            'id'                   => $this->id,
            'car_slug'             => $this->car_slug,
            'brand'                => $brandName,
            'model'                => $modelName,
            'year_of_manufacture'  => $this->year_of_manufacture,
            'mileage'              => $this->mileage,
            'mileage_unit'         => $this->mileage_unit,
            'price'                => $this->price,
            'swap_deals'           => $this->swap_deals,
            'aircon'               => $this->aircon,
            'registered'           => $this->registered,
            'registration_year'    => $this->registration_year,
            'fuel_type'            => $this->fuel_type,
            'transmission'         => $this->transmission,
            'colour'               => $this->colour,
            'location'             => $this->location,
            'status'               => $this->status,
            'expires_at'           => $this->expires_at?->toIso8601String(),
            'images'               => $this->images,
            'primary_image'        => $this->images[0] ?? null,
            'dealer'               => $this->when($this->relationLoaded('dealer'), function () {
                return [
                    'id'            => $this->dealer->id,
                    'business_name' => $this->dealer->business_name,
                    'region'        => $this->dealer->region,
                    'city'          => $this->dealer->city,
                ];
            }),
            'created_at'           => $this->created_at?->toIso8601String(),
        ];
    }
}
