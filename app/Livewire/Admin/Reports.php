<?php

namespace App\Livewire\Admin;

use App\Services\ReportService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * OWNER ADMIN — reports & analytics (must-add #4, Phase 3 analytics).
 *
 * The owner's nightly numbers: hourly sales for any day, best-sellers for
 * any range, date-range KPI rollup (revenue/bills/discounts/refunds/net),
 * plus one-click CSV export of the range's bills.
 */
#[Layout('layouts.app')]
final class Reports extends Component
{
    public string $date = '';

    public string $from = '';

    public string $to = '';

    public int $limit = 10;

    private ReportService $reports;

    public function boot(ReportService $reports): void
    {
        $this->reports = $reports;
    }

    public function mount(): void
    {
        $this->authorize('access-admin-portal');
        $today = now()->toDateString();
        $this->date = $today;
        $this->from = now()->subDays(6)->toDateString();
        $this->to = $today;
    }

    #[Computed]
    public function hourly(): array
    {
        return $this->reports->hourly($this->date ?: now()->toDateString());
    }

    #[Computed]
    public function bestSellers(): array
    {
        return $this->reports->bestSellers($this->from, $this->to, max($this->limit, 1));
    }

    #[Computed]
    public function rangeStats(): array
    {
        return $this->reports->range($this->from, $this->to);
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('access-admin-portal');

        $from = $this->from ?: now()->toDateString();
        $to = $this->to ?: now()->toDateString();

        $orders = \App\Models\Order::query()
            ->with('user:id,name')
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->orderBy('created_at')
            ->get(['id', 'order_number', 'created_at', 'user_id', 'order_type', 'payment_mode', 'status', 'subtotal', 'discount_amount', 'tax_amount', 'total_amount', 'refunded_amount']);

        $filename = 'bizbite-sales-'.$from.'-to-'.$to.'.csv';

        return response()->streamDownload(function () use ($orders): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['bill', 'date', 'cashier', 'type', 'payment', 'status', 'subtotal', 'discount', 'tax', 'total', 'refunded']);
            foreach ($orders as $order) {
                fputcsv($out, [
                    $order->order_number,
                    $order->created_at?->format('Y-m-d H:i'),
                    $order->user?->name,
                    $order->order_type->value,
                    $order->payment_mode->value,
                    $order->status->value,
                    $order->subtotal,
                    $order->discount_amount,
                    $order->tax_amount,
                    $order->total_amount,
                    $order->refunded_amount,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        return view('livewire.admin.reports');
    }
}
