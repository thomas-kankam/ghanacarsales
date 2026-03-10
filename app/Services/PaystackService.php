<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    protected string $secretKey;
    protected string $paymentUrl;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key', '');
        $this->paymentUrl = rtrim(config('services.paystack.payment_url', 'https://api.paystack.co'), '/');
    }

    /**
     * Initialize a Paystack transaction. Returns authorization_url and reference.
     */
    public function initializeTransaction(
        Payment $payment,
        string $callbackUrl,
        ?string $email = null
    ): array {
        $reference = $payment->reference_id ?? $payment->reference ?? ('GHCS' . time() . strtoupper(\Illuminate\Support\Str::random(8)));
        $payment->update(['reference_id' => $reference]);
        // $payment->update(['reference_id' => $reference, 'reference' => $reference]);

        $amountInCedis = (float) $payment->amount;
        $amountInPesewas = (int) round($amountInCedis * 100);

        $response = Http::withToken($this->secretKey)
            ->post("{$this->paymentUrl}/transaction/initialize", [
                'email'       => $email ?? $payment->dealer?->email ?? 'customer@example.com',
                'amount'      => $amountInPesewas,
                'reference'   => $reference,
                'callback_url' => $callbackUrl,
                'metadata'    => [
                    'payment_slug' => $payment->payment_slug,
                    'dealer_slug'  => $payment->dealer_slug,
                ],
                'channels' => ['card', 'mobile_money'],
            ]);

        $body = $response->json();
        if (!($response->successful() && ($body['status'] ?? false))) {
            Log::warning('Paystack initialize failed', ['response' => $body]);
            return [
                'success' => false,
                'authorization_url' => null,
                'reference' => $reference,
                'message' => $body['message'] ?? 'Failed to initialize payment',
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
     * Verify Paystack webhook signature.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = config('services.paystack.webhook_secret');
        if (empty($secret)) {
            return true;
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
