<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WalletRecharge;
use App\Services\RazorpayService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

/**
 * Wallet recharge via Razorpay (Flutter `razorpay_flutter` flow).
 *
 *   POST /api/wallet/recharge/initiate → { amount } → Razorpay order id.
 *   POST /api/wallet/recharge/verify   → { razorpay_* } → signature check,
 *                                        then credit wallet (₹N → N points).
 */
class WalletRechargeController extends Controller
{
    public function __construct(
        private readonly RazorpayService $razorpay,
        private readonly WalletService $wallet,
    ) {}

    /**
     * Create a Razorpay order for the requested recharge amount.
     *
     * Body: { "amount": 100 } (rupees; credited 1:1 as points on verify).
     */
    public function initiate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:100000'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $amount = number_format((float) $data['amount'], 2, '.', '');
        $receipt = 'wl_'.$user->id.'_'.now()->format('YmdHis').'_'.substr(uniqid(), -6);

        $order = $this->razorpay->createOrder($amount, $receipt);

        WalletRecharge::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'razorpay_order_id' => $order['order_id'],
            'status' => 'created',
        ]);

        return Response::json([
            'message' => 'Razorpay order created.',
            'razorpay_order_id' => $order['order_id'],
            'amount' => $amount,
            'currency' => $order['currency'] ?? 'INR',
            'key_id' => $order['key_id'],
        ], status: 201);
    }

    /**
     * Verify the Razorpay signature server-side and credit the wallet.
     *
     * Body: { "razorpay_payment_id", "razorpay_order_id", "razorpay_signature" }
     */
    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'razorpay_payment_id' => ['required', 'string', 'max:100'],
            'razorpay_order_id' => ['required', 'string', 'max:100'],
            'razorpay_signature' => ['required', 'string', 'max:255'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $intent = WalletRecharge::query()
            ->where('razorpay_order_id', $data['razorpay_order_id'])
            ->where('user_id', $user->id)
            ->first();

        if ($intent === null) {
            return Response::json([
                'message' => 'Recharge order not found. Please initiate again.',
            ], status: 404);
        }

        if ($intent->status === 'verified') {
            return Response::json([
                'message' => 'Recharge already credited.',
                'wallet_balance' => number_format((float) ($user->fresh()->wallet_balance ?? 0), 2, '.', ''),
            ]);
        }

        $valid = $this->razorpay->verifySignature(
            $data['razorpay_order_id'],
            $data['razorpay_payment_id'],
            $data['razorpay_signature'],
        );

        if (! $valid) {
            $intent->forceFill([
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'razorpay_signature' => $data['razorpay_signature'],
                'status' => 'failed',
            ])->save();

            return Response::json([
                'message' => 'Payment signature verification failed.',
            ], status: 422);
        }

        $balance = $this->wallet->creditRecharge(
            $user,
            (string) $intent->amount,
            $data['razorpay_payment_id'],
            $data['razorpay_order_id'],
        );

        $intent->forceFill([
            'razorpay_payment_id' => $data['razorpay_payment_id'],
            'razorpay_signature' => $data['razorpay_signature'],
            'status' => 'verified',
        ])->save();

        return Response::json([
            'message' => 'Wallet recharged successfully.',
            'credited_points' => number_format((float) $intent->amount, 2, '.', ''),
            'wallet_balance' => number_format((float) $balance, 2, '.', ''),
        ]);
    }
}
