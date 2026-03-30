<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminDealerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Dealer::query()
            ->withCount([
                'cars as listings_count' => function ($q) {
                    $q->where('status', '!=', 'draft');
                },
            ]);

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

    /**
     * Activate a dealer account (status = active).
     */
    public function activate($id): JsonResponse
    {
        $dealer = Dealer::findOrFail($id);
        $dealer->update(['status' => 'active']);

        return $this->apiResponse(
            in_error: false,
            message: "Dealer activated successfully",
            status_code: self::API_SUCCESS,
            data: $dealer->fresh()
        );
    }

    /**
     * Deactivate a dealer account (status = deactivated).
     */
    public function deactivate($id): JsonResponse
    {
        $dealer = Dealer::findOrFail($id);
        $dealer->update(['status' => 'deactivated']);

        return $this->apiResponse(
            in_error: false,
            message: "Dealer deactivated successfully",
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

    public function assignCode(Dealer $dealer): JsonResponse
    {
        $dealer->update([
            'dealer_code'      => $this->generateDealerCode(),
            'code_status'      => 'assigned',
            'code_assigned_at' => now(),
            'code_revoked_at'  => null,
            'reason'           => null,
        ]);

        return $this->apiResponse(
            in_error: false,
            message: "Dealer code assigned successfully",
            status_code: self::API_SUCCESS,
            data: $dealer->fresh()
        );
    }

    public function dealerCodes(Request $request): JsonResponse
    {
        $query = Dealer::query()
            ->whereNotNull('dealer_code')
            ->orderByDesc('code_assigned_at')
            ->orderByDesc('updated_at');

        if ($request->filled('code_status')) {
            $query->where('code_status', $request->get('code_status'));
        }

        $dealerCodes = $query->paginate((int) $request->get('per_page', 20));

        return $this->apiResponse(
            in_error: false,
            message: "Dealer codes retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $dealerCodes
        );
    }

    public function revokeCode(Request $request, Dealer $dealer): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if (!$dealer->dealer_code) {
            return $this->apiResponse(
                in_error: true,
                message: "Dealer code not found",
                status_code: self::API_BAD_REQUEST,
                data: []
            );
        }

        $dealer->update([
            'code_status'     => 'revoked',
            'code_revoked_at' => now(),
            'reason'          => $data['reason'],
        ]);

        return $this->apiResponse(
            in_error: false,
            message: "Dealer code revoked successfully",
            status_code: self::API_SUCCESS,
            data: $dealer->fresh()
        );
    }

    protected function generateDealerCode(): string
    {
        do {
            $code = 'GHCS' . strtoupper(Str::random(8));
        } while (Dealer::where('dealer_code', $code)->exists());

        return $code;
    }
}
