<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Enums\PaymentMode;
use App\Models\Order;
use App\Models\Refund;
use App\Models\User;
use App\Services\Exceptions\OrderPlacementException;
use Illuminate\Support\Facades\DB;

/**
 * Partial / post-completion refunds (must-add #3: beyond cancel-void).
 *
 * Cancel-void already exists in OrderService::updateStatus(). This service
 * records money returned WITHOUT voiding the whole bill: the refunded total
 * can never exceed the bill total, a reason is mandatory, and every refund
 * writes a Refund ledger row + a negative Payment leg + an owner-visible
 * audit row, all atomically.
 */
final class RefundService
{
    /**
     * @throws OrderPlacementException
     */
    public function issue(User $actor, Order $order, string $amount, string $reason, string $mode = 'cash'): Order
    {
        $reason = substr(trim($reason), 0, 200);
        $mode = strtolower(substr(trim($mode), 0, 20));

        if ($reason === '') {
            throw new OrderPlacementException('A refund reason is required.', 422);
        }

        if (! is_numeric($amount) || bccomp(number_format((float) $amount, 2, '.', ''), '0', 2) <= 0) {
            throw new OrderPlacementException('Refund amount must be greater than zero.', 422);
        }

        $amount = number_format((float) $amount, 2, '.', '');

        if (! in_array($mode, array_column(PaymentMode::cases(), 'value'), strict: true)) {
            $mode = PaymentMode::Cash->value;
        }

        return DB::transaction(function () use ($actor, $order, $amount, $reason, $mode): Order {
            $fresh = $order->fresh(['refunds']) ?? $order;
            $already = (string) ($fresh->refunded_amount ?? '0.00');
            $remaining = bcsub((string) $fresh->total_amount, $already, 2);

            if (bccomp($amount, $remaining, 2) > 0) {
                throw new OrderPlacementException(
                    'Refund of Rs.'.$amount.' exceeds refundable Rs.'.$remaining.' on bill '.$fresh->order_number.'.',
                    422
                );
            }

            Refund::create([
                'store_id' => $fresh->store_id,
                'order_id' => $fresh->id,
                'user_id' => $actor->id,
                'amount' => $amount,
                'mode' => $mode,
                'reason' => $reason,
            ]);

            $newTotal = bcadd($already, $amount, 2);
            $fresh->update(['refunded_amount' => $newTotal, 'refund_reason' => $reason]);
            $fresh->payments()->create([
                'store_id' => $fresh->store_id,
                'mode' => $mode,
                'amount' => '-'.$amount,
                'status' => 'refunded',
                'notes' => 'Refund: '.$reason,
                'paid_at' => now(),
            ]);

            Audit::record(
                $actor,
                AuditLog::ACTION_ORDER_REFUND,
                'Refund Rs.'.number_format((float) $amount, 2).' on bill '.$fresh->order_number.' ('.$reason.').',
                entityType: 'order',
                entityId: $fresh->id,
                entityName: $fresh->order_number,
                old: ['refunded_amount' => $already],
                new: ['refunded_amount' => $newTotal, 'reason' => $reason, 'mode' => $mode],
                amount: $amount,
            );

            return $fresh->fresh(['items', 'refunds', 'user:id,name']);
        });
    }
}
