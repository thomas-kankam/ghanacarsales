<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
            'price'         => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'features'      => ['nullable', 'array'],
        ]);

        // Always derive slug from plan_name (e.g. "10 Months" => "10_months").
        $data['plan_slug'] = Str::slug($data['plan_name'], '_');
        $this->assertPlanSlugIsAvailable($data['plan_slug']);

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
            'price'         => ['nullable', 'numeric', 'min:0'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'features'      => ['nullable', 'array'],
        ]);

        if (isset($data['plan_name'])) {
            $data['plan_slug'] = Str::slug($data['plan_name'], '_');
            $this->assertPlanSlugIsAvailable($data['plan_slug'], $plan->id);
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

    private function assertPlanSlugIsAvailable(string $slug, ?int $ignorePlanId = null): void
    {
        $query = Plan::query()->where('plan_slug', $slug);

        if ($ignorePlanId !== null) {
            $query->where('id', '!=', $ignorePlanId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'plan_name' => ['A plan with this name already exists.'],
            ]);
        }
    }
}
