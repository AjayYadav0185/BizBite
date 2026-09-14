<?php

namespace App\Livewire\Pos;

use App\Models\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Exceptions\OrderPlacementException;
use App\Services\OrderService;
use App\Services\RefundService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Order-queue refund + delivery side-panel (must-add #3, Phase 3 delivery).
 *
 * Lives inside the queue board: partial refunds with reason on any bill,
 * and delivery-status moves (assigned → out → delivered/failed) on delivery
 * bills. All writes go through the shared services (audited, tenant scoped).
 */
final class QueueActions extends Component
{
    public int $orderId = 0;

    public string $refundAmount = '';

    public string $refundReason = '';

    public string $refundMode = 'cash';

    public string $deliveryStatus = '';

    public string $deliveryAgent = '';

    public ?string $error = null;

    public ?string $success = null;

    private OrderService $orders;

    private RefundService $refunds;

    public function boot(OrderService $orders, RefundService $refunds): void
    {
        $this->orders = $orders;
        $this->refunds = $refunds;
    }

    public function mount(int $orderId): void
    {
        $this->authorize('access-pos-portal');
        $this->orderId = $orderId;
    }

    public function issueRefund(): void
    {
        $this->authorize('place-orders');
        $this->reset('error', 'success');

        try {
            $order = Order::query()->findOrFail($this->orderId);
            $this->refunds->issue(Auth::user(), $order, $this->refundAmount, $this->refundReason, $this->refundMode);
            $this->success = 'Refund issued.';
            $this->reset('refundAmount', 'refundReason');
        } catch (OrderPlacementException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    public function moveDelivery(): void
    {
        $this->authorize('place-orders');
        $this->reset('error', 'success');

        try {
            $order = Order::query()->findOrFail($this->orderId);
            $this->orders->updateDelivery(
                Auth::user(), $order, $this->deliveryStatus,
                $this->deliveryAgent ?: null
            );
            $this->success = 'Delivery marked '.$this->deliveryStatus.'.';
        } catch (OrderPlacementException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    public function render()
    {
        $order = Order::query()->with('refunds')->find($this->orderId);

        return view('livewire.pos.queue-actions', ['order' => $order]);
    }
}
