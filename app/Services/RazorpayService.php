<?php

namespace App\Services;

/**
 * Razorpay gateway adapter.
 *
 * Production: uses the official `razorpay/razorpay` PHP SDK
 * (`composer require razorpay/razorpay`) when RAZORPAY_KEY_ID /
 * RAZORPAY_KEY_SECRET are set. Without the SDK/keys (local dev, CI) it
 * falls back to a deterministic offline stub so the wallet recharge flow
 * stays fully testable: order ids look like `order_test_<uniqid>`.
 *
 * Signature verification follows Razorpay's documented scheme:
 *   expected = HMAC_SHA256(razorpay_order_id|razorpay_payment_id, key_secret)
 * compared with hash_equals() against razorpay_signature.
 */
final class RazorpayService
{
    public function isConfigured(): bool
    {
        return (string) config('services.razorpay.key_id') !== ''
            && (string) config('services.razorpay.key_secret') !== '';
    }

    /**
     * Create a Razorpay order for a recharge of ₹amount (amount in rupees).
     *
     * @return array{order_id: string, amount: string, currency: string, key_id: string|null}
     */
    public function createOrder(string $amountRupees, string $receipt): array
    {
        $amount = number_format(max((float) $amountRupees, 0), 2, '.', '');
        $paise = (int) round(((float) $amount) * 100);

        if ($this->sdkAvailable() && $this->isConfigured()) {
            $api = $this->api();
            /** @var object $order */
            $order = $api->order->create([
                'amount' => $paise,
                'currency' => 'INR',
                'receipt' => substr($receipt, 0, 40),
                'payment_capture' => 1,
            ]);
            $id = is_array($order) ? ($order['id'] ?? null) : ($order->id ?? null);

            return [
                'order_id' => (string) $id,
                'amount' => $amount,
                'currency' => 'INR',
                'key_id' => (string) config('services.razorpay.key_id'),
            ];
        }

        // Offline stub (dev/CI/test): deterministic, no network.
        return [
            'order_id' => 'order_test_'.uniqid(),
            'amount' => $amount,
            'currency' => 'INR',
            'key_id' => (string) config('services.razorpay.key_id') ?: null,
        ];
    }

    /**
     * Verify a Razorpay payment signature. In stub mode (no secret
     * configured) any non-empty signature is accepted so the mobile flow can
     * be exercised end-to-end without live keys.
     */
    public function verifySignature(string $orderId, string $paymentId, string $signature): bool
    {
        if ($orderId === '' || $paymentId === '' || $signature === '') {
            return false;
        }

        $secret = (string) config('services.razorpay.key_secret');

        // Test hook: when the secret is the literal 'test', the signature
        // must equal HMAC_SHA256(order|payment, 'test') — lets PHPUnit cover
        // the real crypto path without network access.
        if ($secret !== '' && $this->sdkAvailable()) {
            try {
                $this->api()->utility->verifyPaymentSignature([
                    'razorpay_order_id' => $orderId,
                    'razorpay_payment_id' => $paymentId,
                    'razorpay_signature' => $signature,
                ]);

                return true;
            } catch (\Throwable) {
                return false;
            }
        }

        if ($secret !== '') {
            $expected = hash_hmac('sha256', $orderId.'|'.$paymentId, $secret);

            return hash_equals($expected, $signature);
        }

        // No secret configured (local dev): accept a well-formed payload.
        return true;
    }

    private function sdkAvailable(): bool
    {
        return class_exists(\Razorpay\Api\Api::class);
    }

    /**
     * @return \Razorpay\Api\Api
     */
    private function api(): object
    {
        $class = \Razorpay\Api\Api::class;

        return new $class(
            (string) config('services.razorpay.key_id'),
            (string) config('services.razorpay.key_secret')
        );
    }
}
