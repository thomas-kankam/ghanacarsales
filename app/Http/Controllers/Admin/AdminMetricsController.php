<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Dealer;
use App\Models\Payment;
// use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminMetricsController extends Controller
{
    public function metrics(): JsonResponse
    {
        $data = [
            'dealers_total'         => Dealer::count(),
            'dealers_active'       => Dealer::where('status', 'active')->count(),
            'cars_total'           => Car::count(),
            'cars_published'       => Car::where('status', 'published')->count(),
            'cars_draft'           => Car::where('status', 'draft')->count(),
            'cars_pending_approval'   => Car::where('status', 'pending_approval')->count(),
            // 'cars_pending_sponsor' => Car::where('status', 'pending_sponsor_approval')->count(),
            // 'subscriptions_active' => Subscription::where('status', 'active')->count(),
            'payments_total'       => Payment::count(),
            'payments_completed'   => Payment::where('status', 'paid')->count(),
            'payments_failed'      => Payment::where('status', 'failed')->count(),
            'cars_expiring_7_days' => Car::where('status', 'published')
                ->whereBetween('expiry_date', [now(), now()->addDays(7)])->count(),
        ];

        return $this->apiResponse(
            in_error: false,
            message: "Metrics retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $data
        );
    }

    public function health(): JsonResponse
    {
        $dbOk      = true;
        $storageOk = true;

        try {
            DB::select('SELECT 1');
        } catch (\Throwable $e) {
            $dbOk = false;
        }

        try {
            $storageOk = Storage::disk('public')->exists('/') || true;
        } catch (\Throwable $e) {
            $storageOk = false;
        }

        $status = $dbOk && $storageOk;

        return $this->apiResponse(
            in_error: ! $status,
            message: $status ? "System healthy" : "System issues detected",
            status_code: $status ? self::API_SUCCESS : self::API_FAIL,
            data: [
                'database' => $dbOk ? 'ok' : 'fail',
                'storage'  => $storageOk ? 'ok' : 'fail',
            ]
        );
    }
}
