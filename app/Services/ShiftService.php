<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Shift;
use App\Models\User;
use App\Services\Exceptions\OrderPlacementException;
use Illuminate\Support\Facades\DB;

/**
 * Staff shifts / cash-drawer sessions (Business Plan: staff shifts).
 *
 * One open shift per staff member at a time. Opening cash is counted at
 * handover; expected cash = opening + cash payments settled during the
 * shift; closing variance = counted - expected (short/over for the owner).
 */
final class ShiftService
{
    /** @throws OrderPlacementException */
    public function open(User $actor, string $openingCash = '0.00', ?string $notes = null): Shift
    {
        if (! is_numeric($openingCash)) {
            $openingCash = '0.00';
        }
        $openingCash = number_format(max((float) $openingCash, 0), 2, '.', '');

        $existing = Shift::query()
            ->where('user_id', $actor->id)
            ->where('status', Shift::STATUS_OPEN)
            ->first();

        if ($existing !== null) {
            throw new OrderPlacementException('A shift is already open for '.$actor->name.'.', 422);
        }

        $shift = Shift::create([
            'store_id' => $actor->store_id,
            'user_id' => $actor->id,
            'opened_at' => now(),
            'opening_cash' => $openingCash,
            'notes' => $notes !== null ? substr(trim($notes), 0, 200) ?: null : null,
            'status' => Shift::STATUS_OPEN,
        ]);

        Audit::record(
            $actor,
            AuditLog::ACTION_STAFF_LOGIN,
            'Shift opened for '.$actor->name.' with Rs.'.number_format((float) $openingCash, 2).' in drawer.',
            entityType: 'user',
            entityId: $actor->id,
            entityName: $actor->name,
            new: ['opening_cash' => $openingCash],
            amount: $openingCash,
        );

        return $shift;
    }

    /** @throws OrderPlacementException */
    public function close(User $actor, Shift $shift, string $closingCash = '0.00'): Shift
    {
        if ((int) $shift->user_id !== (int) $actor->id && ! $actor->isAdmin()) {
            throw new OrderPlacementException('Only the shift owner or an admin can close this shift.', 403);
        }

        if ($shift->status !== Shift::STATUS_OPEN) {
            throw new OrderPlacementException('This shift is already closed.', 422);
        }

        if (! is_numeric($closingCash)) {
            $closingCash = '0.00';
        }
        $closingCash = number_format(max((float) $closingCash, 0), 2, '.', '');

        return DB::transaction(function () use ($actor, $shift, $closingCash): Shift {
            $cashSales = (string) Order::query()
                ->withoutGlobalScopes()
                ->where('store_id', $shift->store_id)
                ->where('user_id', $shift->user_id)
                ->whereBetween('created_at', [$shift->opened_at, now()])
                ->where('status', '!=', 'cancelled')
                ->where('payment_mode', 'cash')
                ->sum('total_amount');

            $expected = bcadd((string) $shift->opening_cash, $cashSales, 2);

            $shift->update([
                'closed_at' => now(),
                'closing_cash' => $closingCash,
                'expected_cash' => $expected,
                'status' => Shift::STATUS_CLOSED,
            ]);

            $variance = bcsub($closingCash, $expected, 2);

            Audit::record(
                $actor,
                AuditLog::ACTION_STAFF_LOGIN,
                'Shift closed for '.$shift->user->name.' — counted Rs.'.number_format((float) $closingCash, 2)
                    .', expected Rs.'.number_format((float) $expected, 2)
                    .' ('.(bccomp($variance, '0', 2) >= 0 ? '+' : '').$variance.').',
                entityType: 'user',
                entityId: $shift->user_id,
                entityName: $shift->user->name,
                old: ['status' => Shift::STATUS_OPEN],
                new: ['status' => Shift::STATUS_CLOSED, 'closing_cash' => $closingCash, 'expected_cash' => $expected],
                amount: $closingCash,
            );

            return $shift->fresh();
        });
    }

    /** Cash-register variance: counted minus expected (positive = over). */
    public function variance(Shift $shift): string
    {
        if ($shift->closing_cash === null || $shift->expected_cash === null) {
            return '0.00';
        }

        return bcsub((string) $shift->closing_cash, (string) $shift->expected_cash, 2);
    }
}
