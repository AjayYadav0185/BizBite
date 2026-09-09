<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * OWNER ADMIN PORTAL — Audit Log viewer (owner eyes only).
 *
 * Shows WHO changed WHAT and WHEN: price updates, menu edits, discounts,
 * credit bills, cancellations, settings changes and staff activity.
 * Tenant-scoped by StoreScope + double-guarded by the `view-audit-logs`
 * gate in mount(). There is intentionally NO route/API exposing this to
 * cashiers — a cashier hitting /admin?tab=logs gets a 403.
 */
#[Layout('layouts.app')]
final class AuditLogViewer extends Component
{
    use WithPagination;

    /** Free-text search across description / user / entity names. */
    #[Url(history: true)]
    public string $search = '';

    /** Action filter (empty = all). */
    #[Url(history: true)]
    public string $action = '';

    /** Staff filter (user id, empty = all). */
    #[Url(history: true)]
    public string $staffId = '';

    public function mount(): void
    {
        $this->authorize('view-audit-logs');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedAction(): void
    {
        $this->resetPage();
    }

    public function updatedStaffId(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'action', 'staffId');
        $this->resetPage();
    }

    /** Staff list for the filter dropdown (own store only). */
    #[Computed]
    public function staffList(): Collection
    {
        return \App\Models\User::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function actionOptions(): array
    {
        return [
            AuditLog::ACTION_PRICE_UPDATED => 'Price updated',
            AuditLog::ACTION_ITEM_CREATED => 'Item created/updated',
            AuditLog::ACTION_ITEM_DELETED => 'Item deleted',
            AuditLog::ACTION_ITEM_AVAILABILITY => 'Availability toggled',
            AuditLog::ACTION_CATEGORY_CREATED => 'Category created',
            AuditLog::ACTION_CATEGORY_UPDATED => 'Category updated',
            AuditLog::ACTION_CATEGORY_DELETED => 'Category deleted',
            AuditLog::ACTION_ORDER_DISCOUNT => 'Discount given',
            AuditLog::ACTION_ORDER_CANCELLED => 'Bill cancelled',
            AuditLog::ACTION_CREDIT_BILL => 'Credit (udhaar) bill',
            AuditLog::ACTION_PAYMENT_SETTLED => 'Payment settled',
            AuditLog::ACTION_STORE_SETTINGS => 'Settings changed',
            AuditLog::ACTION_STAFF_LOGIN => 'Staff login',
            AuditLog::ACTION_STAFF_CREATED => 'Staff added',
            AuditLog::ACTION_STAFF_DEACTIVATED => 'Staff deactivated',
        ];
    }

    public function render()
    {
        $this->authorize('view-audit-logs');

        $logs = AuditLog::query()
            ->with('user:id,name')
            ->when($this->action !== '', fn ($q) => $q->where('action', $this->action))
            ->when($this->staffId !== '', fn ($q) => $q->where('user_id', (int) $this->staffId))
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(fn ($w) => $w
                    ->where('description', 'like', $term)
                    ->orWhere('user_name', 'like', $term)
                    ->orWhere('entity_name', 'like', $term));
            })
            ->latest()
            ->paginate(20);

        return view('livewire.admin.audit-log-viewer', [
            'logs' => $logs,
        ]);
    }
}
