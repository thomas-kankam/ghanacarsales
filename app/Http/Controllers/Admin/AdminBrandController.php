<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CarModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminBrandController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $brands = Brand::with('models')
            ->orderBy('name')
            ->paginate((int) $request->get('per_page', 20));

        $payload = [
            'items' => $brands->getCollection()->map(fn (Brand $brand) => $this->transformBrand($brand))->values()->all(),
            'meta' => [
                'current_page' => $brands->currentPage(),
                'last_page' => $brands->lastPage(),
                'per_page' => $brands->perPage(),
                'total' => $brands->total(),
            ],
        ];

        return $this->apiResponse(
            in_error: false,
            message: "Brands retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $payload
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

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:brands,name'],
            'models' => ['nullable', 'array'],
            'models.*' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'starts_with:data:,http://,https://'],
        ]);

        $brand = DB::transaction(function () use ($data) {
            $brand = Brand::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'image' => self::base64ImageDecode($data['image'] ?? null),
            ]);

            $this->syncModels($brand, $data['models'] ?? []);

            return $brand->fresh('models');
        });

        return $this->apiResponse(
            in_error: false,
            message: "Brand created successfully",
            status_code: self::API_CREATED,
            data: $this->transformBrand($brand)
        );
    }

    public function update(Request $request, $id): JsonResponse
    {
        $brand = Brand::with('models')->findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:brands,name,' . $brand->id],
            'models' => ['sometimes', 'array'],
            'models.*' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'starts_with:data:,http://,https://'],
        ]);

        $brand = DB::transaction(function () use ($brand, $data) {
            if (array_key_exists('name', $data)) {
                $brand->name = $data['name'];
                $brand->slug = Str::slug($data['name']);
            }

            if (array_key_exists('image', $data) && $data['image']) {
                self::deleteImage($brand->image);
                $brand->image = self::base64ImageDecode($data['image']);
            }

            $brand->save();

            if (array_key_exists('models', $data)) {
                $this->syncModels($brand, $data['models'] ?? []);
            }

            return $brand->fresh('models');
        });

        return $this->apiResponse(
            in_error: false,
            message: "Brand updated successfully",
            status_code: self::API_SUCCESS,
            data: $this->transformBrand($brand)
        );
    }

    public function destroy($id): JsonResponse
    {
        $brand = Brand::with('models')->findOrFail($id);

        DB::transaction(function () use ($brand) {
            self::deleteImage($brand->image);
            $brand->delete();
        });

        return $this->apiResponse(
            in_error: false,
            message: "Brand deleted successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }

    protected function syncModels(Brand $brand, array $models): void
    {
        $normalized = collect($models)
            ->filter(fn ($model) => is_string($model) && trim($model) !== '')
            ->map(fn ($model) => trim($model))
            ->unique(fn ($model) => Str::lower($model))
            ->values();

        $brand->models()->delete();

        foreach ($normalized as $modelName) {
            CarModel::create([
                'brand_id' => $brand->id,
                'name' => $modelName,
                'slug' => Str::slug($modelName),
            ]);
        }
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
            'created_at' => optional($brand->created_at)->toIso8601String(),
            'updated_at' => optional($brand->updated_at)->toIso8601String(),
        ];
    }
}

