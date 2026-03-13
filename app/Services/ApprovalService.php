<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\Dealer;
use Illuminate\Support\Str;

class ApprovalService
{
    public function createForCar(
        string $carSlug,
        Dealer $dealer,
        string $type,
        string $status = 'pending',
        ?string $dealerCode = null,
        ?string $paymentSlug = null
    ): Approval {
        return Approval::create([
            'approval_slug' => (string) Str::uuid(),
            'car_slug'      => $carSlug,
            'dealer_slug'   => $dealer->dealer_slug,
            'type'          => $type,
            'status'        => $status,
            'dealer_code'   => $dealerCode,
            'dealer_name'   => $dealer->full_name ?? $dealer->business_name,
            'payment_slug' => $paymentSlug,
        ]);
    }

    public function approve(Approval $approval, ?string $adminSlug = null): void
    {
        $approval->update([
            'admin_approval'    => true,
            'admin_approval_at' => now(),
            'admin_slug'        => $adminSlug,
            'status'            => 'approved',
        ]);
    }

    public function reject(Approval $approval, ?string $reason = null, ?string $adminSlug = null): void
    {
        $approval->update([
            'admin_approval'    => false,
            'admin_approval_at' => now(),
            'admin_slug'        => $adminSlug,
            'status'            => 'rejected',
            'reason'            => $reason,
        ]);
    }

    /**
     * Revert an approval (and car) from approved/rejected/expired back to pending_approval.
     * Resets approval status to pending and clears admin/reason fields; sets car status to pending_approval.
     */
    public function revertToPending(Approval $approval): void
    {
        $approval->update([
            'admin_approval'    => null,
            'admin_approval_at' => null,
            'admin_slug'        => null,
            'status'            => 'pending',
            'reason'            => null,
        ]);
        $car = $approval->car;
        if ($car) {
            $car->update(['status' => 'pending_approval']);
        }
    }
}
