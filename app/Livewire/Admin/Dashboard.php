<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * OWNER ADMIN PORTAL — multi-tab shell.
 *
 * A single full-page Livewire component hosting modular child components:
 *
 *   - Livewire\Admin\MenuManager        (menu CRUD + availability toggles)
 *   - Livewire\Admin\ReceiptCustomizer  (print header/footer bindings)
 *   - Livewire\Admin\SalesSummary       (today's KPIs, tenant scoped)
 *   - Livewire\Admin\AuditLogViewer     (owner-only audit trail: prices,
 *     discounts, credit bills, settings, staff activity)
 *
 * Tabs are deep-linkable via the `?tab=` query string so the owner can
 * bookmark each management screen. Every child is an isolated Livewire 3
 * component with its own state, validation and persistence — no page reloads
 * anywhere in the portal.
 */
#[Layout('layouts.app')]
#[Title('BizBite Admin — Store Console')]
final class Dashboard extends Component
{
    /** Active tab, synced with the browser URL for deep links. */
    #[Url(history: true)]
    public string $tab = 'menu';

    /** Whitelist of renderable tabs (child components). */
    private const TABS = ['menu', 'receipt', 'sales', 'logs'];

    public function mount(): void
    {
        $this->authorize('access-admin-portal');

        if (! in_array($this->tab, self::TABS, strict: true)) {
            $this->tab = 'menu';
        }
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, self::TABS, strict: true)) {
            $this->tab = $tab;
        }
    }

    public function render()
    {
        return view('livewire.admin.dashboard');
    }
}
