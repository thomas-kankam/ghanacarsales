<?php
namespace App\Transformers;

use App\Models\Plan;

class PlanTransformer
{
    /**
     * Transform a single plan for API responses
     */
    public static function summary(Plan $plan): array
    {
        return [
            'id'            => $plan->id,
            'plan_name'     => $plan->plan_name,
            'plan_slug'     => $plan->plan_slug,
            'price'         => $plan->price !== null ? (float) $plan->price : null,
            'duration_days' => $plan->duration_days,
            'features'      => self::transformFeatures($plan->features),
            'created_at'    => optional($plan->created_at)->toIso8601String(),
            'updated_at'    => optional($plan->updated_at)->toIso8601String(),
        ];
    }

    /**
     * Transform multiple plans for API responses
     */
    public static function collection($plans): array
    {
        return [
            'data' => collect($plans)->map(function ($plan) {
                return self::summary($plan);
            })->values()->toArray()
        ];
    }

    /**
     * Detailed plan transformation with additional information
     */
    public static function detailed(Plan $plan): array
    {
        return array_merge(self::summary($plan), [
            'features_detailed' => [
                'car_limits'        => $plan->features['car_limits'] ?? null,
                'listing_duration'  => $plan->features['listing_duration'] ?? null,
                'visibility'        => $plan->features['visibility'] ?? null,
                'support'           => $plan->features['support'] ?? null,
                'analytics'         => $plan->features['analytics'] ?? null,
                'badges'            => $plan->features['badges'] ?? [],
                'verification'      => (bool) ($plan->features['verification'] ?? false),
                'promotional_tools' => $plan->features['promotional_tools'] ?? null,
                'api_access'        => (bool) ($plan->features['api_access'] ?? false),
                'highlights'        => $plan->features['highlights'] ?? [],
            ],
            'is_free' => $plan->price == 0,
            'formatted_price' => self::formatPrice($plan->price),
            'duration_text' => self::formatDuration($plan->duration_days),
        ]);
    }

    /**
     * Transform features array safely
     */
    private static function transformFeatures($features): array
    {
        // If features is null or not an array, return empty array
        if (empty($features) || !is_array($features)) {
            return [];
        }

        // Return a simplified/transformed version of features
        return [
            'car_limit_total'   => $features['car_limits']['total'] ?? 0,
            'car_limit_featured' => $features['car_limits']['featured'] ?? 0,
            'images_per_car'    => $features['car_limits']['images_per_car'] ?? 0,
            'listing_duration'  => $features['listing_duration'] ?? 0,
            'visibility'        => $features['visibility'] ?? 'standard',
            'support'           => $features['support'] ?? 'email_only',
            'has_analytics'     => isset($features['analytics']) &&
                                   ($features['analytics']['views'] ?? false) ||
                                   ($features['analytics']['inquiries'] ?? false) ||
                                   ($features['analytics']['reports'] ?? false),
            'badges'            => $features['badges'] ?? [],
            'is_verified'       => (bool) ($features['verification'] ?? false),
            'has_api_access'    => (bool) ($features['api_access'] ?? false),
            'highlights_count'  => count($features['highlights'] ?? []),
            'highlights'        => $features['highlights'] ?? [],
        ];
    }

    /**
     * Format price for display
     */
    private static function formatPrice($price): string
    {
        if ($price == 0) {
            return 'Free';
        }

        return '$' . number_format($price, 2);
    }

    /**
     * Format duration for display
     */
    private static function formatDuration($days): string
    {
        if ($days >= 365) {
            $years = floor($days / 365);
            return $years . ' ' . ($years > 1 ? 'Years' : 'Year');
        }

        if ($days >= 30) {
            $months = floor($days / 30);
            return $months . ' ' . ($months > 1 ? 'Months' : 'Month');
        }

        return $days . ' ' . ($days > 1 ? 'Days' : 'Day');
    }
}
