<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Approval;
use App\Models\Car;
use App\Models\Dealer;
use App\Traits\AppNotifications;
use Illuminate\Support\Str;

class ApprovalService
{
    use AppNotifications;

    protected function carLabel(?Car $car, string $fallbackSlug): string
    {
        if (! $car) {
            return $fallbackSlug;
        }

        return trim(($car->brand ?? '') . ' ' . ($car->model ?? '')) ?: $fallbackSlug;
    }

    protected function notifyDealer(Dealer $dealer, string $subject, string $body): void
    {
        if (! empty($dealer->email)) {
            self::sendEmail(
                $dealer->email,
                email_class: "App\Mail\DealerCarNotification",
                parameters: [$dealer->email, $subject, $body]
            );
        }

        if (! empty($dealer->phone_number)) {
            self::sendSms($dealer->phone_number, $body);
        }
    }

    protected function notifyActiveAdmins(string $body): void
    {
        $admins = Admin::query()->where('is_active', true)->get(['email', 'phone_number']);
        foreach ($admins as $admin) {
            if (! empty($admin->email)) {
                self::sendEmail(
                    $admin->email,
                    email_class: "App\Mail\AdminPendingApproval",
                    parameters: [$admin->email, $body]
                );
            }
            if (! empty($admin->phone_number)) {
                self::sendSms($admin->phone_number, $body);
            }
        }
    }

    public function notifyAdminsCarUploaded(Dealer $dealer, Car $car): void
    {
        $dealerLabel = $dealer->full_name ?? $dealer->business_name ?? $dealer->dealer_slug;
        $carLabel = $this->carLabel($car, $car->car_slug);
        $message = "Seller uploaded a car listing. Dealer: {$dealerLabel}. Car: {$carLabel}.";

        $this->notifyActiveAdmins($message);
    }

    /**
     * Friend-code flow: dealer_code must belong to a different dealer, and must not already be consumed.
     *
     * @return string|null Human-readable error reason for API responses, or null when valid.
     */
    public function friendCodeDealerCodeError(Dealer $submitter, string $dealerCode): ?string
    {
        $dealerCode = trim($dealerCode);
        if ($dealerCode === '') {
            return 'dealer_code is required for friend code flow.';
        }

        $assignee = Dealer::where('dealer_code', $dealerCode)->first();
        if (! $assignee) {
            return 'Invalid dealer code.';
        }

        if ($assignee->dealer_slug === $submitter->dealer_slug) {
            return 'You cannot use your own dealer code. Use a code assigned to another dealer.';
        }

        if (Approval::where('dealer_code', $dealerCode)->exists()) {
            return 'This dealer code has already been used.';
        }

        return null;
    }

    public function createForCar(
        string $carSlug,
        Dealer $dealer,
        string $type,
        string $status = 'pending',
        ?string $dealerCode = null,
        ?string $paymentSlug = null
    ): Approval {
        $existing = Approval::where('car_slug', $carSlug)
            ->where('payment_slug', $paymentSlug)
            ->where('type', $type)
            ->latest()
            ->first();

        if ($existing) {
            $existing->update([
                'status'      => $status,
                'dealer_code' => $dealerCode,
                'dealer_name' => $dealer->full_name ?? $dealer->business_name,
            ]);

            return $existing->fresh();
        }

        $approval = Approval::create([
            'approval_slug' => (string) Str::uuid(),
            'car_slug'      => $carSlug,
            'dealer_slug'   => $dealer->dealer_slug,
            'type'          => $type,
            'status'        => $status,
            'dealer_code'   => $dealerCode,
            'dealer_name'   => $dealer->full_name ?? $dealer->business_name,
            'payment_slug'  => $paymentSlug,
        ]);

        $dealerLabel = $dealer->full_name ?? $dealer->business_name ?? 'Dealer';
        $carModel = Car::where('car_slug', $carSlug)->first();
        $carLabel = $this->carLabel($carModel, $carSlug);
        $message = "A new car listing has been submitted for approval. Dealer: {$dealerLabel}. Car: {$carLabel}.";

        $this->notifyActiveAdmins($message);

        $this->notifyDealer(
            $dealer,
            'Car Submitted for Approval',
            "Your car listing ({$carLabel}) has been submitted for admin approval."
        );

        return $approval;
    }

    public function approve(Approval $approval, ?string $adminSlug = null): void
    {
        $approval->update([
            'admin_approval'    => true,
            'admin_approval_at' => now(),
            'admin_slug'        => $adminSlug,
            'status'            => 'approved',
        ]);

        $car = $approval->car;
        $dealer = $approval->dealer;
        if ($dealer) {
            $carLabel = $this->carLabel($car, $approval->car_slug);
            $this->notifyDealer(
                $dealer,
                'Car Approved',
                "Good news! Your car listing ({$carLabel}) has been approved and is now active."
            );
        }
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

        $car = $approval->car;
        $dealer = $approval->dealer;
        if ($dealer) {
            $carLabel = $this->carLabel($car, $approval->car_slug);
            $suffix = $reason ? " Reason: {$reason}" : '';
            $this->notifyDealer(
                $dealer,
                'Car Rejected',
                "Your car listing ({$carLabel}) was rejected by admin.{$suffix}"
            );
        }
    }

    public function notifyDealerForceExpired(Car $car): void
    {
        $dealer = $car->dealer;
        if (! $dealer) {
            return;
        }

        $carLabel = $this->carLabel($car, $car->car_slug);
        $this->notifyDealer(
            $dealer,
            'Car Force Expired',
            "Your car listing ({$carLabel}) has been force-expired by admin."
        );
    }

    /**
     * Revert an approval (and car) from approved/rejected/expired back to pending_approval.
     * Resets approval status to pending and clears admin/reason fields; sets car status to pending_approval.
     */
    public function revertToPending(Approval $approval): void
    {
        $approval->update([
            'admin_approval'    => false,
            'admin_approval_at' => null,
            'admin_slug'        => null,
            'status'            => 'pending',
            'reason'            => null,
        ]);
        $car = $approval->car;
        if ($car) {
            $car->update(
                [
                    'status' => 'pending_approval',
                    'start_date' => null,
                    'expiry_date' => null,
                ]
            );
        }
    }
}
