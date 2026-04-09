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
            'region' => $car->region,
            'location' => $car->location,
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
                'id'            => $car->dealer?->id ?? null,
                'business_name' => $car->dealer?->business_name ?? null,
                'full_name'     => $car->dealer?->full_name ?? null,
                'region'        => $car->dealer?->region ?? null,
                'city'          => $car->dealer?->city ?? null,
                'dealer_code'   => $car->dealer?->dealer_code ?? null,
                'dealer_slug'   => $car->dealer?->dealer_slug ?? null,
                'phone_number'  => $car->dealer?->phone_number ?? null,
                'email'         => $car->dealer?->email ?? null,
                'business_type' => $car->dealer?->business_type ?? null,
                'landmark'      => $car->dealer?->landmark ?? null,
                'verified'      => (bool) ($car->dealer?->verified ?? false),
                'terms_accepted' => (bool) ($car->dealer?->terms_accepted ?? false),
                'is_onboarded'  => (bool) ($car->dealer?->is_onboarded ?? false),
                'status'        => $car->dealer?->status ?? null
            ],
            'images' => $car->images ?? [],
            'created_at' => optional($car->created_at)->toIso8601String(),
            'approval' => null,
        ];

        $approval = null;
        if ($car->relationLoaded('latestApproval')) {
            $approval = $car->latestApproval;
        } elseif ($car->relationLoaded('approvals')) {
            $approval = $car->approvals->sortByDesc('created_at')->first();
        }

        if ($approval) {
            $payload['approval'] = [
                'id' => $approval->id ?? null,
                'approval_slug' => $approval->approval_slug ?? null,
                'car_slug' => $approval->car_slug ?? null,
                'dealer_slug' => $approval->dealer_slug ?? null,
                'dealer_name' => $approval->dealer_name ?? null,
                'dealer_code' => $approval->dealer_code ?? null,
                'payment_slug' => $approval->payment_slug ?? null,
                'status' => $approval->status ?? null,
                'type' => $approval->type ?? null,
                'reason' => $approval->reason ?? null,
                'rejection_reason' => $approval->reason ?? null,
                'created_at' => optional($approval->created_at)->toIso8601String(),
                'updated_at' => optional($approval->updated_at)->toIso8601String(),
                'admin_approval' => (bool) ($approval->admin_approval ?? false),
                'admin_approval_at' => optional($approval->admin_approval_at)->toIso8601String(),
                'admin_slug' => $approval->admin_slug ?? null,
            ];
        }

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
