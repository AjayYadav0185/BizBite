<div class="min-h-screen bg-slate-100">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <div class="flex items-center gap-3">
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-slate-900 font-black text-emerald-400">B</span>
                <div>
                    <p class="text-sm font-black leading-tight text-slate-900">{{ auth()->user()?->store?->name }} — Owner Console</p>
                    <p class="text-xs text-slate-500">{{ auth()->user()?->name }} · Admin</p>
                </div>
            </div>
            <nav class="flex items-center gap-1 text-sm font-bold">
                <button wire:click="switchTab('menu')"
                        @class(['rounded-lg px-3 py-2 transition', $tab === 'menu' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:bg-slate-200'])>
                    Menu Management
                </button>
                <button wire:click="switchTab('receipt')"
                        @class(['rounded-lg px-3 py-2 transition', $tab === 'receipt' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:bg-slate-200'])>
                    Receipt
                </button>
                <button wire:click="switchTab('sales')"
                        @class(['rounded-lg px-3 py-2 transition', $tab === 'sales' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:bg-slate-200'])>
                    Sales Summary
                </button>
                <a href="{{ route('pos.billing') }}"
                   class="ml-2 rounded-lg bg-emerald-500 px-3 py-2 text-white transition hover:bg-emerald-400">
                    POS →
                </a>
                <a href="{{ route('logout') }}" class="px-3 py-2 text-slate-400 hover:text-red-500">Logout</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-6 py-8">
        {{-- Each tab is an isolated Livewire component with its own state,
             validation bag and persistence logic. --}}
        @if ($tab === 'menu')
            <livewire:admin.menu-manager :key="'menu'" />
        @elseif ($tab === 'receipt')
            <livewire:admin.receipt-customizer :key="'receipt'" />
        @elseif ($tab === 'sales')
            <livewire:admin.sales-summary :key="'sales'" />
        @endif
    </main>
</div>
