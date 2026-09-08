<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * OWNER ADMIN PORTAL — Receipt Customizer.
 *
 * The two text inputs bind directly (wire:model.live) to the store's thermal
 * receipt template lines: `print_header` and `print_footer`. A live preview
 * re-renders on every keystroke so the owner sees exactly what their
 * cashiers will print on the 80mm roll before saving.
 *
 * The Store model is intentionally NOT tenant-scoped (it has no StoreScope);
 * it is resolved through the authenticated admin's `user.store` relation,
 * which makes cross-tenant writes impossible by construction.
 */
#[Layout('layouts.app')]
final class ReceiptCustomizer extends Component
{
    /** Bound to stores.print_header (top of the thermal receipt). */
    #[Validate('nullable|string|max:120')]
    public string $printHeader = '';

    /** Bound to stores.print_footer (bottom of the thermal receipt). */
    #[Validate('nullable|string|max:120')]
    public string $printFooter = '';

    public bool $saved = false;

    public function mount(): void
    {
        $this->authorize('access-admin-portal');

        $store = $this->store();

        $this->printHeader = (string) $store->print_header;
        $this->printFooter = (string) $store->print_footer;
    }

    /**
     * The authenticated admin's own store.
     */
    private function store()
    {
        return auth()->user()->store;
    }

    public function updated(): void
    {
        $this->saved = false;
    }

    public function save(): void
    {
        $this->authorize('access-admin-portal');

        $validated = $this->validate([
            'printHeader' => ['nullable', 'string', 'max:120'],
            'printFooter' => ['nullable', 'string', 'max:120'],
        ]);

        $this->store()->update([
            'print_header' => trim($validated['printHeader']) ?: null,
            'print_footer' => trim($validated['printFooter']) ?: null,
        ]);

        $this->saved = true;
    }

    public function render()
    {
        return view('livewire.admin.receipt-customizer', [
            'store' => $this->store(),
        ]);
    }
}
