<?php

namespace App\Livewire\Admin;

use App\Models\DiningTable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * OWNER ADMIN — dining tables (Phase 3: table management).
 *
 * The owner defines tables (number + seats); the POS marks a table occupied
 * when a dine-in bill carries its number and frees it on completion/cancel.
 * Reserved is a manual hold for phone bookings.
 */
#[Layout('layouts.app')]
final class TableManager extends Component
{
    public string $tableNumber = '';

    public int $seats = 4;

    public ?string $error = null;

    public ?string $success = null;

    public function mount(): void
    {
        $this->authorize('manage-menu');
    }

    #[Computed]
    public function tables()
    {
        return DiningTable::query()->orderBy('table_number')->get();
    }

    public function create(): void
    {
        $this->authorize('manage-menu');
        $this->reset('error', 'success');

        $number = substr(trim($this->tableNumber), 0, 20);

        if ($number === '') {
            $this->error = 'Table number is required.';

            return;
        }

        $exists = DiningTable::query()->where('table_number', $number)->exists();

        if ($exists) {
            $this->error = 'Table '.$number.' already exists.';

            return;
        }

        DiningTable::create([
            'store_id' => auth()->user()->store_id,
            'table_number' => $number,
            'seats' => max($this->seats, 1),
            'status' => DiningTable::STATUS_AVAILABLE,
        ]);

        $this->success = 'Table '.$number.' added.';
        $this->reset('tableNumber');
        $this->seats = 4;
        unset($this->tables);
    }

    public function setStatus(int $id, string $status): void
    {
        $this->authorize('manage-menu');

        if (! in_array($status, [DiningTable::STATUS_AVAILABLE, DiningTable::STATUS_OCCUPIED, DiningTable::STATUS_RESERVED], strict: true)) {
            return;
        }

        $table = DiningTable::query()->findOrFail($id);
        $table->update([
            'status' => $status,
            'current_order_id' => $status === DiningTable::STATUS_AVAILABLE ? null : $table->current_order_id,
        ]);
        unset($this->tables);
    }

    public function delete(int $id): void
    {
        $this->authorize('manage-menu');
        DiningTable::query()->findOrFail($id)->delete();
        unset($this->tables);
    }

    public function render()
    {
        return view('livewire.admin.table-manager');
    }
}
