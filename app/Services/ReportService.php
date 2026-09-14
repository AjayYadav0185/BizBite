<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Order;
use App\Services\Exceptions\OrderPlacementException;

/**
 * Phase 3: loyalty / discount campaigns + expanded owner reporting.
 *
 * Campaigns live in tbl_pos_campaigns (percent|flat, min-order gate, active
 * window). Bills snapshot the applied code + rupee discount so reports stay
 * exact even if the campaign is edited later. Reporting queries below back
 * the owner "night numbers": hourly sales, best-sellers, date-range + CSV.
 */
final class ReportService
{
    /** Base settled-bill query (voids excluded), tenant scoped. */
    private function settled()
    {
        return Order::query()->where('status', '!=', 'cancelled');
    }

    /** Hourly revenue + bill count for one calendar day (Y-m-d). */
    public function hourly(string $date): array
    {
        // Group in PHP (not strftime/HOUR()) so the query works identically
        // on SQLite (tests/local) and MySQL (production).
        $rows = $this->settled()
            ->whereDate('created_at', $date)
            ->get(['created_at', 'total_amount'])
            ->groupBy(fn ($order): string => $order->created_at?->format('H') ?? '00');

        $out = [];
        for ($h = 0; $h < 24; $h++) {
            $key = str_pad((string) $h, 2, '0', STR_PAD_LEFT);
            $group = $rows->get($key, collect());
            $out[] = [
                'hour' => $key.':00',
                'bills' => $group->count(),
                'revenue' => number_format((float) $group->sum('total_amount'), 2, '.', ''),
            ];
        }

        return $out;
    }

    /** Best-selling items by quantity (and revenue) for a date range. */
    public function bestSellers(string $from, string $to, int $limit = 10): array
    {
        $orderIds = $this->settled()
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->pluck('id');

        if ($orderIds->isEmpty()) {
            return [];
        }

        return \App\Models\OrderItem::query()
            ->withoutGlobalScopes()
            ->whereIn('order_id', $orderIds)
            ->selectRaw('food_item_name, SUM(quantity) as qty, SUM(subtotal) as revenue')
            ->groupBy('food_item_name')
            ->orderByDesc('qty')
            ->limit(max($limit, 1))
            ->get()
            ->map(fn ($row): array => [
                'name' => $row->food_item_name,
                'quantity' => (int) $row->qty,
                'revenue' => number_format((float) $row->revenue, 2, '.', ''),
            ])
            ->all();
    }

    /** Date-range KPI rollup: revenue, bills, discounts, refunds, avg bill. */
    public function range(string $from, string $to): array
    {
        $base = $this->settled()->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);

        $bills = (clone $base)->count();
        $revenue = (string) (clone $base)->sum('total_amount');
        $discounts = (string) (clone $base)->sum('discount_amount');
        $refunds = (string) (clone $base)->sum('refunded_amount');

        return [
            'from' => $from,
            'to' => $to,
            'bills' => $bills,
            'revenue' => number_format((float) $revenue, 2, '.', ''),
            'discounts' => number_format((float) $discounts, 2, '.', ''),
            'refunds' => number_format((float) $refunds, 2, '.', ''),
            'net' => bcsub(number_format((float) $revenue, 2, '.', ''), number_format((float) $refunds, 2, '.', ''), 2),
            'average_bill' => $bills > 0 ? bcdiv(number_format((float) $revenue, 2, '.', ''), (string) $bills, 2) : '0.00',
        ];
    }

    /** Resolve a live campaign code or throw (server-side validation). */
    public function resolveCode(string $code, string $subtotal): Campaign
    {
        $campaign = Campaign::query()->where('code', strtoupper(trim($code)))->first();

        if ($campaign === null || ! $campaign->isLive()) {
            throw new OrderPlacementException('Campaign code is invalid or expired.', 422);
        }

        if (bccomp($campaign->discountFor($subtotal), '0', 2) <= 0) {
            throw new OrderPlacementException('This campaign does not apply to the current bill.', 422);
        }

        return $campaign;
    }
}
