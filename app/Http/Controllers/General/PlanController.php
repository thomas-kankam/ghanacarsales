<?php
namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\Plan;

class PlanController extends Controller
{
    public function getPlans()
    {
        $plans = Plan::all();
        return $this->apiResponse(
            in_error: false,
            message: "Plans retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $plans->toArray(),
            reason: "Plans retrieved successfully."
        );
    }
}
