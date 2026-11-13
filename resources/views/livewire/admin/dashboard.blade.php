@php
    $nav = [
        'Business' => [
            'sales' => ['Today', 'home'],
            'reports' => ['Reports', 'chart'],
        ],
        'Manage' => [
            'menu' => ['Menu & stock', 'menu'],
            'tables' => ['Tables', 'table'],
            'campaigns' => ['Coupons', 'tag'],
            'staff' => ['Staff', 'users'],
        ],
        'Settings' => [
            'store' => ['Store & billing', 'store'],
            'logs' => ['Audit log', 'shield'],
        ],
    ];
    $user = auth()->user();
@endphp
<div class="min-h-screen lg:flex" x-data="{ nav: false }">
    {{-- ============================================== SIDEBAR --}}
    <div x-show="nav" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-black/40 lg:hidden" @click="nav = false"></div>
    <aside :class="nav ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-line bg-surface transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
        <div class="flex items-center gap-3 px-5 py-5">
            <img src="{{ asset('images/bizbite_logo_mark.png') }}" alt="BizBite" width="36" height="36" class="h-9 w-9 rounded-full" />
            <div class="min-w-0 leading-tight">
                <p class="truncate font-display text-sm font-semibold">{{ $user?->store?->name }}</p>
                <p class="text-[11px] text-ink-3">Owner console</p>
            </div>
        </div>

        <div class="px-3">
            <a href="{{ route('pos.billing') }}"
               class="flex items-center justify-between rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                <span class="flex items-center gap-2"><x-icon name="receipt" class="h-4 w-4" />Open POS</span>
                <x-icon name="arrow-right" class="h-4 w-4" />
            </a>
            <a href="{{ route('pos.orders') }}"
               class="mt-1.5 flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium text-ink-2 transition hover:bg-surface-2">
                <x-icon name="chef" class="h-4 w-4" />Kitchen orders
            </a>
        </div>

        <nav class="mt-3 flex-1 space-y-5 overflow-y-auto px-3 pb-4">
            @foreach ($nav as $group => $items)
                <div>
                    <p class="px-3 pb-1.5 text-[11px] font-semibold uppercase tracking-wider text-ink-3">{{ $group }}</p>
                    @foreach ($items as $key => [$text, $icon])
                        <button wire:click="switchTab('{{ $key }}')" @click="nav = false"
                                @class(['flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm font-medium transition',
                                    $tab === $key ? 'bg-brand-soft text-brand-ink' : 'text-ink-2 hover:bg-surface-2 hover:text-ink'])>
                            <x-icon :name="$icon" class="h-[18px] w-[18px]" />{{ $text }}
                        </button>
                    @endforeach
                </div>
            @endforeach
        </nav>

        <div class="border-t border-line p-3">
            <div class="flex items-center gap-2">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-brand-soft font-display text-sm font-semibold text-brand-ink">{{ mb_strtoupper(mb_substr($user?->name ?? 'A', 0, 1)) }}</span>
                <div class="min-w-0 flex-1 leading-tight">
                    <p class="truncate text-sm font-semibold">{{ $user?->name }}</p>
                    <p class="truncate text-[11px] text-ink-3">{{ $user?->email }}</p>
                </div>
                <button type="button" onclick="bbToggleTheme()" title="Toggle light / dark" class="grid h-8 w-8 place-items-center rounded-lg text-ink-3 hover:bg-surface-2 hover:text-ink">
                    <x-icon name="moon" class="h-4 w-4 dark:hidden" /><x-icon name="sun" class="hidden h-4 w-4 dark:block" />
                </button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Logout" class="grid h-8 w-8 place-items-center rounded-lg text-ink-3 hover:bg-red-500/10 hover:text-red-500"><x-icon name="logout" class="h-4 w-4" /></button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ============================================== CONTENT --}}
    <div class="min-w-0 flex-1">
        <header class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-line bg-surface/95 px-4 backdrop-blur lg:hidden">
            <button @click="nav = true" class="grid h-9 w-9 place-items-center rounded-lg border border-line"><x-icon name="menu" class="h-5 w-5" /></button>
            <p class="truncate font-display text-sm font-semibold">{{ $user?->store?->name }}</p>
            <a href="{{ route('pos.billing') }}" class="ml-auto rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white">POS</a>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            @if ($tab === 'sales')
                <livewire:admin.sales-summary :key="'sales'" />
            @elseif ($tab === 'menu')
                <livewire:admin.menu-manager :key="'menu'" />
            @elseif ($tab === 'store')
                <livewire:admin.store-settings :key="'store'" />
            @elseif ($tab === 'reports')
                <livewire:admin.reports :key="'reports'" />
            @elseif ($tab === 'tables')
                <livewire:admin.table-manager :key="'tables'" />
            @elseif ($tab === 'campaigns')
                <livewire:admin.campaign-manager :key="'campaigns'" />
            @elseif ($tab === 'staff')
                <livewire:admin.staff-manager :key="'staff'" />
            @elseif ($tab === 'logs')
                <livewire:admin.audit-log-viewer :key="'logs'" />
            @endif
        </main>
    </div>
</div>
