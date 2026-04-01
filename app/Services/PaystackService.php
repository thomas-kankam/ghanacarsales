<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaystackService
{
    protected string $secretKey;
    protected string $paymentUrl;

    public function __construct()
    {
        $this->secretKey = (string) (config('services.paystack.secret_key') ?? '');
        $this->paymentUrl = rtrim(config('services.paystack.payment_url', 'https://api.paystack.co'), '/');
    }

    /**
     * Initialize a Paystack transaction. Returns authorization_url and reference.
     */
    public function initializeTransaction(
        Payment $payment,
        // string $callbackUrl,
        ?string $email = null
    ): array {
        $reference = $payment->reference_id ?? $payment->reference ?? ('GHCS' . time() . strtoupper(Str::random(8)));
        $payment->update(['reference_id' => $reference, 'reference' => $reference]);

        $amountInCedis = (float) $payment->amount;
        $amountInPesewas = (int) round($amountInCedis * 100);

        $response = Http::withToken($this->secretKey)
            ->post("{$this->paymentUrl}/transaction/initialize", [
                'email'       => $email ?? $payment->dealer?->email ?? 'customer@example.com',
                'amount'      => $amountInPesewas,
                // 'callback_url' => $callbackUrl,
                'reference'    => $reference,
                'metadata'    => [
                    'payment_slug' => $payment->payment_slug,
                    'dealer_slug'  => $payment->dealer_slug,
                    'car_slugs'    => $payment->cars->pluck('car_slug')->toArray(),
                    'plan_slug'    => $payment->plan_slug,
                    'reference_id' => $reference,
                    'amount'       => $amountInCedis,
                    'phone' => $payment->phone_number,
                ],
            ]);

        $body = $response->json();
        if (!($response->successful() && ($body['status'] ?? false))) {
            Log::channel('single')->warning('Paystack initialize failed', [
                'message' => $body['message'] ?? 'Unknown',
                'status'  => $response->status(),
            ]);
            return [
                'success'            => false,
                'authorization_url'  => null,
                'reference'          => $reference,
                'message'            => $body['message'] ?? 'Failed to initialize payment',
            ];
        }

        $data = $body['data'] ?? [];
        return [
            'success'            => true,
            'authorization_url'  => $data['authorization_url'] ?? null,
            'access_code'        => $data['access_code'] ?? null,
            'reference'          => $data['reference'] ?? $reference,
        ];
    }

    /**
     * Verify Paystack webhook signature. Production: webhook_secret must be set.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = config('services.paystack.webhook_secret');
        if (empty($secret)) {
            if (config('app.env') === 'production') {
                Log::channel('paystack')->warning('Paystack webhook: PAYSTACK_WEBHOOK_SECRET not set in production');
                return false;
            }
            return true;
        }
        if (empty($signature)) {
            Log::channel('paystack')->warning('Paystack webhook: signature is empty');
            return false;
        }
        $computed = hash_hmac('sha512', $payload, $secret);
        return hash_equals($computed, $signature);
    }

    /**
     * Verify transaction status with Paystack API.
     */
    public function verifyTransaction(string $reference): ?array
    {
        $response = Http::withToken($this->secretKey)
            ->get("{$this->paymentUrl}/transaction/verify/{$reference}");

        $body = $response->json();
        if (!$response->successful()) {
            return null;
        }
        $data = $body['data'] ?? [];
        $status = $data['status'] ?? '';
        return [
            'status'   => $status,
            'paid'     => $status === 'success',
            'reference' => $data['reference'] ?? $reference,
        ];
    }
}
