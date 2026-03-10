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
}
