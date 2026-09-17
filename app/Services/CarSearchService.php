<?php

namespace App\Services;

use App\Models\Car;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class CarSearchService
{
    /**
     * Search cars: status=published and (expiry_date null or > now).
     */
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $page = max(1, (int) ($filters['page'] ?? 1));

        // Cache only the unfiltered first page (homepage / default browse) — file cache safe.
        if ($this->isDefaultBrowse($filters, $page, $perPage)) {
            $cacheKey = "catalog.cars.browse.{$perPage}.{$page}";

            return Cache::remember($cacheKey, 120, function () use ($filters, $perPage) {
                return $this->runSearch($filters, $perPage);
            });
        }

        return $this->runSearch($filters, $perPage);
    }

    protected function runSearch(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Car::query()
            ->where('status', 'published')
            ->where(function (Builder $q) {
                $q->whereNull('expiry_date')->orWhere('expiry_date', '>', now());
            });

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        return $query->with('dealer')->paginate($perPage);
    }

    protected function isDefaultBrowse(array $filters, int $page, int $perPage): bool
    {
        if ($page !== 1) {
            return false;
        }

        $ignored = ['page', 'per_page', 'sort_by', 'sort_order'];
        foreach ($filters as $key => $value) {
            if (in_array($key, $ignored, true)) {
                continue;
            }
            if ($value !== null && $value !== '' && $value !== []) {
                return false;
            }
        }

        return true;
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            $query->where(function (Builder $outer) use ($search, $terms) {
                if ($this->supportsFullText() && mb_strlen($search) >= 3) {
                    $outer->orWhereFullText(
                        ['brand', 'model', 'description', 'location', 'region', 'colour'],
                        $search
                    );
                }

                foreach ($terms as $term) {
                    $like = '%' . $term . '%';
                    $outer->orWhere(function (Builder $q) use ($like) {
                        $q->where('brand', 'like', $like)
                            ->orWhere('model', 'like', $like)
                            ->orWhere('description', 'like', $like)
                            ->orWhere('location', 'like', $like)
                            ->orWhere('region', 'like', $like)
                            ->orWhere('colour', 'like', $like)
                            ->orWhere('car_slug', 'like', $like)
                            ->orWhereRaw(
                                "CONCAT(COALESCE(brand, ''), ' ', COALESCE(model, '')) LIKE ?",
                                [$like]
                            );
                    });
                }
            });
        }

        if (isset($filters['brand'])) {
            $query->where('brand', $filters['brand']);
        }

        if (isset($filters['model'])) {
            $query->where('model', $filters['model']);
        }

        if (isset($filters['min_year'])) {
            $query->where('year_of_manufacture', '>=', $filters['min_year']);
        }

        if (isset($filters['max_year'])) {
            $query->where('year_of_manufacture', '<=', $filters['max_year']);
        }

        if (isset($filters['min_mileage'])) {
            $query->where('mileage', '>=', $filters['min_mileage']);
        }

        if (isset($filters['max_mileage'])) {
            $query->where('mileage', '<=', $filters['max_mileage']);
        }

        if (isset($filters['mileage_unit'])) {
            $query->where('mileage_unit', $filters['mileage_unit']);
        }

        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        if (isset($filters['swap_deals'])) {
            $query->where('swap_deals', $filters['swap_deals']);
        }

        if (isset($filters['aircon'])) {
            $query->where('aircon', $filters['aircon']);
        }

        if (isset($filters['registered'])) {
            $query->where('registered', $filters['registered']);
        }

        if (isset($filters['fuel_type']) && is_array($filters['fuel_type'])) {
            $query->whereIn('fuel_type', $filters['fuel_type']);
        } elseif (isset($filters['fuel_type'])) {
            $query->where('fuel_type', $filters['fuel_type']);
        }

        if (isset($filters['transmission'])) {
            $query->where('transmission', $filters['transmission']);
        }

        if (isset($filters['colour'])) {
            $query->where('colour', $filters['colour']);
        }

        if (isset($filters['region'])) {
            $query->where('region', $filters['region']);
        }

        if (isset($filters['location']) && $filters['location'] !== '') {
            $query->where('location', 'like', '%' . $filters['location'] . '%');
        }
    }

    protected function applySorting(Builder $query, array $filters): void
    {
        $sortBy = $filters['sort_by'] ?? 'price';
        $sortOrder = $filters['sort_order'] ?? 'asc';

        switch ($sortBy) {
            case 'year':
                $query->orderBy('year_of_manufacture', $sortOrder);
                break;
            case 'mileage':
                $query->orderBy('mileage', $sortOrder);
                break;
            case 'price':
            default:
                $query->orderBy('price', $sortOrder);
                break;
        }
    }

    protected function supportsFullText(): bool
    {
        static $supported = null;

        if ($supported !== null) {
            return $supported;
        }

        $driver = Schema::getConnection()->getDriverName();
        $supported = in_array($driver, ['mysql', 'mariadb'], true);

        return $supported;
    }
}
