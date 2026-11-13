{{-- Shared top bar for the staff POS screens. Param: $active (billing|orders|shift). --}}
@php
    $links = [
        'billing' => ['route' => 'pos.billing', 'label' => 'Billing', 'icon' => 'receipt'],
        'orders' => ['route' => 'pos.orders', 'label' => 'Orders', 'icon' => 'chef'],
        'shift' => ['route' => 'pos.shift', 'label' => 'Shift', 'icon' => 'wallet'],
    ];
    $user = auth()->user();
@endphp
<header class="sticky top-0 z-30 flex h-14 shrink-0 items-center gap-3 border-b border-line bg-surface/95 px-3 backdrop-blur print:hidden sm:px-4">
    <a href="{{ route('pos.billing') }}" class="flex min-w-0 items-center gap-2.5">
        <img src="{{ asset('images/bizbite_logo_mark.png') }}" alt="BizBite" width="32" height="32" class="h-8 w-8 shrink-0 rounded-full" />
        <span class="hidden min-w-0 flex-col leading-tight sm:flex">
            <span class="truncate font-display text-sm font-semibold">{{ $user?->store?->name ?? 'BizBite POS' }}</span>
            <span class="truncate text-[11px] text-ink-3">{{ $user?->name }} · {{ $user?->isAdmin() ? 'Owner' : 'Cashier' }}</span>
        </span>
    </a>

    <nav class="ml-1 flex items-center gap-1 rounded-xl bg-surface-2 p-1 sm:ml-4">
        @foreach ($links as $key => $link)
            <a href="{{ route($link['route']) }}"
               @class(['flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold transition sm:px-3',
                   $active === $key ? 'bg-surface text-brand-ink shadow-card' : 'text-ink-3 hover:text-ink'])>
                <x-icon :name="$link['icon']" class="h-4 w-4" />
                <span class="hidden sm:inline">{{ $link['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="ml-auto flex items-center gap-1.5">
        <span x-data="{ t: '' }" x-init="const f = () => t = new Date().toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' }); f(); setInterval(f, 15000)"
              x-text="t" class="hidden rounded-lg px-2 font-mono text-xs font-semibold text-ink-3 md:block"></span>

        <button type="button" onclick="bbToggleTheme()" title="Toggle light / dark"
                class="grid h-9 w-9 place-items-center rounded-lg text-ink-3 transition hover:bg-surface-2 hover:text-ink">
            <x-icon name="moon" class="h-[18px] w-[18px] dark:hidden" />
            <x-icon name="sun" class="hidden h-[18px] w-[18px] dark:block" />
        </button>

        @if ($user?->isAdmin())
            <a href="{{ route('admin.dashboard') }}"
               class="flex h-9 items-center gap-1.5 rounded-lg border border-line px-3 text-xs font-semibold text-ink-2 transition hover:border-brand-400 hover:text-brand-ink">
                <x-icon name="grid" class="h-4 w-4" /><span class="hidden sm:inline">Admin</span>
            </a>
        @endif

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" title="Logout"
                    class="grid h-9 w-9 place-items-center rounded-lg text-ink-3 transition hover:bg-red-500/10 hover:text-red-500">
                <x-icon name="logout" class="h-[18px] w-[18px]" />
            </button>
        </form>
    </div>
</header>
