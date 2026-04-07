<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CarModel;
use Illuminate\Http\JsonResponse;

class BrandController extends Controller
{
    public function index(): JsonResponse
    {
        $brands = Brand::with('models')->orderBy('name')->get();

        return $this->apiResponse(
            in_error: false,
            message: "Brands retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $brands->map(fn (Brand $brand) => $this->transformBrand($brand))->values()->all()
        );
    }

    public function show($id): JsonResponse
    {
        $brand = Brand::with('models')->findOrFail($id);

        return $this->apiResponse(
            in_error: false,
            message: "Brand retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $this->transformBrand($brand)
        );
    }

    public function models($id): JsonResponse
    {
        $brand = Brand::with('models')->findOrFail($id);

        return $this->apiResponse(
            in_error: false,
            message: "Brand models retrieved successfully",
            status_code: self::API_SUCCESS,
            data: [
                'brand_id' => $brand->id,
                'brand_name' => $brand->name,
                'models' => $brand->models->pluck('name')->values()->all(),
                'models_data' => $brand->models->map(fn (CarModel $model) => [
                    'id' => $model->id,
                    'name' => $model->name,
                    'slug' => $model->slug,
                ])->values()->all(),
            ]
        );
    }

    protected function transformBrand(Brand $brand): array
    {
        return [
            'id' => $brand->id,
            'name' => $brand->name,
            'slug' => $brand->slug,
            'image' => $brand->image,
            'models' => $brand->models->pluck('name')->values()->all(),
            'models_data' => $brand->models->map(fn (CarModel $model) => [
                'id' => $model->id,
                'name' => $model->name,
                'slug' => $model->slug,
            ])->values()->all(),
        ];
    }
}

