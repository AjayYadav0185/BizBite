<?php

namespace App\Livewire\Admin;

use App\Models\Campaign;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * OWNER ADMIN — discount / loyalty campaigns (Phase 3).
 *
 * Percent or flat codes with a min-order gate + active window. The POS sends
 * the code; OrderService resolves it server side and snapshots the rupee
 * discount on the bill so reports stay exact after edits.
 */
#[Layout('layouts.app')]
final class CampaignManager extends Component
{
    public string $name = '';

    public string $code = '';

    public string $type = 'percent';

    public string $value = '';

    public string $minOrder = '0';

    public ?string $error = null;

    public ?string $success = null;

    public function mount(): void
    {
        $this->authorize('manage-menu');
    }

    #[Computed]
    public function campaigns()
    {
        return Campaign::query()->orderByDesc('created_at')->limit(50)->get();
    }

    public function create(): void
    {
        $this->authorize('manage-menu');
        $this->reset('error', 'success');

        $name = substr(trim($this->name), 0, 120);
        $code = strtoupper(substr(trim($this->code), 0, 40));

        if ($name === '' || $code === '' || ! is_numeric($this->value) || (float) $this->value <= 0) {
            $this->error = 'Name, code and a positive value are required.';

            return;
        }

        if (! in_array($this->type, [Campaign::TYPE_PERCENT, Campaign::TYPE_FLAT], strict: true)) {
            $this->error = 'Unknown campaign type.';

            return;
        }

        if (Campaign::query()->where('code', $code)->exists()) {
            $this->error = 'Code '.$code.' already exists.';

            return;
        }

        Campaign::create([
            'store_id' => auth()->user()->store_id,
            'name' => $name,
            'code' => $code,
            'type' => $this->type,
            'value' => number_format((float) $this->value, 2, '.', ''),
            'min_order_amount' => is_numeric($this->minOrder) ? number_format(max((float) $this->minOrder, 0), 2, '.', '') : '0.00',
            'is_active' => true,
        ]);

        $this->success = 'Campaign '.$code.' created.';
        $this->reset('name', 'code', 'value', 'minOrder');
        $this->type = 'percent';
        unset($this->campaigns);
    }

    public function toggle(int $id): void
    {
        $this->authorize('manage-menu');
        $campaign = Campaign::query()->findOrFail($id);
        $campaign->update(['is_active' => ! $campaign->is_active]);
        unset($this->campaigns);
    }

    public function delete(int $id): void
    {
        $this->authorize('manage-menu');
        Campaign::query()->findOrFail($id)->delete();
        unset($this->campaigns);
    }

    public function render()
    {
        return view('livewire.admin.campaign-manager');
    }
}
