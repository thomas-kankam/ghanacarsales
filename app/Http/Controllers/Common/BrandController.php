<?php
namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BrandController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            "data" => "God is good",
        ]);

        // $brands = Brand::with('models')
        //     ->where('is_active', true)
        //     ->get();

        // return $this->apiResponse(
        //     in_error: false,
        //     message: "Brands retrieved successfully",
        //     data: BrandResource::collection($brands)
        // );
    }
}
