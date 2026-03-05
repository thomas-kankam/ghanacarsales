<?php
namespace App\Services;

use App\Models\Dealer;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SubscriptionService
{
    public function listActivePlans(): Collection
    {
        return Plan::query()
            ->orderBy('price')
            ->get();
    }

    public function getCurrentSubscription(Dealer $dealer): ?Subscription
    {
        return Subscription::query()
            ->where('dealer_slug', $dealer->dealer_slug)
            ->where('status', 'active')
            ->where('expiry_date', '>=', now())
            ->orderByDesc('expiry_date')
            ->first();
    }

    public function getPaymentHistory(Dealer $dealer, int $perPage = 15): LengthAwarePaginator
    {
        return Payment::query()
            ->where('dealer_slug', $dealer->dealer_slug)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
