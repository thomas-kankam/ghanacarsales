<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Car;
use App\Models\Plan;
use App\Transformers\CarTransformer;
use Illuminate\Support\Facades\Cache;

/**
 * File-cache friendly catalog helpers (works on shared hosting with CACHE_DRIVER=file).
 * No Redis required.
 */
class CatalogCacheService
{
    public const BRANDS_KEY = 'catalog.brands.all';
    public const PLANS_KEY = 'catalog.plans.all';
    public const HOT_LISTINGS_KEY = 'catalog.cars.hot';

    public const BRANDS_TTL_SECONDS = 3600;      // 1 hour
    public const PLANS_TTL_SECONDS = 3600;       // 1 hour
    public const HOT_LISTINGS_TTL_SECONDS = 120; // 2 minutes

    public function brands(): array
    {
        return Cache::remember(self::BRANDS_KEY, self::BRANDS_TTL_SECONDS, function () {
            return Brand::with('models')
                ->orderBy('name')
                ->get()
                ->map(fn (Brand $brand) => [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'image' => $brand->image,
                    'models' => $brand->models->pluck('name')->values()->all(),
                    'models_data' => $brand->models->map(fn ($model) => [
                        'id' => $model->id,
                        'name' => $model->name,
                        'slug' => $model->slug,
                    ])->values()->all(),
                ])
                ->values()
                ->all();
        });
    }

    public function plans()
    {
        return Cache::remember(self::PLANS_KEY, self::PLANS_TTL_SECONDS, function () {
            return Plan::orderBy('price')->get();
        });
    }

    /**
     * First page of published listings for homepage / hot cars (short TTL).
     */
    public function hotListings(int $limit = 12): array
    {
        $key = self::HOT_LISTINGS_KEY . '.' . $limit;

        return Cache::remember($key, self::HOT_LISTINGS_TTL_SECONDS, function () use ($limit) {
            return Car::query()
                ->where('status', 'published')
                ->where(function ($q) {
                    $q->whereNull('expiry_date')->orWhere('expiry_date', '>', now());
                })
                ->with('dealer')
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get()
                ->map(fn (Car $car) => CarTransformer::summary($car))
                ->values()
                ->all();
        });
    }

    public function forgetBrands(): void
    {
        Cache::forget(self::BRANDS_KEY);
    }

    public function forgetPlans(): void
    {
        Cache::forget(self::PLANS_KEY);
    }

    public function forgetHotListings(): void
    {
        Cache::forget(self::HOT_LISTINGS_KEY . '.12');
        Cache::forget(self::HOT_LISTINGS_KEY . '.6');
        Cache::forget(self::HOT_LISTINGS_KEY . '.15');
        Cache::forget(self::HOT_LISTINGS_KEY . '.30');
        Cache::forget('catalog.cars.browse.15.1');
        Cache::forget('catalog.cars.browse.100.1');
        Cache::forget('catalog.cars.browse.12.1');
    }

    public function forgetAll(): void
    {
        $this->forgetBrands();
        $this->forgetPlans();
        $this->forgetHotListings();
    }
}
