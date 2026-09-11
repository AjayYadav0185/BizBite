<div class="min-h-screen bg-canvas">
    {{-- ============================================================
         APP BAR — sticky, white, thin hairline. Desktop nav is inline;
         mobile collapses into a <details> drawer (zero-JS, Livewire-safe).
    ============================================================ --}}
    <header class="sticky top-0 z-40 border-b border-card-border bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
            {{-- Brand pill (theme brandMain gradient: emerald-500 → 600) --}}
            <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-600 text-lg font-black text-white shadow-card">B</span>
                <span class="flex min-w-0 flex-col text-left leading-tight">
                    <span class="truncate text-sm font-bold text-slate-900">{{ auth()->user()?->store?->name }} — Owner Console</span>
                    <span class="truncate text-xs text-slate-500">{{ auth()->user()?->name }} · Admin</span>
                </span>
            </a>

            {{-- Desktop nav (lg and up) --}}
            <nav class="hidden items-center gap-1 text-sm font-bold lg:flex">
                <button wire:click="switchTab('menu')"
                        @class(['rounded-xl px-3.5 py-2 transition',
                            $tab === 'menu' ? 'bg-brand-500 text-white shadow-sm' : 'text-slate-600 hover:bg-brand-50'])>
                    Menu Management
                </button>
                <button wire:click="switchTab('receipt')"
                        @class(['rounded-xl px-3.5 py-2 transition',
                            $tab === 'receipt' ? 'bg-brand-500 text-white shadow-sm' : 'text-slate-600 hover:bg-brand-50'])>
                    Receipt
                </button>
                <button wire:click="switchTab('sales')"
                        @class(['rounded-xl px-3.5 py-2 transition',
                            $tab === 'sales' ? 'bg-brand-500 text-white shadow-sm' : 'text-slate-600 hover:bg-brand-50'])>
                    Sales Summary
                </button>
                <button wire:click="switchTab('logs')"
                        @class(['rounded-xl px-3.5 py-2 transition',
                            $tab === 'logs' ? 'bg-brand-500 text-white shadow-sm' : 'text-slate-600 hover:bg-brand-50'])>
                    Audit Logs
                </button>
                <a href="{{ route('pos.billing') }}"
                   class="ml-2 rounded-xl bg-brand-500 px-4 py-2 text-white shadow-sm transition hover:bg-brand-400">
                    POS →
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="px-3 py-2 text-sm font-bold text-slate-400 transition hover:text-red-500">Logout</button>
</form>
            </nav>
{{-- Mobile drawer trigger (collapses into a dropdown panel) --}}
            <details class="relative lg:hidden">
                <summary class="grid h-10 w-10 cursor-pointer list-none place-items-center rounded-xl border border-card-border text-slate-600 hover:bg-surface-subtle">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </summary>

                <div class="absolute right-0 top-full z-50 mt-2 w-72 rounded-2xl border border-card-border bg-white p-3 shadow-card-hover">
                    <p class="px-3 py-1 text-[11px] font-black uppercase tracking-widest text-slate-400">Navigate</p>
                    <div class="flex flex-col gap-1">
                        <button wire:click="switchTab('menu')"
                                @class(['flex items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-bold transition',
                                    $tab === 'menu' ? 'bg-brand-500 text-white' : 'text-slate-700 hover:bg-brand-50'])>
                            <span>Menu Management</span>
                            @if ($tab === 'menu')<span class="text-brand-200">●</span>@endif
                        </button>
                        <button wire:click="switchTab('receipt')"
                                @class(['flex items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-bold transition',
                                    $tab === 'receipt' ? 'bg-brand-500 text-white' : 'text-slate-700 hover:bg-brand-50'])>
                            <span>Receipt</span>
                            @if ($tab === 'receipt')<span class="text-brand-200">●</span>@endif
                        </button>
                        <button wire:click="switchTab('sales')"
                                @class(['flex items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-bold transition',
                                    $tab === 'sales' ? 'bg-brand-500 text-white' : 'text-slate-700 hover:bg-brand-50'])>
                            <span>Sales Summary</span>
                            @if ($tab === 'sales')<span class="text-brand-200">●</span>@endif
                        </button>
                        <button wire:click="switchTab('logs')"
                                @class(['flex items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-bold transition',
                                    $tab === 'logs' ? 'bg-brand-500 text-white' : 'text-slate-700 hover:bg-brand-50'])>
                            <span>Audit Logs</span>
                            @if ($tab === 'logs')<span class="text-brand-200">●</span>@endif
                        </button>
                        <a href="{{ route('pos.billing') }}"
                           class="flex items-center justify-between rounded-xl bg-brand-500 px-3 py-2.5 text-sm font-bold text-white">
                            <span>Open POS</span><span class="text-brand-200">→</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-bold text-red-500 hover:bg-red-50">
                                <span>Logout</span><span>↪</span>
                            </button>
                        </form>
                    </div>
                </div>
            </details>
        </div>
    </header>

    {{-- Each tab is an isolated Livewire component with its own state,
         validation bag and persistence logic. --}}
    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        @if ($tab === 'menu')
            <livewire:admin.menu-manager :key="'menu'" />
        @elseif ($tab === 'receipt')
            <livewire:admin.receipt-customizer :key="'receipt'" />
        @elseif ($tab === 'sales')
            <livewire:admin.sales-summary :key="'sales'" />
        @elseif ($tab === 'logs')
            <livewire:admin.audit-log-viewer :key="'logs'" />
        @endif
    </main>
</div>
