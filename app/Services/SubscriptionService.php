<?php
namespace App\Services;

use App\Models\Dealer;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionService
{
    public function listActivePlans(): Collection
    {
        return SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->get();
    }

    public function getCurrentSubscription(Dealer $dealer): ?Subscription
    {
        return $dealer->subscriptions()
            ->with('plan')
            ->where('status', 'active')
            ->where('ends_at', '>=', now())
            ->orderByDesc('ends_at')
            ->first();
    }

    public function getPaymentHistory(Dealer $dealer, int $perPage = 15): LengthAwarePaginator
    {
        return Payment::query()
            ->where('dealer_id', $dealer->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function initiateSubscription(
        Dealer $dealer,
        SubscriptionPlan $plan,
        string $phoneNumber,
        string $paymentMethod = 'momo',
        ?string $network = null
    ): Payment {
        return DB::transaction(function () use ($dealer, $plan, $phoneNumber, $paymentMethod, $network) {
            $subscription = Subscription::create([
                'dealer_id'       => $dealer->id,
                'plan_id'         => $plan->id,
                'status'          => 'pending',
                'published_count' => 0,
                'metadata'        => [
                    'plan_name'       => $plan->name,
                    'plan_slug'       => $plan->slug,
                    'publish_quota'   => $plan->publish_quota,
                    'duration_days'   => $plan->duration_days,
                ],
            ]);

            $payment = Payment::create([
                'payment_slug'   => Str::uuid(),
                'dealer_id'      => $dealer->id,
                'subscription_id'=> $subscription->id,
                'plan_id'        => $plan->id,
                'payment_type'   => 'subscription',
                'amount'         => $plan->price,
                'phone_number'   => $phoneNumber,
                'payment_method' => $paymentMethod,
                'provider'       => 'momo',
                'channel'        => 'api',
                'status'         => 'pending',
                'network'        => $network,
                'duration_days'  => $plan->duration_days,
                'metadata'       => [
                    'plan_name' => $plan->name,
                    'plan_slug' => $plan->slug,
                ],
                'reference'      => strtoupper(Str::random(12)),
            ]);

            $subscription->last_payment_id = $payment->id;
            $subscription->save();

            return $payment->load(['subscription.plan']);
        });
    }
}

