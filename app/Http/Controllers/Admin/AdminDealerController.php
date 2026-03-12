<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDealerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Dealer::query();

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('is_onboarded')) {
            $query->where('is_onboarded', filter_var($request->get('is_onboarded'), FILTER_VALIDATE_BOOL));
        }

        $dealers = $query->paginate((int) $request->get('per_page', 20));

        return $this->apiResponse(
            in_error: false,
            message: "Dealers retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $dealers
        );
    }

    public function show($id): JsonResponse
    {
        // let the data show a dealer with his car and it's associated payments
        $dealer = Dealer::with('cars.paymentItems.payment')->findOrFail($id);

        return $this->apiResponse(
            in_error: false,
            message: "Dealer retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $dealer
        );
    }

    public function update(Request $request, $id): JsonResponse
    {
        $dealer = Dealer::findOrFail($id);

        $data = $request->validate([
            'status'       => ['nullable', 'string', 'in:active,deactivated,suspended'],
            'is_onboarded' => ['nullable', 'boolean'],
        ]);

        $dealer->update($data);

        return $this->apiResponse(
            in_error: false,
            message: "Dealer updated successfully",
            status_code: self::API_SUCCESS,
            data: $dealer->fresh()
        );
    }

    public function destroy($id): JsonResponse
    {
        $dealer = Dealer::findOrFail($id);
        $dealer->delete();

        return $this->apiResponse(
            in_error: false,
            message: "Dealer deleted successfully",
            status_code: self::API_NO_CONTENT
        );
    }

    public function trashed(Request $request): JsonResponse
    {
        $dealers = Dealer::onlyTrashed()->paginate((int) $request->get('per_page', 20));

        return $this->apiResponse(
            in_error: false,
            message: "Trashed dealers retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $dealers
        );
    }

    public function restore($id): JsonResponse
    {
        $dealer = Dealer::onlyTrashed()->findOrFail($id);
        $dealer->restore();

        return $this->apiResponse(
            in_error: false,
            message: "Dealer restored successfully",
            status_code: self::API_SUCCESS,
            data: $dealer->fresh()
        );
    }

    public function forceDelete($id): JsonResponse
    {
        $dealer = Dealer::onlyTrashed()->findOrFail($id);
        $dealer->forceDelete();

        return $this->apiResponse(
            in_error: false,
            message: "Dealer permanently deleted",
            status_code: self::API_NO_CONTENT
        );
    }
}
