<?php
namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Services\CatalogCacheService;

class PlanController extends Controller
{
    public function __construct(private CatalogCacheService $catalogCache)
    {
    }

    public function getPlans()
    {
        return $this->apiResponse(
            in_error: false,
            message: "Plans retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $this->catalogCache->plans(),
            reason: "Plans retrieved successfully."
        );
    }
}
