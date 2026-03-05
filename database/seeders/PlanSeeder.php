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
                'name'          => 'Free Trial',
                'plan_slug'     => 'free_trial',
                'price'         => 0.00,
                'duration_days' => 15,
                'features'      => [
                    'car_limits'        => [
                        'total'          => 3,
                        'featured'       => 0,
                        'images_per_car' => 5,
                    ],
                    'listing_duration'  => 7,
                    'visibility'        => 'standard',
                    'support'           => 'email_only',
                    'analytics'         => [
                        'views'     => false,
                        'inquiries' => true,
                        'reports'   => false,
                    ],
                    'badges'            => [],
                    'verification'      => false,
                    'promotional_tools' => false,
                    'api_access'        => false,
                    'highlights'        => [
                        'List up to 3 vehicles',
                        '7 days active listing',
                        'Basic inquiry management',
                        'Email support',
                    ],
                ],
            ],
            [
                'name'          => '1 Month',
                'plan_slug'     => '1_month',
                'price'         => 30.00,
                'duration_days' => 30,
                'features'      => [
                    'car_limits'        => [
                        'total'          => 15,
                        'featured'       => 2,
                        'images_per_car' => 10,
                    ],
                    'listing_duration'  => 30,
                    'visibility'        => 'enhanced',
                    'support'           => 'priority_email',
                    'analytics'         => [
                        'views'     => true,
                        'inquiries' => true,
                        'reports'   => true,
                    ],
                    'badges'            => ['basic_dealer'],
                    'verification'      => false,
                    'promotional_tools' => [
                        'social_share' => true,
                        'highlight'    => true,
                    ],
                    'api_access'        => false,
                    'highlights'        => [
                        'List up to 15 vehicles',
                        '2 featured listings',
                        '30 days active listing',
                        'View analytics & reports',
                        'Priority email support',
                    ],
                ],
            ],
            [
                'name'          => '3 Months',
                'plan_slug'     => '3_months',
                'price'         => 75.00,
                'duration_days' => 90,
                'features'      => [
                    'car_limits'        => [
                        'total'          => 50,
                        'featured'       => 10,
                        'images_per_car' => 20,
                    ],
                    'listing_duration'  => 90,
                    'visibility'        => 'premium',
                    'support'           => '24_7_priority',
                    'analytics'         => [
                        'views'               => true,
                        'inquiries'           => true,
                        'reports'             => true,
                        'competitor_insights' => true,
                    ],
                    'badges'            => ['premium_dealer', 'verified'],
                    'verification'      => true,
                    'promotional_tools' => [
                        'social_share'         => true,
                        'highlight'            => true,
                        'featured_homepage'    => true,
                        'newsletter_spotlight' => true,
                    ],
                    'api_access'        => true,
                    'highlights'        => [
                        'List up to 50 vehicles',
                        '10 featured listings',
                        '90 days active listing',
                        'Advanced analytics with insights',
                        '24/7 priority support',
                        'Verified dealer badge',
                        'Homepage featured rotation',
                        'API access for bulk management',
                    ],
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
