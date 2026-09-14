<?php

namespace App\Livewire\Pos;

use App\Models\Enums\OrderStatus;
use App\Models\Enums\OrderType;
use App\Models\Order;
use App\Services\Exceptions\OrderPlacementException;
use App\Services\OrderService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * STAFF POS PORTAL — Kitchen / Counter order queue.
 *
 * Priority feature §5 (order status updates) + §4.4/§4.5 (order status flow and
 * kitchen / counter communication).
 *
 * A live board of today's bills. Billing happens on the BillingDashboard; this
 * screen is the fulfilment side: counter/kitchen staff advance each bill
 * through `pending → preparing → ready → completed`, or void it. Dine-in and
 * takeaway can be separated with the order-type filter so the kitchen doesn't
 * mix a parcel with a table order.
 *
 * The board is `wire:poll`-ed so a second screen (kitchen tablet) updates
 * without anyone refreshing, and every transition goes through the shared
 * {@see OrderService::updateStatus()} — the same code path the mobile API uses.
 */
#[Layout('layouts.app')]
#[Title('BizBite POS — Order Queue')]
final class OrderQueue extends Component
{
    /** 'all' or an OrderType value — the dine-in / takeaway split (§4.5). */
    public string $typeFilter = 'all';

    /** 'open' = live kitchen floor only, 'all' = today's full bill list. */
    public string $statusFilter = 'open';

    /** Staff-facing error banner (invalid transition, etc.). */
    public ?string $error = null;

    /** Shared fulfilment service (also used by the Sanctum API). */
    private OrderService $orderService;

    /**
     * Re-bound on every request (Livewire 3 only runs mount() once), exactly
     * like BillingDashboard::boot().
     */
    public function boot(OrderService $orders): void
    {
        $this->orderService = $orders;
    }

    public function mount(): void
    {
        $this->authorize('access-pos-portal');
    }

    // ---------------------------------------------------------------------
    // Board data (tenant scoped automatically via StoreScope)
    // ---------------------------------------------------------------------

    #[Computed]
    public function boardOrders(): Collection
    {
        return Order::query()
            ->with(['items', 'user:id,name'])
            ->whereDate('created_at', now()->toDateString())
            ->when($this->statusFilter === 'open', fn ($query) => $query->whereIn('status', [
                OrderStatus::Pending->value,
                OrderStatus::Preparing->value,
                OrderStatus::Ready->value,
            ]))
            ->when($this->typeFilter !== 'all', fn ($query) => $query->where('order_type', $this->typeFilter))
            ->oldest('created_at')
            ->limit(80)
            ->get();
    }

    /** Per-status counters for the tiles (today's bills). */
    #[Computed]
    public function statusCounts(): array
    {
        $base = Order::query()->whereDate('created_at', now()->toDateString());

        $counts = [];

        foreach (OrderStatus::cases() as $status) {
            $counts[$status->value] = (clone $base)->where('status', $status->value)->count();
        }

        return $counts;
    }

    // ---------------------------------------------------------------------
    // Filters
    // ---------------------------------------------------------------------

    public function setTypeFilter(string $type): void
    {
        $allowed = ['all', ...array_column(OrderType::cases(), 'value')];

        if (in_array($type, $allowed, strict: true)) {
            $this->typeFilter = $type;
            $this->error = null;
        }
    }

    public function setStatusFilter(string $filter): void
    {
        if (in_array($filter, ['open', 'all'], strict: true)) {
            $this->statusFilter = $filter;
            $this->error = null;
        }
    }

    // ---------------------------------------------------------------------
    // Status transitions (delegated to the shared OrderService)
    // ---------------------------------------------------------------------

    /** Advance a bill one step (pending → preparing → ready → completed). */
    public function advance(int $orderId): void
    {
        $order = Order::query()->findOrFail($orderId);
        $next = $order->status->next();

        if ($next === null) {
            $this->error = 'Bill '.$order->order_number.' is already '.$order->status->label().'.';

            return;
        }

        $this->transition($order, $next);
    }

    /** Jump straight to an explicit status (skip-ahead or void). */
    public function setStatus(int $orderId, string $status): void
    {
        $target = OrderStatus::tryFrom($status);

        if ($target === null) {
            $this->error = 'Unknown order status.';

            return;
        }

        $this->transition(Order::query()->findOrFail($orderId), $target);
    }

    public function cancelOrder(int $orderId): void
    {
        $this->transition(Order::query()->findOrFail($orderId), OrderStatus::Cancelled);
    }

    /**
     * Run one transition and surface any rejection (e.g. an illegal move) as a
     * friendly banner instead of crashing the board.
     */
    private function transition(Order $order, OrderStatus $status): void
    {
        $this->authorize('place-orders');

        $this->error = null;

        try {
            $this->orderService->updateStatus(Auth::user(), $order, $status);
        } catch (OrderPlacementException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.pos.order-queue');
    }
}
