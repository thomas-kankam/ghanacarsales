<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminPlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $plans = SubscriptionPlan::query()
            ->when($request->filled('is_active'), function ($q) use ($request) {
                $q->where('is_active', filter_var($request->get('is_active'), FILTER_VALIDATE_BOOL));
            })
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
            'name'          => ['required', 'string', 'max:255'],
            'slug'          => ['nullable', 'string', 'max:255', 'unique:subscription_plans,slug'],
            'price'         => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'publish_quota' => ['required', 'integer', 'min:1'],
            'features'      => ['nullable', 'array'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $plan = SubscriptionPlan::create($data);

        return $this->apiResponse(
            in_error: false,
            message: "Subscription plan created successfully",
            status_code: self::API_CREATED,
            data: $plan
        );
    }

    public function update(Request $request, $id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);

        $data = $request->validate([
            'name'          => ['nullable', 'string', 'max:255'],
            'slug'          => ['nullable', 'string', 'max:255', 'unique:subscription_plans,slug,' . $plan->id],
            'price'         => ['nullable', 'numeric', 'min:0'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'publish_quota' => ['nullable', 'integer', 'min:1'],
            'features'      => ['nullable', 'array'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        if (isset($data['name']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $plan->update($data);

        return $this->apiResponse(
            in_error: false,
            message: "Subscription plan updated successfully",
            status_code: self::API_SUCCESS,
            data: $plan->fresh()
        );
    }

    public function destroy($id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $plan->update(['is_active' => false]);

        return $this->apiResponse(
            in_error: false,
            message: "Subscription plan deactivated successfully",
            status_code: self::API_SUCCESS
        );
    }
}

