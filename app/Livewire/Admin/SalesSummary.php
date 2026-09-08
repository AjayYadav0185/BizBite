<?php

namespace App\Livewire\Admin;

use App\Models\Enums\PaymentMode;
use App\Models\Order;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * OWNER ADMIN PORTAL — Sales Summary telemetry.
 *
 * Computes real-time KPIs strictly scoped to the logged-in admin's store:
 * today's total revenue, total bills, per-payment-mode mix and the latest
 * bills. Every query runs on the StoreScope-decorated Order model, so the
 * global `store_id` tenant scope is applied automatically — an admin can
 * only ever read their own store's numbers, by construction.
 */
#[Layout('layouts.app')]
final class SalesSummary extends Component
{
    /** Number of recent bills listed beneath the KPI tiles. */
    #[Locked]
    public int $recentLimit = 10;

    #[Computed]
    public function todaysStats(): array
    {
        $base = Order::query()
            ->where('status', 'completed')
            ->whereDate('created_at', now()->toDateString());

        $billsCount = (clone $base)->count();
        $totalRevenue = (string) (clone $base)->sum('total_amount');

        $breakdown = (clone $base)
            ->selectRaw('payment_mode, COUNT(*) as bills, SUM(total_amount) as revenue')
            ->groupBy('payment_mode')
            ->get();

        $modes = [];
        foreach (PaymentMode::cases() as $mode) {
            $row = $breakdown->firstWhere('payment_mode', $mode->value);

            $modes[$mode->value] = [
                'label' => $mode->value,
                'bills' => (int) ($row->bills ?? 0),
                'revenue' => (string) ($row->revenue ?? '0'),
                // Payment type percentage of today's bill count.
                'percentage' => $billsCount > 0
                    ? round(((int) ($row->bills ?? 0)) / $billsCount * 100, 1)
                    : 0.0,
            ];
        }

        return [
            'revenue' => $totalRevenue,
            'bills' => $billsCount,
            'average_bill' => $billsCount > 0
                ? bcdiv($totalRevenue, (string) $billsCount, 2)
                : '0',
            'modes' => $modes,
        ];
    }

    #[Computed]
    public function recentBills(): Collection
    {
        return Order::query()
            ->with('user:id,name')
            ->latest('created_at')
            ->limit($this->recentLimit)
            ->get(['id', 'order_number', 'total_amount', 'payment_mode', 'user_id', 'created_at']);
    }

    public function render()
    {
        return view('livewire.admin.sales-summary');
    }
}
