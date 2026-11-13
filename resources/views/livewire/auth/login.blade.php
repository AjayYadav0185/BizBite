@php
    $field = 'w-full rounded-xl border border-line bg-surface-2 px-4 py-3 text-sm text-ink placeholder:text-ink-3 outline-none transition focus:border-brand-500 focus:bg-surface focus:ring-2 focus:ring-brand-500/20';
@endphp
<div class="grid min-h-screen lg:grid-cols-2">
    {{-- Brand panel --}}
    <div class="relative hidden overflow-hidden bg-brand-900 p-12 text-white lg:flex lg:flex-col">
        <div class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-brand-500/30 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 -left-16 h-96 w-96 rounded-full bg-accent-500/20 blur-3xl"></div>

        <div class="relative flex items-center gap-3">
            <img src="{{ asset('images/bizbite_logo_mark.png') }}" alt="" width="44" height="44" class="h-11 w-11 rounded-full ring-2 ring-white/20" />
            <span class="font-display text-xl font-semibold">BizBite POS</span>
        </div>

        <div class="relative mt-auto max-w-md">
            <h2 class="font-display text-4xl font-semibold leading-tight">Fast billing for busy counters.</h2>
            <p class="mt-4 text-brand-100/80">Built for restaurants, cafés, QSRs and cloud kitchens across Delhi NCR.</p>
            <ul class="mt-8 space-y-3 text-sm text-brand-50/90">
                @foreach (['GST tax invoice with CGST / SGST breakup', 'UPI QR with the exact bill amount', 'KOT printing, hold bills & WhatsApp e-bills', 'Live sales, stock and shift cash reports'] as $point)
                    <li class="flex items-center gap-3">
                        <span class="grid h-6 w-6 place-items-center rounded-full bg-white/10"><x-icon name="check" class="h-3.5 w-3.5" /></span>{{ $point }}
                    </li>
                @endforeach
            </ul>
        </div>
        <p class="relative mt-12 text-xs text-brand-200/60">© {{ date('Y') }} BizBite · Made in India</p>
    </div>

    {{-- Form --}}
    <div class="flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-sm">
            <div class="mb-8">
                <img src="{{ asset('images/bizbite_logo_mark.png') }}" alt="BizBite" width="56" height="56" class="h-14 w-14 rounded-full lg:hidden" />
                <h1 class="mt-4 font-display text-2xl font-semibold lg:mt-0">Welcome back</h1>
                <p class="mt-1 text-sm text-ink-3">Sign in to the billing counter or owner console.</p>
            </div>

            @if (session('status'))
                <p class="mb-4 rounded-xl border border-accent-500/30 bg-accent-500/10 px-4 py-3 text-sm font-medium text-accent-700 dark:text-accent-400" role="status">
                    {{ session('status') }}
                </p>
            @endif

            <form wire:submit="authenticate" class="space-y-4">
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-ink-2">Email</label>
                    <input id="email" type="email" wire:model="email" autofocus autocomplete="username" placeholder="you@restaurant.in"
                           class="{{ $field }} @error('email') border-red-400 @enderror" />
                    @error('email') <p class="mt-1 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                </div>

                <div x-data="{ show: false }">
                    <label for="password" class="mb-1.5 block text-sm font-medium text-ink-2">Password</label>
                    <div class="relative">
                        <input id="password" :type="show ? 'text' : 'password'" type="password" wire:model="password" autocomplete="current-password"
                               class="{{ $field }} pr-16 @error('password') border-red-400 @enderror" />
                        <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-ink-3 hover:text-ink" x-text="show ? 'Hide' : 'Show'"></button>
                    </div>
                    @error('password') <p class="mt-1 text-xs font-medium text-red-500">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-ink-2">
                    <input type="checkbox" wire:model="remember" class="h-4 w-4 rounded border-line-strong text-brand-600 focus:ring-brand-500" />
                    Keep me signed in on this device
                </label>

                <button type="submit" wire:loading.attr="disabled"
                        class="flex h-12 w-full items-center justify-center rounded-xl bg-brand-600 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60">
                    <span wire:loading.remove wire:target="authenticate">Sign in</span>
                    <span wire:loading wire:target="authenticate">Signing in…</span>
                </button>
            </form>

            <div class="mt-8 flex items-center justify-between text-xs text-ink-3">
                <a href="{{ route('download-app') }}" class="font-medium hover:text-brand-ink">Get the Android app</a>
                <button type="button" onclick="bbToggleTheme()" class="flex items-center gap-1 hover:text-ink">
                    <x-icon name="moon" class="h-3.5 w-3.5 dark:hidden" /><x-icon name="sun" class="hidden h-3.5 w-3.5 dark:block" />Theme
                </button>
            </div>
        </div>
    </div>
</div>
