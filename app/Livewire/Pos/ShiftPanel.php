<?php

namespace App\Livewire\Pos;

use App\Models\Order;
use App\Models\Shift;
use App\Services\Exceptions\OrderPlacementException;
use App\Services\ShiftService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * STAFF POS — shift / cash-drawer panel (Business Plan: staff shifts).
 *
 * The cashier opens a shift with a counted opening float, bills against it,
 * then closes with a counted closing float. Expected cash =
 * opening + cash sales in-window; variance is shown to the owner.
 */
#[Layout('layouts.app')]
#[Title('BizBite POS — Shift')]
final class ShiftPanel extends Component
{
    public string $openingCash = '';

    public string $closingCash = '';

    public string $notes = '';

    public ?string $error = null;

    public ?string $success = null;

    private ShiftService $shifts;

    public function boot(ShiftService $shifts): void
    {
        $this->shifts = $shifts;
    }

    public function mount(): void
    {
        $this->authorize('access-pos-portal');
    }

    #[Computed]
    public function openShift(): ?Shift
    {
        return Shift::query()
            ->where('user_id', Auth::id())
            ->where('status', Shift::STATUS_OPEN)
            ->latest('opened_at')
            ->first();
    }

    #[Computed]
    public function recentShifts()
    {
        return Shift::query()
            ->with('user:id,name')
            ->latest('opened_at')
            ->limit(15)
            ->get();
    }

    public function open(): void
    {
        $this->authorize('place-orders');
        $this->reset('error', 'success');

        try {
            $this->shifts->open(Auth::user(), $this->openingCash, $this->notes ?: null);
            $this->success = 'Shift opened.';
            $this->reset('openingCash', 'notes');
            unset($this->openShift, $this->recentShifts);
        } catch (OrderPlacementException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    public function close(int $shiftId): void
    {
        $this->authorize('place-orders');
        $this->reset('error', 'success');

        try {
            $shift = Shift::query()->findOrFail($shiftId);
            $this->shifts->close(Auth::user(), $shift, $this->closingCash);
            $this->success = 'Shift closed — variance Rs.'.$this->shifts->variance($shift->fresh()).'.';
            $this->reset('closingCash');
            unset($this->openShift, $this->recentShifts);
        } catch (OrderPlacementException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.pos.shift-panel');
    }
}
