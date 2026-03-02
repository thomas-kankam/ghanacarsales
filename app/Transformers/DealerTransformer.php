<?php
namespace App\Transformers;

use App\Models\Dealer;

class DealerTransformer
{
    public static function summary(Dealer $dealer): array
    {
        return [
            'id'            => $dealer->id,
            'slug'          => $dealer->dealer_slug,
            'full_name'     => $dealer->full_name,
            'business_name' => $dealer->business_name,
            'phone_number'  => $dealer->phone_number,
            'email'         => $dealer->email,
            'region'        => $dealer->region,
            'city'          => $dealer->city,
            'landmark'      => $dealer->landmark,
            'dealer_code'   => $dealer->dealer_code,
            'is_active'     => (bool) $dealer->is_active,
            'is_onboarded'  => (bool) $dealer->is_onboarded,
            'created_at'    => optional($dealer->created_at)->toIso8601String(),
        ];
    }
}

