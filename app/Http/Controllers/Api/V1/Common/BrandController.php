<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;

class BrandController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $brands = Brand::with('models')
            ->where('is_active', true)
            ->get();

        return $this->apiResponse(
            in_error: false,
            message: "Brands retrieved successfully",
            data: BrandResource::collection($brands)
        );
    }
}
