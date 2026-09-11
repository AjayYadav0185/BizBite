<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

/**
 * Customer Wallet endpoints (Flutter Wallet Dashboard).
 *
 *   GET /api/wallet/balance → current points + recent ledger history.
 */
class WalletController extends Controller
{
    /**
     * Current wallet balance + recent transaction history (newest first).
     */
    public function balance(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user()->fresh();

        $transactions = WalletTransaction::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (WalletTransaction $tx): array => [
                'id' => $tx->id,
                'amount' => (string) $tx->amount,
                'type' => $tx->type,
                'description' => $tx->description,
                'reference_id' => $tx->reference_id,
                'balance_after' => $tx->balance_after !== null ? (string) $tx->balance_after : null,
                'created_at' => $tx->created_at?->toISOString(),
            ])
            ->all();

        return Response::json([
            'wallet_balance' => number_format((float) ($user->wallet_balance ?? 0), 2, '.', ''),
            'currency' => 'INR',
            'points_unit' => '1 point = ₹1',
            'transactions' => $transactions,
        ]);
    }
}
