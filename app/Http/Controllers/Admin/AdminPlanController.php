<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminPlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $plans = Plan::query()
            ->orderBy('price')
            ->get();

        return $this->apiResponse(
            in_error: false,
            message: "Subscription plans retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $plans
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_name'     => ['required', 'string', 'max:255'],
            'plan_slug'     => ['nullable', 'string', 'max:255', 'unique:plans,plan_slug'],
            'price'         => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'features'      => ['nullable', 'array'],
        ]);

        $data['plan_slug'] = $data['plan_slug'] ?? Str::slug($data['plan_name']);

        $plan = Plan::create($data);

        return $this->apiResponse(
            in_error: false,
            message: "Plan created successfully",
            status_code: self::API_CREATED,
            data: $plan
        );
    }

    public function update(Request $request, $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);

        $data = $request->validate([
            'plan_name'     => ['nullable', 'string', 'max:255'],
            'plan_slug'     => ['nullable', 'string', 'max:255', 'unique:plans,plan_slug,' . $plan->id],
            'price'         => ['nullable', 'numeric', 'min:0'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'features'      => ['nullable', 'array'],
        ]);

        if (isset($data['plan_name']) && empty($data['plan_slug'])) {
            $data['plan_slug'] = Str::slug($data['plan_name']);
        }

        $plan->update($data);

        return $this->apiResponse(
            in_error: false,
            message: "Plan updated successfully",
            status_code: self::API_SUCCESS,
            data: $plan->fresh()
        );
    }

    public function destroy($id): JsonResponse
    {
        $plan = Plan::findOrFail($id);
        $plan->delete();

        return $this->apiResponse(
            in_error: false,
            message: "Plan deleted successfully",
            status_code: self::API_SUCCESS
        );
    }
}
