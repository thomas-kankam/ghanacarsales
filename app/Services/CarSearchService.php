<?php

namespace App\Services;

use App\Models\Car;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class CarSearchService
{
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Car::with(['brand', 'model', 'images'])
            ->where('status', 'active');

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        return $query->paginate($perPage);
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        if (isset($filters['model_id'])) {
            $query->where('model_id', $filters['model_id']);
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

        if (isset($filters['fuel_type'])) {
            $query->where('fuel_type', $filters['fuel_type']);
        }

        if (isset($filters['transmission'])) {
            $query->where('transmission', $filters['transmission']);
        }

        if (isset($filters['colour'])) {
            $query->where('colour', $filters['colour']);
        }

        if (isset($filters['location'])) {
            $query->where('location', $filters['location']);
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
}
