<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'car_slug' => $this->car_slug,
            'brand' => [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
            ],
            'model' => [
                'id' => $this->model->id,
                'name' => $this->model->name,
            ],
            'year_of_manufacture' => $this->year_of_manufacture,
            'mileage' => $this->mileage,
            'mileage_unit' => $this->mileage_unit,
            'price' => $this->price,
            'swap_deals' => $this->swap_deals,
            'aircon' => $this->aircon,
            'registered' => $this->registered,
            'registration_year' => $this->registration_year,
            'fuel_type' => $this->fuel_type,
            'transmission' => $this->transmission,
            'colour' => $this->colour,
            'location' => $this->location,
            'status' => $this->status,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'images' => CarImageResource::collection($this->whenLoaded('images')),
            'primary_image' => $this->when($this->relationLoaded('images'), function () {
                $primary = $this->images->where('is_primary', true)->first();
                return $primary ? new CarImageResource($primary) : null;
            }),
            'seller' => $this->when($request->user() && $request->user()->getTable() === 'sellers', function () {
                return [
                    'id' => $this->seller->id,
                    'seller_type' => $this->seller->seller_type,
                    'business_name' => $this->seller->business_name,
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
