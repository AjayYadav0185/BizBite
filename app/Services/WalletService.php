<?php

namespace App\Services;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Customer Wallet domain service — single place for every points movement.
 *
 * Business rules (spec):
 *   - Sign-up bonus: every new user starts with 200 points.
 *   - Bill deduction: 1% of the settled bill total is debited (1 pt = ₹1).
 *   - Recharge: Razorpay payment of ₹N credits N points after verification.
 *
 * All mutations run inside a DB transaction with a row lock on the user so
 * concurrent bills / recharges can never corrupt the balance.
 */
final class WalletService
{
    public const SIGNUP_BONUS = '200.00';

    public const BILL_DEDUCTION_RATE = '0.01';

    /**
     * Seed a fresh user's wallet: balance 200 + a 'Sign-up bonus' ledger row.
     * Idempotent — skips users that already have a bonus row.
     */
    public function grantSignupBonus(User $user): void
    {
        DB::transaction(function () use ($user) {
            $locked = User::query()->withoutGlobalScopes()->lockForUpdate()->find($user->id);
            if ($locked === null) {
                return;
            }

            $already = WalletTransaction::query()
                ->where('user_id', $locked->id)
                ->where('description', WalletTransaction::DESC_SIGNUP_BONUS)
                ->exists();
            if ($already) {
                return;
            }

            $locked->forceFill(['wallet_balance' => self::SIGNUP_BONUS])->save();

            WalletTransaction::create([
                'user_id' => $locked->id,
                'amount' => self::SIGNUP_BONUS,
                'type' => WalletTransaction::TYPE_CREDIT,
                'description' => WalletTransaction::DESC_SIGNUP_BONUS,
                'reference_id' => 'signup:'.$locked->id,
                'balance_after' => self::SIGNUP_BONUS,
            ]);
        });
    }

    /**
     * Debit 1% of a settled bill from the user's wallet.
     *
     * Must be called from INSIDE OrderService's transaction (after the order
     * row exists) so bill + wallet stay atomic. Caps the debit at the
     * available balance so the wallet can never go negative.
     *
     * @return string debited points, 2dp (e.g. '1.00' for a ₹100 bill).
     */
    public function deductForBill(User $user, string $billTotal, int|string $billId): string
    {
        $locked = User::query()->withoutGlobalScopes()->lockForUpdate()->find($user->id);
        if ($locked === null) {
            return '0.00';
        }

        // 1% of bill, rounded to 2dp (100 -> 1.00, 900 -> 9.00).
        $deduction = number_format(round(((float) $billTotal) * 0.01, 2), 2, '.', '');
        $available = number_format((float) ($locked->wallet_balance ?? 0), 2, '.', '');

        if (bccomp($deduction, '0', 2) <= 0) {
            return '0.00';
        }
        // Never drive the balance negative — cap at what the wallet holds.
        if (bccomp($deduction, $available, 2) > 0) {
            $deduction = $available;
        }
        if (bccomp($deduction, '0', 2) <= 0) {
            return '0.00';
        }

        $after = bcsub($available, $deduction, 2);
        $locked->forceFill(['wallet_balance' => $after])->save();

        WalletTransaction::create([
            'user_id' => $locked->id,
            'amount' => '-'.$deduction,
            'type' => WalletTransaction::TYPE_DEBIT,
            'description' => WalletTransaction::DESC_BILL_DEDUCTION,
            'reference_id' => (string) $billId,
            'balance_after' => $after,
        ]);

        return $deduction;
    }

    /**
     * Credit a verified Razorpay recharge (₹N paid -> N points).
     * Idempotent on razorpay_payment_id: a replay returns the same balance.
     *
     * @return string new balance, 2dp.
     */
    public function creditRecharge(User $user, string $amount, string $paymentId, ?string $orderId = null): string
    {
        return DB::transaction(function () use ($user, $amount, $paymentId, $orderId) {
            $locked = User::query()->withoutGlobalScopes()->lockForUpdate()->find($user->id);
            \abort_if($locked === null, 404);

            $existing = WalletTransaction::query()
                ->where('user_id', $locked->id)
                ->where('description', WalletTransaction::DESC_RECHARGE)
                ->where('reference_id', $paymentId)
                ->first();
            if ($existing !== null) {
                return (string) $locked->wallet_balance;
            }

            $credit = number_format(max((float) $amount, 0), 2, '.', '');
            $after = bcadd(number_format((float) ($locked->wallet_balance ?? 0), 2, '.', ''), $credit, 2);
            $locked->forceFill(['wallet_balance' => $after])->save();

            WalletTransaction::create([
                'user_id' => $locked->id,
                'amount' => $credit,
                'type' => WalletTransaction::TYPE_CREDIT,
                'description' => WalletTransaction::DESC_RECHARGE,
                'reference_id' => $paymentId,
                'razorpay_order_id' => $orderId,
                'balance_after' => $after,
            ]);

            return $after;
        });
    }
}
