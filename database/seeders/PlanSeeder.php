<?php
namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'plan_name'     => 'Friend Code',
                'plan_slug'     => 'friend_code',
                'price'         => 0.00,
                'duration_days' => 15,
                'features'      => [
                    'listing_duration' => 15,
                ],
            ],
            [
                'plan_name'     => '1 Month',
                'plan_slug'     => '1_month',
                'price'         => 30.00,
                'duration_days' => 30,
                'features'      => [
                    'listing_duration' => 30,
                ],
            ],
            [
                'plan_name'     => '3 Months',
                'plan_slug'     => '3_months',
                'price'         => 75.00,
                'duration_days' => 90,
                'features'      => [
                    'listing_duration' => 90,
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['plan_slug' => $plan['plan_slug']],
                $plan
            );
        }
    }
}
