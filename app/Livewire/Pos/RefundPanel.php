<?php

namespace App\Livewire\Pos;

use App\Models\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Exceptions\OrderPlacementException;
use App\Services\RefundService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * POS counter refunds (must-add #3: refunds beyond cancel-void).
 *
 * Cancel-void stays on the queue board (full bill, stock restored). This
 * small panel issues PARTIAL refunds with a mandatory reason: the refunded
 * total can never exceed the bill total, and every refund writes a ledger
 * row + an owner-visible audit row via RefundService.
 */
final class RefundPanel extends Component
{
    public int $orderId = 0;

    public string $amount = '';

    public string $reason = '';

    public string $mode = 'cash';

    public ?string $error = null;

    public ?string $success = null;

    private RefundService $refunds;

    public function boot(RefundService $refunds): void
    {
        $this->refunds = $refunds;
    }

    public function mount(int $orderId = 0): void
    {
        $this->authorize('place-orders');
        $this->orderId = $orderId;
    }

    public function issueRefund(): void
    {
        $this->authorize('place-orders');
        $this->reset('error', 'success');

        if ($this->orderId <= 0) {
            $this->error = 'Select a bill first.';

            return;
        }

        try {
            $order = Order::query()->findOrFail($this->orderId);
            $order = $this->refunds->issue(Auth::user(), $order, $this->amount, $this->reason, $this->mode);
            $this->success = 'Refund Rs.'.number_format((float) $this->amount, 2).' issued on '.$order->order_number.'.';
            $this->reset('amount', 'reason');
        } catch (OrderPlacementException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    public function render()
    {
        $order = $this->orderId > 0 ? Order::query()->with('refunds')->find($this->orderId) : null;

        return view('livewire.pos.refund-panel', ['order' => $order]);
    }
}
